# Implementation Plan: OCR biên lai chuyển khoản và mapping Booking

## 1. Mục tiêu

Triển khai chức năng cho khách đặt sân upload ảnh biên lai chuyển khoản, tự động trích xuất thông tin bằng OCR và đối chiếu với Payment/BookingOrder hiện có.

**Nguyên tắc bắt buộc:** OCR chỉ xác định *thông tin trên ảnh có khớp đơn hàng hay không*, **không chứng minh tiền đã vào tài khoản**. Không tự động chuyển `PAID` chỉ dựa trên ảnh; cần admin xác minh giao dịch nhận thực tế hoặc webhook/API ngân hàng đáng tin cậy.

## 2. Hiện trạng repository và phạm vi

- Backend đang chạy Laravel 13, MySQL 8.4 và các lớp Service + Repository. Không chuyển sang PostgreSQL.
- Public frontend là Vue 3 trong `src/`; admin dùng Vue + Inertia trong `resources/js/`.
- Queue database và worker Docker `queue-worker` đã được cấu hình; worker ưu tiên queue `payments` trước queue mặc định.
- Tesseract OCR tiếng Việt/Anh chạy trong PHP worker image; mapping receipt được dispatch sau khi transaction tạo booking/payment đã commit.
- Thanh toán hiện là `MANUAL_QR`; người dùng đã chọn VietQR VPBank (`VPB`) để sinh QR. Admin nhập số tài khoản VPBank dùng chung tại `/admin/settings/contact`; không upload QR tĩnh nữa.
- Mỗi lượt giữ sân sinh mã `BBPAY` + 10 ký tự chữ/số ngẫu nhiên. VietQR nhận số tiền server tính và mã này làm `addInfo` không dấu/ký tự đặc biệt. Khi khách gửi proof, mã được lưu vào `payments.qr_payload` và `payments.provider_metadata.payment_reference`.
- Chưa có API/webhook ngân hàng; ảnh proof và OCR không xác minh tiền đã vào.
- Ảnh proof lưu ở private disk `local`, trường `payments.proof_image_path`; bảng `payment_receipts` lưu trạng thái queue, OCR và kết quả mapping.
- Bảng `bank_transfer_transactions` đã có với unique `bank_transaction_id`, nhưng chưa có workflow controller/service đối soát được nối vào ứng dụng.
- Admin có thể đổi trạng thái BookingOrder trong trang quản lý booking; thao tác hiện tại chưa phải workflow đối soát ngân hàng hoàn chỉnh và chưa đồng bộ Payment một cách nguyên tử.
- Khách dùng hold token để giữ slot và status token ngẫu nhiên/hash để xem booking. `booking_orders.status_token_hash` là hash token; không dùng ID tuần tự làm quyền truy cập.
- Job `ProcessPaymentReceiptJob` đọc ảnh proof bằng Tesseract, parser chuẩn hóa dữ liệu rồi gọi mapper; kết quả được lưu trong `payment_receipts`. Job không đổi trạng thái booking/payment và không xác nhận tiền đã vào tài khoản.

### Ngoài phạm vi MVP

- Không tự động xác nhận tiền vào ngân hàng.
- Không dùng AI Vision mặc định; chỉ đánh giá bổ sung khi OCR có tỷ lệ đọc sai cao.
- Không tự động hoàn tiền.

## 3. Luồng nghiệp vụ

