# Kế hoạch quản lý đặt sân và thanh toán BALLBURN

## Mục tiêu

Thêm khu vực admin để nhân viên theo dõi booking do khách tự đặt, xác minh trạng thái thanh toán và biết booking nào đã được xác nhận sử dụng sân. Giao diện tham chiếu `docs/Giao diện quản lý đặt sân BALLBURN.png`; giữ Vue + Inertia trong `resources/js/`, thiết kế token hiện có và quy tắc phân quyền admin.

## Định hướng đã thống nhất

- Ưu tiên QR động riêng cho từng booking, chứa đúng số tiền và mã/key đơn hàng; máy chủ xác nhận tự động từ webhook giao dịch đã xác minh.
- Quét QR, quay lại trang thành công hoặc tải ảnh chụp màn hình không tự chứng minh tiền đã vào tài khoản.
- Giao dịch ngân hàng không ghép được do thiếu/sai nội dung phải vào hàng đợi đối soát. Nhân viên kiểm tra giao dịch thực nhận, rồi ghi nhận mã tham chiếu ngân hàng duy nhất, số tiền, thời điểm và người xử lý.
- Trạng thái `CONFIRMED` nghĩa là đã xác nhận giữ sân sau khi thanh toán được xác minh (hoặc booking do admin tạo theo quy trình riêng). `COMPLETED` chỉ dùng sau khi thời gian sử dụng sân kết thúc.
- Provider cụ thể vẫn chưa được chọn. Vì vậy giai đoạn đầu có thể làm danh sách và nhập/đối soát giao dịch thủ công; adapter QR/webhook cụ thể sẽ nối vào sau khi provider được duyệt.

## Hiện trạng đã kiểm tra

- `/admin/courts` đã có lịch sân, xem booking theo ngày, khóa lịch và tạo booking xác nhận ngay do admin đặt hộ.
- `Booking` và `booking_status_history` đã có trạng thái, hạn thanh toán, số tiền, mã đơn và lịch sử chuyển trạng thái.
- Migration đã tạo bảng `payments`, `payment_webhook_events` và `refunds`, nhưng chưa có model/repository/service/controller quản lý thanh toán hoặc endpoint webhook.
- Chưa có trang danh sách booking admin. Repository booking hiện chỉ hỗ trợ tạo booking, kiểm tra token khách và các luồng tạo đặt sân.
- Luồng khách hiện tạo booking trực tiếp ở `CONFIRMED`, `payment_deadline_at = null`, đồng thời ghi chú “thanh toán sẽ được bổ sung sau”. Đây là điểm phải sửa trước khi có thể vận hành thanh toán QR và giữ chỗ 5 phút đúng yêu cầu.
- Chưa chọn nhà cung cấp thanh toán. Vì vậy chưa thể coi thao tác bấm “Đánh dấu đã thanh toán” là xác minh thanh toán điện tử.
- Có trường hợp khách đã chuyển khoản nhưng bỏ trống/sai nội dung nên hệ thống không tự ánh xạ giao dịch vào booking; cần một hàng đợi đối soát thủ công có audit.

## Phạm vi giao diện

Trang mới `/admin/bookings` gồm:

1. **Thẻ tổng quan theo khoảng ngày đang lọc:** tổng booking; đã thanh toán; chờ thanh toán; đã hủy/hết hạn; tổng doanh thu đã thu. Định nghĩa rõ doanh thu là các khoản thanh toán đã xác minh, không cộng booking chưa trả hoặc booking chỉ mới được tạo.
2. **Bộ lọc và tìm kiếm:** ngày đặt sân (từ–đến), sân, trạng thái booking, trạng thái thanh toán, tìm theo mã booking/tên/số điện thoại/email; có phân trang và giữ nguyên query khi chuyển trang.
3. **Bảng danh sách:** mã đơn, ngày giờ sân theo `Asia/Ho_Chi_Minh`, sân, khách, liên hệ cần thiết, tổng tiền/tiền tệ, trạng thái thanh toán, trạng thái booking và thao tác xem chi tiết. Hỗ trợ sắp xếp mặc định booking mới nhất hoặc giờ sân gần nhất.
4. **Panel chi tiết:** thông tin đặt sân và khách vừa đủ cho vận hành; lịch sử trạng thái; danh sách lần thanh toán với phương thức, số tiền, mã giao dịch đã che/giới hạn, thời điểm và nguồn xác minh; thời hạn thanh toán còn hiệu lực nếu là booking pending.
5. **Thao tác theo trạng thái:** hủy booking theo chính sách được duyệt; xử lý xác nhận thanh toán hoặc đối soát giao dịch theo cơ chế bên dưới. Các hành động phải có hộp xác nhận, thông báo kết quả/lỗi và vô hiệu hóa khi trạng thái đã thay đổi.
6. **Xuất Excel:** cân nhắc sau khi danh sách, lọc và quyền xem dữ liệu cá nhân ổn định; xuất theo đúng bộ lọc và giới hạn quyền.

Nhãn trạng thái cần dễ phân biệt và luôn có chữ, không chỉ dựa vào màu: booking `PENDING_PAYMENT`, `CONFIRMED`, `EXPIRED`, `CANCELLED`, `COMPLETED`; payment `PENDING`, `PAID`, `FAILED`, `EXPIRED`, `REFUND_PENDING`, `REFUNDED`.

## Luồng nghiệp vụ chuẩn

1. Khách chọn slot hợp lệ; backend khóa dòng sân, kiểm tra lại giờ hoạt động, closure và booking chồng lấn trong transaction.
2. Backend chuẩn bị yêu cầu thanh toán/QR qua adapter của provider đã chọn. Chỉ sau khi tạo booking và dữ liệu thanh toán cần thiết thành công, ghi `PENDING_PAYMENT` cùng `payment_deadline_at = thời điểm tạo + đúng 5 phút`.
3. Availability chỉ coi booking pending còn hiệu lực khi deadline lớn hơn thời gian hiện tại. Job hết hạn chuyển pending quá hạn sang `EXPIRED`; kiểm tra availability vẫn phải tự bỏ qua pending trễ kể cả job chạy chậm.
4. Provider webhook được xác thực chữ ký và kiểm tra provider, mã đơn/giao dịch, số tiền, tiền tệ, trạng thái. Xử lý idempotent trong transaction; chỉ khi khoản thu hợp lệ mới chuyển payment sang `PAID` và booking sang `CONFIRMED`, ghi `booking_status_history`, sau commit mới gửi thông báo.
5. Nếu webhook báo trả tiền sau khi booking hết hạn hoặc sân đã được cấp cho booking khác, không xác nhận chồng lấn. Đưa vào hàng chờ đối soát; xử lý hoàn tiền/thủ công theo chính sách được chủ dự án duyệt.
6. Lịch sân tiếp tục dùng chung nguồn trạng thái với danh sách booking: booking đã thanh toán/xác nhận hiện là slot bận; booking pending chỉ bận trước deadline; booking hủy/hết hạn không bận.

### Đối soát thủ công khi thiếu/sai nội dung chuyển khoản

