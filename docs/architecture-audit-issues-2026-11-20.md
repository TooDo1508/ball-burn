# Ballburn — Architecture Audit Issues

> Ngày đánh giá: 2026-11-20 · Repository: `TooDo1508/ball-burn` · Base: `main`  
> Phạm vi: review tĩnh source code, migration, AGENTS.md và project-audit.md; **chưa chạy tests, load test hoặc kiểm tra EC2 runtime**.  
> **Phân biệt:** `Confirmed` = có bằng chứng code/schema; `Needs verification` = cần tái hiện hoặc kiểm tra môi trường; `Planned` = cải tiến tính năng, không mặc định là bug.

## Tổng quan

| ID | Priority | Trạng thái | Vấn đề |
| --- | --- | --- | --- |
| BB-01 | High | Confirmed code path; needs reproduction | Admin đánh dấu PAID cho order hết hạn không recheck overlap |
| BB-02 | High | Confirmed FK conflict path | Bulk delete payment khi payment_receipts còn tham chiếu |
| BB-03 | High (business) | Planned | Chưa có nguồn xác nhận thanh toán tự động đáng tin cậy |
| BB-04 | High | Needs verification | Test suite cũ có failures; cần chạy lại trên HEAD |
| BB-05 | Medium | Confirmed design observation | Repository đang điều phối nhiều nghiệp vụ |
| BB-06 | Medium | Needs verification | Cơ chế cleanup booking/hold hết hạn |
| BB-07 | Medium | Confirmed deployment design | Single point of failure trên một EC2 |
| BB-08 | Medium | Needs verification | Chưa có benchmark/observability được xác nhận |
| BB-09 | High nếu queue được sử dụng | Needs runtime verification | Queue worker production có thực sự chạy? |

## BB-01 — Chuyển order hết hạn sang PAID có thể chiếm trùng lịch

- **Priority:** High · **Evidence:** [EloquentAdminBookingRepository.php](../app/Repositories/Eloquent/EloquentAdminBookingRepository.php), `updateStatus()`, `bulkUpdateStatus()`; [Booking.php](../app/Models/Booking.php), `scopeOccupyingSchedule()`.
- **Quan sát:** Admin lock court/order/segments rồi cập nhật `status=PAID`, nhưng không tái kiểm tra overlap của từng segment trước khi đổi trạng thái. Scope chỉ coi pending còn hạn hoặc paid là đang chiếm lịch.
- **Rủi ro:** Pending A hết hạn → B đặt cùng giờ → admin xác nhận A → cả A và B chiếm lịch.
- **Hướng xử lý:** Trong cùng transaction, sau khi lock court theo thứ tự ID, kiểm tra tất cả segment của order với các booking đang chiếm lịch, **loại trừ chính order hiện tại**. Định nghĩa chính sách rõ ràng cho late payment (manual review/refund/reschedule); áp dụng cho cả single và bulk status update.
- **Acceptance:** Không thể chuyển A sang PAID khi slot đã bị B chiếm; không làm ảnh hưởng việc xác nhận đơn còn hợp lệ; test đồng thời trên MySQL.
- **Note:** Locking hiện có là điểm tốt; **không** bỏ lock khi sửa. Cần xem cả luồng tích hợp SePay sau này.

## BB-02 — Bulk delete xung đột FK payment_receipts

- **Priority:** High · **Evidence:** [EloquentAdminBookingRepository.php](../app/Repositories/Eloquent/EloquentAdminBookingRepository.php), `bulkDelete()` xóa `payments` trước; [migration payment_receipts](../database/migrations/2026_11_09_000001_create_payment_receipts_table.php) khai báo `payment_id` với `restrictOnDelete()`; [EloquentBookingRepository.php](../app/Repositories/Eloquent/EloquentBookingRepository.php) tạo receipt cùng payment.
- **Quan sát:** Nếu payment có receipt, thao tác xóa payment bị FK chặn. Transaction rollback, admin không xóa được order.
- **Hướng xử lý:** Ưu tiên chính sách lưu vết/soft delete với dữ liệu thanh toán; nếu hard delete được phép, xử lý receipt trước payment và kiểm tra các FK liên quan, các job OCR đang chạy, audit và transaction ngân hàng.
- **Acceptance:** Integration test tạo pending order + payment + receipt rồi bulk delete theo chính sách được chọn; không vi phạm FK, không mất chứng cứ giao dịch; thao tác idempotent và audit đầy đủ.
- **Note:** Không đơn giản chuyển FK sang cascade nếu còn yêu cầu lưu bằng chứng thanh toán.

## BB-03 — Xác minh thanh toán tự động

- **Priority:** High (business readiness) · **Status:** Planned, **không phải bug của OCR**.
- **Evidence:** [PaymentReceiptProcessingService.php](../app/Services/Payments/PaymentReceiptProcessingService.php), [PaymentReceiptMappingService.php](../app/Services/Payments/PaymentReceiptMappingService.php). OCR chỉ đối chiếu ảnh, không xác thực tiền đã vào tài khoản.
- **Hướng xử lý:** Tích hợp nguồn giao dịch ngân hàng/SePay được xác thực; verify webhook, lưu event ID duy nhất, đối chiếu reference + amount + recipient, transaction/idempotency, retry và reconciliation; late payment chuyển review nếu slot đã nhường.
- **Acceptance:** Webhook duplicate/out-of-order không ghi nhận hai lần; sai số tiền hoặc sai mã không auto-PAID; event có audit và khả năng replay an toàn.
- **Note:** Không sử dụng ảnh chuyển khoản do khách gửi làm bằng chứng duy nhất để auto-PAID. Phối hợp với kế hoạch SePay riêng.

## BB-04 — Chuẩn hóa test suite