1. Khách chọn sân/ngày/slot; backend tạo lượt giữ sân có hạn 5 phút và trả mã `BBPAY…`, tổng tiền và hạn giữ. Ở bước này chưa có BookingOrder hoặc Payment.
2. Backend tạo ảnh VietQR riêng cho lượt giữ bằng STK VPBank site-wide, amount server tính và `addInfo` là mã random `BBPAY…` chỉ gồm chữ/số.
3. Khách quét QR, chuyển khoản với nội dung được điền sẵn, rồi gửi ảnh proof.
4. Backend kiểm tra hold token, server-side giá/slot và file; trong transaction tạo `BookingOrder` `PENDING_PAYMENT`, các Booking con, Payment `PENDING` và receipt `QUEUED`. Payment giữ số tiền/tiền tệ chuẩn từ server, mã tham chiếu ở `qr_payload`/`provider_metadata`, và ảnh private ở `proof_image_path`.
5. Sau commit, backend dispatch `ProcessPaymentReceiptJob` vào queue `payments`. Tesseract đọc ảnh, parser chuẩn hóa `payment_reference`, `amount`, `currency`, `transaction_id` và `recipient_account` nếu đọc được.
6. `PaymentReceiptMappingService` tìm Payment duy nhất theo mã tham chiếu hiện có, so sánh chính xác amount/currency, kiểm tra payment/order còn chờ và deadline còn hiệu lực, đồng thời cảnh báo transaction ID đã ghi nhận. Thiếu hoặc không khớp dữ liệu trả `NEEDS_REVIEW`; lỗi OCR được retry và cuối cùng đánh dấu `FAILED`.
7. Kết quả `MATCHED` chỉ xác nhận nội dung OCR khớp một Payment đang chờ; không đổi `Payment.status` hoặc `BookingOrder.status` sang `PAID`.
8. Admin phải kiểm tra giao dịch thực nhận trong ngân hàng trước khi xác nhận. Trang admin hiện có thao tác đổi trạng thái đơn nhưng chưa phải workflow xác minh hoàn chỉnh; milestone verification phải cập nhật Payment và BookingOrder cùng transaction, ghi audit và chống xác nhận lặp. Không lấy OCR, ảnh QR, ảnh chụp màn hình hoặc redirect làm bằng chứng tiền vào.

## 4. Thiết kế trạng thái

Tách trạng thái OCR khỏi trạng thái tiền thực nhận:

| Đối tượng | Trạng thái đề xuất | Ý nghĩa |
|---|---|---|
| `booking_orders` | `PENDING_PAYMENT`, `PAID` | Trạng thái đơn hiện có của dự án; hạn 5 phút nằm ở `payment_deadline_at` |
| `bookings` | `PENDING_PAYMENT`, `CONFIRMED`, `EXPIRED`, `CANCELLED`, `COMPLETED` | Trạng thái từng segment sân; khi khách gửi proof, order/segment bắt đầu `PENDING_PAYMENT` |
| `payments` | `PENDING`, `PAID`, `FAILED`, `EXPIRED`, `REFUND_PENDING`, `REFUNDED` | Trạng thái tiền hiện có |
| Kết quả mapping | `MATCHED`, `NEEDS_REVIEW` | Kết quả đối chiếu nội dung OCR; không phải trạng thái Payment/Booking |
| `payment_receipts` | `QUEUED`, `PROCESSING`, `MATCHED`, `NEEDS_REVIEW`, `FAILED` | Theo dõi OCR/mapping; không thay đổi trạng thái thanh toán |

`MATCHED` không đồng nghĩa với `PAID`. Trạng thái OCR được lưu riêng trong `payment_receipts`, không thêm vào enum MySQL của Booking hoặc Payment.

### Quy tắc giữ slot 5 phút

- `booking_orders.payment_deadline_at` tính ở backend; frontend chỉ hiển thị đếm ngược.
- Upload ảnh không được mặc nhiên gia hạn vô hạn.
- MVP nên định nghĩa chính sách rõ ràng: ví dụ cho phép một khoảng xác minh giới hạn và hiển thị `AWAITING_VERIFICATION`; admin xử lý trong thời gian đó.
- Job hết hạn phải khóa booking/payment trong transaction và kiểm tra lại trạng thái trước khi giải phóng slot.
- Nếu tiền thực nhận sau khi slot đã hết hạn/được đặt lại, đưa vào đối soát thủ công hoặc hoàn tiền; **không tự tái kích hoạt slot**.

## 5. Database changes

### `payments` và `booking_orders` hiện có