- Nội dung chuyển khoản chứa booking code/key là cách ghép tự động ưu tiên, nhưng khách có thể bỏ trống hoặc nhập sai. Không được để việc thiếu nội dung khiến giao dịch biến mất khỏi quy trình xử lý.
- Nếu ngân hàng/provider có API hoặc webhook giao dịch vào, ghi nhận mọi giao dịch nhận được. Nếu chưa có nguồn tự động, nhân viên nhập giao dịch từ sao kê/app ngân hàng với mã giao dịch ngân hàng duy nhất, số tiền, tiền tệ, thời điểm và nội dung chuyển khoản nguyên bản. Không yêu cầu khách gửi thông tin đăng nhập hoặc dữ liệu thẻ.
- Tạo hàng đợi `Cần đối soát` cho giao dịch không ghép được, thiếu trường, lệch số tiền hoặc có nhiều booking tiềm năng. Có thể gợi ý booking pending dựa trên key, số tiền, thời điểm, khách/sân, nhưng chỉ nhân viên có quyền mới chọn booking và xác nhận.
- **Không xác nhận chỉ theo số tiền hoặc thời gian gần nhau.** Nhân viên phải đối chiếu giao dịch thực tế trong nguồn ngân hàng và chọn đúng booking; lưu transaction reference duy nhất, số tiền/tiền tệ, thời điểm ngân hàng, người xử lý, thời điểm xử lý và lý do/ghi chú. Có thể yêu cầu đính kèm chứng từ nếu chính sách vận hành cần, nhưng cần hạn chế quyền truy cập và thời gian lưu.
- Khi giao dịch đã được xác minh và booking còn `PENDING_PAYMENT`, xử lý transactionally: khóa theo chiến lược thống nhất, kiểm tra chưa bị thanh toán/gắn giao dịch khác, kiểm tra số tiền/tiền tệ và slot vẫn có thể xác nhận; tạo payment `PAID`, chuyển booking `CONFIRMED`, ghi history/audit. Mã giao dịch ngân hàng phải unique để không dùng cho hai booking.
- Nếu giao dịch thực nhận đúng nhưng booking đã `EXPIRED` hoặc slot đã được người khác xác nhận, ghi nhận khoản tiền vào ca đối soát ngoại lệ; **không** chuyển booking sang confirmed và không lấy slot từ khách khác. Chuyển xử lý theo chính sách hoàn tiền/điều phối được duyệt.
- Nếu giao dịch mơ hồ, thiếu tiền, sai tiền tệ, trùng mã hoặc không chứng minh được đã vào tài khoản BALLBURN, giữ ở trạng thái cần xử lý; không xác nhận và không cho phép nhân viên bỏ qua kiểm tra bằng một click không có lý do.

## Xử lý thao tác “Đánh dấu đã thanh toán” trên mockup

Không dùng thao tác này để tự xác nhận khoản thanh toán QR/online, vì admin click hoặc trình duyệt redirect không phải bằng chứng provider đã nhận tiền.

Khi chưa có provider, nên triển khai theo quy trình đối soát được chủ dự án duyệt. Cần tách rõ “ghi nhận giao dịch ngân hàng” và “xác nhận đã đối soát”: chỉ bước hai mới có thể xác nhận booking.

- **Thanh toán online/QR tự động:** webhook hợp lệ là nguồn xác nhận trong luồng thường. Giao dịch thiếu key, webhook lỗi hoặc không ghép được cần chuyển sang hàng đợi đối soát; admin không xác nhận dựa trên ảnh chụp màn hình hay redirect phía khách.
- **Có nhận chuyển khoản/tiền tại quầy được đối soát:** cho phép action riêng để nhân viên ghi nhận giao dịch thật, gồm phương thức, mã tham chiếu duy nhất, số tiền/tiền tệ, thời điểm và ghi chú cần thiết. Ghi người thao tác và audit; không giả lập provider/webhook. Với chuyển khoản, xác nhận trên nguồn ngân hàng trước khi gắn vào đơn.

## Kiến trúc đề xuất