- **Priority:** High · **Status:** Needs verification.
- **Evidence:** [docs/project-audit.md](project-audit.md) ghi nhận một lần chạy MySQL: 57 tests, 46 passed, 8 assertion failures, 3 errors (kết quả lịch sử, **không phải trạng thái HEAD hiện tại**).
- **Hướng xử lý:** Chạy lại suite trên HEAD với MySQL phù hợp production; phân loại lỗi fixture/schema cũ, lỗi môi trường và regression thực; cập nhật test cho order-centric workflow.
- **Acceptance:** CI MySQL green; test BB-01, BB-02, expiry, idempotency, payment và rollback.
- **Note:** SQLite in-memory không thay thế hoàn toàn MySQL cho locking và migration đặc thù.

## BB-05 — Ranh giới Service/Repository

- **Priority:** Medium · **Status:** Confirmed design observation.
- **Evidence:** [EloquentBookingRepository.php](../app/Repositories/Eloquent/EloquentBookingRepository.php), `createPendingBatchForGuest()` vừa kiểm tra quy tắc nghiệp vụ, vừa tạo order/segments/payment/receipt/notifications.
- **Hướng xử lý:** Cân nhắc chuyển orchestration sang application service/use case; repository giữ query/persistence; định nghĩa transaction boundary rõ ràng.
- **Acceptance:** Giữ nguyên hành vi và locking; unit/integration tests bảo vệ flow; không phát sinh query thừa.
- **Note:** Đây là **technical debt**, không phải lỗi chức năng; ưu tiên sau BB-01/02/04, tránh refactor lớn khi chưa có tests.

## BB-06 — Cleanup hết hạn

- **Priority:** Medium · **Status:** Needs verification.
- **Evidence:** `Booking::occupyingSchedule()` bỏ qua pending quá deadline; `BookingHold` cũng lọc `expires_at` ở truy vấn availability.
- **Cần kiểm tra:** Scheduler/cron trên EC2, cleanup dữ liệu vật lý, retention và dashboard phân biệt expired vs pending.
- **Hướng xử lý:** Nếu cần cleanup, dùng scheduled command có idempotency, batch size và audit; không xóa giao dịch phục vụ đối soát.
- **Acceptance:** Slot được giải phóng đúng hạn ngay cả khi cleanup chậm; không có dữ liệu pending cũ gây hiểu nhầm.
- **Note:** Logic query đã tự loại hold/order hết hạn; thiếu cleanup vật lý **không đồng nghĩa** slot vẫn bị khóa.

## BB-07 — Single point of failure

- **Priority:** Medium · **Status:** Confirmed deployment design.
- **Evidence:** [compose.prod.yaml](../compose.prod.yaml): Nginx, app, MySQL và Redis trên cùng Docker host.
- **Hướng xử lý:** Backup MySQL ngoài host + kiểm tra restore định kỳ; giám sát disk, RAM, container health; kế hoạch recovery và RPO/RTO.
- **Acceptance:** Có bản backup khôi phục thử thành công, hướng dẫn restore và cảnh báo khi hết dung lượng.
- **Note:** Chưa cần microservices/Kubernetes cho quy mô 8 sân; tối ưu reliability trước.

## BB-08 — Performance benchmark & observability

- **Priority:** Medium · **Status:** Needs verification.
- **Evidence:** [EloquentAdminDashboardRepository.php](../app/Repositories/Eloquent/EloquentAdminDashboardRepository.php), `revenueRows()` lấy rows về PHP; chưa có số đo tải xác nhận trong phạm vi audit.
- **Hướng xử lý:** Đo p95 latency, throughput, DB slow queries, Redis, worker lag; dùng EXPLAIN trước khi đổi index; cân nhắc SQL aggregation khi dữ liệu tăng.
- **Acceptance:** Có baseline, ngưỡng cảnh báo và kết quả load test cho availability/hold/checkout.
- **Note:** Chưa chứng minh `revenueRows()` là bottleneck; không tối ưu theo phỏng đoán.

## BB-09 — Queue worker production

- **Priority:** High nếu OCR/email dùng async · **Status:** Needs runtime verification.
- **Evidence:** [ProcessPaymentReceiptJob.php](../app/Jobs/ProcessPaymentReceiptJob.php) chạy queue `payments`; [GuestBookingService.php](../app/Services/Booking/GuestBookingService.php) dispatch job; [compose.prod.yaml](../compose.prod.yaml) định nghĩa worker. Trạng thái container EC2 chưa được kiểm tra.
- **Hướng xử lý:** Xác nhận worker thực sự chạy, queue connection, retry/backoff, failed jobs và supervisor/restart; nếu cố ý không chạy worker, phải chọn cơ chế xử lý khác có kiểm soát.
- **Acceptance:** Receipt QUEUED được xử lý; lỗi OCR được retry/ghi failed; job tồn đọng có cảnh báo.
- **Note:** Có service trong Compose **không chứng minh** service đang chạy ở production.

---

## Thứ tự triển khai đề xuất

1. **P0 — Correctness:** BB-01, BB-02; thêm regression tests trước khi bật auto-payment.
2. **P1 — Operational readiness:** BB-04, BB-09, backup/restore của BB-07.
3. **P2 — Payment automation:** BB-03, sau khi các trạng thái booking đã an toàn.
4. **P3 — Maintainability & performance:** BB-05, BB-06, BB-08 theo số liệu.

## Quy tắc cập nhật

- Mỗi mục phải có owner, trạng thái, PR sửa lỗi, bằng chứng test và ngày xác minh trước khi đánh dấu Done.
- Không xem các mục `Needs verification` là bug đã tái hiện.
- Báo cáo này là **issue register**, không thay đổi runtime code hoặc database.