- `payments`: MySQL `DECIMAL(10,2)`, `currency`, `status`, `qr_payload`, `provider_metadata`, `proof_image_path`, transaction/order IDs.
- `booking_orders`: `booking_code`, hash status token, `status`, `payment_deadline_at`, tổng tiền và tiền tệ.
- Không thêm `payment_code`, `expires_at`, `bank_code`, `recipient_account` hoặc `verified_by` vào `payments` cho mapper hiện tại. Mã tham chiếu đang nằm ở `qr_payload` và JSON `provider_metadata`; hạn nằm trên `booking_orders`.
- Số tài khoản VPBank cấu hình chung dùng để tạo QR và so khớp recipient account nếu OCR đọc được; tên chủ tài khoản/ngân hàng OCR chưa được so khớp.

### `payment_receipts` (đã thêm)

| Column | Type | Ghi chú |
|---|---|---|
| `id` | bigint | PK theo chuẩn migration MySQL của repo |
| `payment_id` | FK unique | Mỗi payment có tối đa một ảnh proof ở luồng hiện tại |
| `status` | varchar | `QUEUED`, `PROCESSING`, `MATCHED`, `NEEDS_REVIEW`, `FAILED` |
| `attempts` | unsigned smallint | Số lượt xử lý queue |
| `ocr_raw_text` | longtext nullable | Kết quả OCR, giới hạn lưu 20.000 ký tự |
| `extracted_data` | JSON nullable | Các trường OCR đã parse |
| `mapping_result` | JSON nullable | Kết quả từng tiêu chí từ mapper |
| `ocr_error` | text nullable | Thông báo lỗi đã lọc dữ liệu nhạy cảm |
| `processing_started_at`, `processed_at` | datetime nullable | Lease stale sau 75 giây và thời điểm xử lý UTC |
| `created_at`, `updated_at` | datetime | Audit UTC |

- Index `payment_receipts(payment_id, created_at)`.
- Repo đã có `bank_transfer_transactions` với `bank_transaction_id` unique. Không tạo bảng bank transaction thứ hai; dùng bảng hiện có khi nối mapping duplicate check/reconciliation.
- Tạo audit log khi admin xác nhận/từ chối receipt ở milestone sau.

## 6. Cấu trúc source code

```text
app/
├── Services/Payments/PaymentReceiptMappingService.php   # Đã triển khai: chuẩn hóa/đối chiếu OCR fields
├── Services/Payments/VietQrImageUrlService.php           # Đã triển khai: tạo QR theo STK/amount/reference
├── Repositories/Contracts/PaymentMappingRepository.php  # Đã triển khai
├── Repositories/Eloquent/EloquentPaymentMappingRepository.php # Đã triển khai
├── Jobs/ProcessPaymentReceiptJob.php                    # Đã nối vào guest proof flow
├── Services/Payments/ReceiptOcrInterface.php             # Đã có adapter Tesseract
├── Services/Payments/PaymentReceiptOcrParser.php         # Đã parse các trường chuyển khoản cơ bản
├── Services/Payments/PaymentReceiptProcessingService.php # Đã xử lý OCR và mapping trong job
└── Models/Payment.php, Booking.php, BookingOrder.php     # Các entity hiện có
```

### Trách nhiệm

- **Controller**: xác thực/ủy quyền, nhận request, trả response.
- **OCR service**: Tesseract `vie+eng` đọc ảnh -> raw text, không quyết định thanh toán.
- **Parsing service**: raw text -> dữ liệu có cấu trúc; để `null` nếu không đọc được và không tự sửa nhầm ký tự mã thanh toán.
- **VietQrImageUrlService (đã có)**: tạo URL ảnh VPBank `compact2` với amount server tính và `addInfo` chữ/số từ reference.
- **PaymentReceiptMappingService (đã có)**: tìm Payment thuộc provider `MANUAL_QR` theo `qr_payload` hoặc `provider_metadata.payment_reference`; chuẩn hóa dấu phân tách legacy; kiểm tra amount/currency chính xác, trạng thái Payment/BookingOrder, hạn thanh toán và transaction ID trùng.
- **Verification service**: chỉ cho admin xác nhận sau khi kiểm tra tiền nhận; transaction + lock + audit + idempotency.
- **Repository**: truy cập MySQL/Eloquent, không chứa nghiệp vụ OCR.