- Route trong nhóm `auth` + `can:access-admin`: `GET /admin/bookings`, `GET /admin/bookings/{booking}`, cùng action POST riêng cho hủy hoặc ghi nhận thu thủ công nếu được duyệt.
- `AdminBookingController`: nhận Form Request, authorize policy, gọi service và trả Inertia/redirect; không tự dựng query.
- `IndexAdminBookingsRequest` và các Form Request action: validate khoảng ngày, filter, sort, pagination, lý do hủy/tham chiếu thanh toán.
- `BookingRepository`/`PaymentRepository`: nơi duy nhất tạo truy vấn lọc, thống kê, chi tiết và ghi dữ liệu. Tách repository thanh toán nếu đủ nghiệp vụ, không nhồi query vào controller/service.
- `AdminBookingManagementService`: điều phối chi tiết, hủy và thu thủ công; kiểm tra chuyển trạng thái hợp lệ; dùng transaction và khóa theo thứ tự ổn định (sân rồi booking/payment nếu cần), kiểm tra lại slot trước khi chuyển trạng thái.
- Bổ sung model `Payment` và quan hệ booking–payments; model/quan hệ webhook event khi triển khai tích hợp provider.
- Bổ sung workflow/model/repository cho giao dịch chờ đối soát nếu nguồn ngân hàng/provider không đảm bảo map booking bằng key; lưu transaction reference duy nhất và kết quả ghép thủ công. Có thể dùng `payments` cộng bảng reconciliation riêng thay vì thêm trạng thái không phù hợp vào booking.
- `PaymentProvider` interface và adapter cụ thể chỉ sau khi provider được chọn. Bí mật ở config/env, không lưu/log payload nhạy cảm.
- Policy action cụ thể cho xem, hủy, ghi nhận thu và xuất dữ liệu; gate admin hiện tại chỉ hạn chế vào khu vực, chưa phân quyền theo hành động.
- Ghi `booking_status_history` cho mọi chuyển trạng thái; dùng `audit_logs` cho thao tác nhạy cảm nếu schema hiện hữu hỗ trợ. Notifications chỉ dispatch sau commit.

## Các giai đoạn triển khai

### Giai đoạn 0 — Chốt quy tắc thanh toán

- Chọn provider và cơ chế QR/checkout.
- Xác nhận có nhận tiền thủ công tại quầy/chuyển khoản hay không; ai được quyền ghi nhận.
- Xác nhận chính sách hủy/hoàn tiền và quy trình xử lý tiền về muộn sau khi slot đã được đặt lại.
- Chốt cách tính thống kê doanh thu và các khoảng ngày mặc định.

### Giai đoạn 1 — Sửa vòng đời booking và tích hợp thanh toán

- Đổi booking khách sang `PENDING_PAYMENT`, tạo `Payment`/QR theo adapter, thiết lập deadline đúng 5 phút.
- Cập nhật availability và repository kiểm tra overlap nhất quán với deadline; thêm job hết hạn.
- Tạo webhook endpoint bảo vệ chữ ký, kiểm tra dữ liệu giao dịch, idempotency, xử lý late payment và thông báo sau commit.
- Giữ booking do admin tạo hộ ở `CONFIRMED` theo hành vi đã được duyệt; thể hiện rõ nguồn booking và trạng thái thanh toán tương ứng.

### Giai đoạn 2 — Danh sách, bộ lọc và thống kê admin

- Thêm truy vấn repository có phân trang, tìm kiếm và filter ngày/sân/status/payment.
- Thêm thống kê tổng hợp cùng điều kiện lọc và timezone venue.
- Tạo `resources/js/pages/admin/bookings/Index.vue`, menu “Quản lý đặt sân”, panel chi tiết thích ứng màn hình nhỏ.
- Dùng token màu/ngữ nghĩa hiện có, tối thiểu 14px cho body/label/button và theme light/dark/system.

### Giai đoạn 3 — Chi tiết, thao tác và audit

- Thêm trang/panel chi tiết, danh sách payments và lịch sử trạng thái.
- Thêm hủy booking theo chính sách, ghi lý do và lịch sử; nếu được duyệt, thêm thu thủ công có phân quyền và audit.
- Thêm xác nhận trước thao tác, xử lý conflict/stale data và hiển thị thông báo rõ ràng.
- Thêm hàng đợi giao dịch không ghép được/thiếu key, màn hình đối chiếu giao dịch với booking ứng viên và ghi nhận quyết định có audit.

### Giai đoạn 4 — Xuất dữ liệu và hoàn thiện vận hành