## 7. Docker và dependencies

1. PHP image cài binary Tesseract và language packs `eng`/`vie`; adapter gọi binary qua Symfony Process, không cần thêm package Composer.
2. `app` và `queue-worker` mount cùng source/storage volume, nên worker đọc được ảnh private đã upload.
3. OCR process timeout là 40 giây; job có giới hạn 3 lần retry và worker xử lý queue `payments` trước queue mặc định.
4. Sau khi thay Dockerfile hoặc migration: rebuild `app`/`queue-worker`, chạy migration Laravel, rồi xem lỗi queue bằng `docker compose logs -f queue-worker`.

## 8. API contract đề xuất

### Tích hợp hiện tại và API tương lai

Khách gửi proof qua `POST /booking/payment-proof` cùng hold token, payment reference và status token. Backend tạo Payment/BookingOrder/receipt trong transaction, sau commit đẩy OCR/mapping vào queue. Kết quả hiện được lưu nội bộ; chưa có API polling/public hiển thị receipt hoặc admin review UI.

### API tương lai (để khách xem trạng thái OCR)

`POST /api/bookings/{booking}/payment-receipts`

- Auth: booking access token (guest) hoặc user có quyền sở hữu booking.
- Content type: `multipart/form-data`.
- Field: `receipt` (jpg/png/webp, giới hạn dung lượng, MIME và kích thước ảnh).
- Response `202 Accepted`:

```json
{
  "receipt_id": "rec_123",
  "ocr_status": "UPLOADED",
  "payment_status": "PENDING"
}
```

### `GET /api/bookings/{booking}/payment-receipts/{receipt}`

- Auth tương tự, receipt phải thuộc booking.
- Response gồm `ocr_status`, `match_summary`, `payment_status`, không lộ raw OCR hay dữ liệu nhạy cảm không cần thiết.

### Admin

- `GET /api/admin/payments?status=REVIEW_REQUIRED`.
- `GET /api/admin/payments/{payment}/receipts`.
- `POST /api/admin/payments/{payment}/verify` (chỉ admin có quyền; yêu cầu reference giao dịch đã đối chiếu).
- `POST /api/admin/payments/{payment}/reject` (lý do bắt buộc).

## 9. OCR và parsing

Ví dụ:

```php
$text = (new TesseractOCR($absolutePath))
    ->lang('vie', 'eng')
    ->run();
```

Chuẩn hóa text: Unicode, khoảng trắng, dấu phân tách tiền, ký tự OCR dễ nhầm (`O/0`, `I/1`) nhưng **không tự sửa mã thanh toán một cách mù quáng**.

- `payment_reference`: parser map `payment_code`/nội dung chuyển khoản thành mã `BBPAY[A-Z0-9]{10}`. Mapper bỏ dấu phân tách để tìm reference mới và legacy; không fuzzy-correct OCR (`O/0`, `I/1`).
- `amount`: parser phải trả số nguyên hoặc chuỗi decimal chuẩn hóa (`75000`, `75000.00`), không đưa float hoặc chuỗi phân tách hàng nghìn mơ hồ vào matcher.
- `currency`: trim/uppercase rồi so khớp chính xác với `payments.currency`.
- `recipient_account`: nếu OCR đọc được, chuẩn hóa chữ số rồi so chính xác với STK VPBank đã cấu hình; mismatch hoặc field OCR sai định dạng -> `NEEDS_REVIEW`. Nếu OCR không đọc được thì không chặn mapping nhưng cần admin xác minh tiền thực nhận.
- `recipient_bank`: chưa được matcher; QR được tạo với VPBank (`VPB`) nhưng OCR đọc tên ngân hàng chưa được chuẩn hóa/đối chiếu.
- `transaction_id`: chỉ dùng để cảnh báo nếu đã tồn tại trong `bank_transfer_transactions`; không xem là bằng chứng giao dịch độc lập.
- `transfer_time`: lưu kèm timezone khi có, không dựa vào timestamp ảnh để xác minh tiền vào.

Ví dụ kết quả parser:

```json
{
  "payment_reference": "BBPAYA1B2C3D4E5",
  "amount": "150000.00",
  "currency": "VND",
  "recipient_bank": null,
  "recipient_account": null,
  "transaction_id": "FT123456789",
  "transfer_time": null
}
```

## 10. Matching rules

| Kiểm tra | Kết quả |
|---|---|
| Mã tham chiếu không có hoặc không tìm thấy đúng một Payment | `NEEDS_REVIEW` |
| Mã tham chiếu khớp, số tiền và tiền tệ chính xác | Điều kiện bắt buộc để `MATCHED` |
| OCR thiếu/sai amount hoặc currency | `NEEDS_REVIEW` |
| OCR đọc được STK nhận khác STK cấu hình hoặc dữ liệu sai định dạng | `NEEDS_REVIEW` |
| OCR không đọc được STK nhận | Không chặn mapping; admin vẫn phải xác minh thực nhận |
| Payment không `PENDING`, order không `PENDING_PAYMENT`, hoặc deadline hết | `NEEDS_REVIEW`; xử lý thanh toán muộn thủ công |
| Transaction ID OCR đã có trong `bank_transfer_transactions` | `NEEDS_REVIEW` + cảnh báo duplicate |
| Không có transaction ID OCR | Không chặn mapping; admin vẫn phải xác minh thực nhận |
| Tên ngân hàng OCR | Chưa được so khớp tự động; dùng STK configured và kiểm tra thực nhận thủ công |
| Mapper trả `MATCHED` | Chỉ khớp nội dung ảnh, không đổi Payment/BookingOrder sang PAID |

Kết quả `MATCHED` chỉ có nghĩa **nội dung ảnh khớp payment**, không chứng thực giao dịch.

## 11. Bảo mật và chống gian lận

- Không tin `amount`, `booking_id`, `payment_code` do client gửi; lấy dữ liệu chuẩn từ database.
- Ảnh biên lai có thể giả mạo; không xác nhận `PAID` tự động từ OCR hoặc AI.
- File upload: whitelist MIME thực, kích thước, kiểm tra decode ảnh, tên file random, private storage, giới hạn số lần upload và rate limit.
- Không render trực tiếp file do user upload như HTML/SVG; sử dụng URL ký hạn ngắn khi admin cần xem.
- Không ghi raw OCR hoặc số tài khoản đầy đủ vào application logs.
- Kết quả mapping che số tài khoản, chỉ giữ trạng thái khớp và vài số cuối.
- Admin verify phải có RBAC, audit trail và transaction lock (`SELECT ... FOR UPDATE`).
- Chống double-confirm và duplicate transaction bằng idempotency/unique constraints.
- Thiết lập retention/deletion policy cho ảnh và dữ liệu cá nhân.

## 12. Frontend Vue 3

### User

- Luồng booking hiện nằm trong `src/pages/BookingPage.vue`; QR VietQR sinh từ STK site-wide ở `/admin/settings/contact`, amount server tính và mã `BBPAY…`; trang hiển thị nội dung chuyển khoản giống `addInfo`.
- Proof upload cũng đang nằm trong trang booking; chưa có OCR status hoặc receipt polling.
- Khi thêm OCR UI, giữ rõ hai trạng thái “Nội dung ảnh khớp/chờ kiểm tra” khác với “Đã xác nhận thanh toán”.

### Admin

- Admin hiện xem proof trong chi tiết booking tại `resources/js/pages/admin/bookings/Show.vue`; status order `PAID` có thể được đổi thủ công, nhưng thao tác này chưa xác minh transaction ID hoặc đồng bộ trạng thái Payment.
- Chưa có danh sách payment review, OCR result, hoặc UI xác nhận transfer ID.
- Khi có OCR persistence, thêm trạng thái mapping/result vào chi tiết booking; nút xác nhận chỉ bật sau khi admin tự kiểm tra giao dịch ngân hàng.