- Chỉ thêm xuất Excel sau khi được yêu cầu/chốt cột; giới hạn theo filter và quyền.
- Rà soát log/PII, cảnh báo webhook lỗi, hàng chờ đối soát, tài liệu vận hành và cấu hình triển khai.

## Kiểm thử cần có khi triển khai

- Khách tạo booking pending và payment deadline chính xác 5 phút; lỗi chuẩn bị QR không để lại booking treo.
- Pending chỉ chiếm slot trước deadline; slot được mở dù expiry job chưa chạy.
- Webhook hợp lệ xác nhận một lần; lặp event không nhân đôi payment, history hoặc email.
- Sai chữ ký, provider, transaction/order ID, amount hoặc currency không xác nhận booking.
- Late payment không thể chiếm slot đã được booking khác xác nhận.
- Giao dịch thiếu nội dung chuyển khoản xuất hiện trong hàng đợi đối soát; không tự ghép chỉ theo số tiền/thời gian; cùng một mã giao dịch không thể gắn cho hai payment.
- Đối soát thủ công hợp lệ xác nhận booking đang pending đúng một lần và ghi reviewer/reference/history; khoản tiền đến muộn không lấy slot đã được booking khác giữ.
- Danh sách/filter/count/pagination trả đúng dữ liệu; filter giữ nguyên khi điều hướng.
- Staff/admin không đủ quyền không thể hủy/ghi nhận thu; đổi trạng thái hợp lệ có history/audit, đổi trạng thái không hợp lệ bị từ chối.
- Hủy hoặc payment đã xác minh phản ánh đồng nhất ở list, detail, lịch sân và trang trạng thái guest.

## Quyết định cần chủ dự án xác nhận

1. Provider thanh toán QR/online nào sẽ được tích hợp?
2. Ngân hàng/provider có nguồn API/webhook giao dịch vào nào để nhập khoản tiền thực nhận, hay giai đoạn đầu nhân viên sẽ nhập thủ công từ sao kê/app ngân hàng?
3. Có cho admin ghi nhận tiền mặt/chuyển khoản thủ công không? Nếu có, vai trò nào được thao tác và cần lưu bằng chứng gì?
4. Hủy booking và hoàn tiền theo điều kiện nào; xử lý khoản tiền về sau khi booking hết hạn ra sao?
5. “Doanh thu hôm nay” tính theo ngày thu tiền hay ngày sử dụng sân? Khoảng ngày mặc định của danh sách là ngày đặt sân hay ngày tạo booking?

## Lưu ý bàn giao

Kế hoạch này dựa trên mockup và hiện trạng repository. Bắt đầu triển khai danh sách admin, chi tiết booking và luồng hàng đợi đối soát thủ công trước; không bật xác nhận QR tự động cho đến khi có provider và webhook đã xác minh. Vòng đời tạo booking khách cần chuyển sang pending/payment-hold đồng bộ khi adapter thanh toán được chọn và triển khai.

### Đã triển khai đợt đầu

- Thêm `/admin/bookings` với tìm kiếm, lọc theo sân/ngày/trạng thái booking/trạng thái payment, thống kê và phân trang; thêm trang chi tiết booking với payments và status history.
- Thêm hàng đợi giao dịch ngân hàng: nhân viên ghi giao dịch đã kiểm tra từ sao kê/app ngân hàng bằng mã tham chiếu duy nhất; giao dịch không có nội dung chuyển khoản vẫn nhập được.
- Ghép giao dịch đúng số tiền/tiền tệ sẽ ghi payment và audit. Booking pending còn hạn được xác nhận nếu slot không bị booking khác chiếm. Tiền về muộn được lưu nhưng booking không được xác nhận lại; lệch tiền/tiền tệ được giữ ở trạng thái cần xem xét.
- Phần chưa có: tạo QR qua provider, webhook xác minh tự động, giải quyết refund/ngoại lệ và hủy booking theo chính sách. Chưa có thao tác admin nào tự đánh dấu thanh toán online chỉ bằng click hoặc ảnh chụp.