## 13. Test cases / Acceptance criteria

- [x] Exact payment reference + exact amount/currency + Payment/BookingOrder pending trong deadline -> mapper trả `MATCHED`.
- [x] Unknown/ambiguous reference, thiếu/sai amount/currency, payment/order state không còn chờ, deadline hết hoặc transaction ID trùng -> `NEEDS_REVIEW`.
- [x] Mapper không đổi payment/booking sang `PAID`.
- [ ] Guest chỉ upload/xem receipt thuộc booking giữ token hợp lệ.
- [ ] OCR đọc được ảnh rõ bằng tiếng Việt và tiếng Anh.
- [ ] OCR lỗi không làm mất booking/payment; receipt chuyển `FAILED`/`NEEDS_REVIEW`.
- [ ] Lưu OCR/mapping bền vững gắn với ảnh receipt và tránh xử lý receipt trùng.
- [ ] Admin xác nhận sau khi kiểm tra giao dịch thực tế -> order/payment `PAID` đúng một lần.
- [ ] Hai admin xác nhận đồng thời không gây cập nhật trùng.
- [ ] Booking hết hạn/đã bán lại không được tự kích hoạt bởi receipt đến muộn.
- [ ] File giả dạng ảnh, quá dung lượng, sai MIME bị từ chối.
- [ ] Worker restart/retry không gây cập nhật trạng thái sai.
- [ ] Email xác nhận chỉ dispatch sau khi commit trạng thái `PAID`.

## 14. Thứ tự thực hiện (milestones)

1. **M1 – Payment mapping (đã triển khai):** contract/repository tìm Payment theo mã tham chiếu hiện có; service kiểm tra mã, amount, currency, STK nhận nếu OCR đọc được, transaction ID đã biết và trạng thái/hạn order. Không sửa trạng thái tiền.
2. **M2 – Receipt persistence (đã triển khai):** migration MySQL `payment_receipts`, model/repository và liên kết với Payment/ảnh proof private.
3. **M3 – OCR Job (đã triển khai):** Tesseract trong PHP image, interface, parser `vie` + `eng`, giới hạn timeout, retry và failure state.
4. **M4 – OCR mapping integration (đã triển khai):** tạo receipt `QUEUED` cùng transaction booking/payment; sau commit dispatch job queue `payments`, lưu kết quả mapping mà không đánh dấu paid.
5. **M5 – UI:** trạng thái OCR/result trong public status và admin booking details.
6. **M6 – Verification:** admin đối chiếu giao dịch thực nhận, cập nhật payment/order trong transaction, audit và notification.
7. **M7 – Edge cases:** hết hạn 5 phút, chuyển tiền muộn, upload trùng, race conditions, retention, bảo mật và kiểm thử OCR trên nhiều mẫu biên lai.

## 15. Hướng mở rộng

- Tạo `ReceiptOcrInterface` để có thể thay Tesseract bằng AI Vision khi cần.
- Sau này tích hợp VPBank/Casso/SePay webhook (khi được cấp quyền): đưa dữ liệu giao dịch đã xác thực vào cùng `PaymentReconciliationService`, tự động `PAID` chỉ khi đủ điều kiện.
- Đo tỷ lệ OCR thành công, tỷ lệ cần review và thời gian admin xác minh trước khi quyết định dùng AI.

## 16. Definition of Done

MVP OCR hoàn tất khi khách có thể upload proof, worker OCR tự động đọc và lưu mapping với đúng Payment/BookingOrder theo schema hiện tại, admin xem được kết quả và chỉ xác nhận `PAID` sau khi kiểm tra tiền thực nhận; mọi trường hợp lỗi/hết hạn/đồng thời đều có trạng thái và xử lý rõ ràng. Hiện đã triển khai M1–M4; UI xem kết quả, admin confirmation và kiểm thử/đối soát edge cases vẫn chưa triển khai.
