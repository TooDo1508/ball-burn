# Kế hoạch quản lý sân BALLBURN

## Mục tiêu đợt triển khai

Xây dựng màn hình vận hành sân trong admin dựa trên `Dark-Themed Court Booking Dashboard.png`, phục vụ bốn tác vụ chính:

1. Tạo và cập nhật sân, giá theo giờ, trạng thái và lịch hoạt động hàng tuần.
2. Theo dõi booking theo ngày trên lưới 30 phút cho toàn bộ sân.
3. Khóa hoặc mở lịch nhanh cho bảo trì, ngày lễ và sự kiện riêng.
4. Click slot trống để admin tạo booking đã xác nhận thay khách hàng.

## Kiến trúc

- `CourtAdminController` và `CourtClosureController` chỉ xử lý HTTP, authorization và Inertia response/redirect.
- `AdminBookingController` gọi `AdminBookingService`; `BookingRepository` khóa dòng sân và kiểm tra trùng lịch lại trong transaction trước khi tạo booking.
- `CourtManagementService` xử lý quy tắc nghiệp vụ, chuyển đổi múi giờ, hình ảnh và dữ liệu lịch.
- `CourtRepository` là nơi duy nhất xây dựng Eloquent query cho sân, booking, giờ hoạt động và lịch khóa.
- `StoreCourtRequest` và `StoreCourtClosureRequest` kiểm tra dữ liệu phía server, gồm ranh giới 30 phút và giờ đóng sau giờ mở.
- `CourtPolicy` bảo vệ thao tác admin/staff bên cạnh middleware `can:access-admin`.

## Quy tắc đang áp dụng

- BALLBURN có 8 sân: sân 1–3 là sân trung tâm, sân 4–8 là sân thường.
- Giờ hoạt động đã xác nhận là 05:00–23:00 mỗi ngày; lịch sử dụng slot 30 phút.
- Giá được lưu theo hạng sân, ngày thường/cuối tuần và 5 khung giờ theo bảng giá hiệu lực từ 01/04/2026; giao diện hiển thị giá tương ứng mỗi slot 30 phút.
- Mỗi sân có tối đa một khoảng hoạt động trong một ngày, không hỗ trợ lịch qua đêm trong giai đoạn này.
- Khóa lịch chỉ được tạo trong giờ hoạt động và không được chồng lên booking còn hiệu lực hoặc lịch khóa khác.
- Booking `PENDING_PAYMENT` quá hạn không xuất hiện và không chiếm lịch.
- Ngày giờ đặt sân được diễn giải theo `VENUE_TIMEZONE=Asia/Ho_Chi_Minh`, sau đó lưu timestamp về UTC; `APP_TIMEZONE` vẫn là UTC.
- Booking do admin tạo được xác nhận ngay, tính tổng tiền phía server và ghi `booking_status_history` với nguồn `ADMIN`.
- Sân tạm ngưng vẫn tồn tại trong admin nhưng không được coi là có slot trống.

## Phạm vi đã triển khai

- Trang `/admin/courts` với thống kê, chọn ngày, lưới lịch, thông tin sân và trạng thái slot.
- Popup tạo/chỉnh sửa sân, giá, ảnh đại diện và giờ hoạt động.
- Popup khóa lịch nhanh và thao tác mở lại lịch.
- Popup đặt hộ khách từ slot trống, hỗ trợ chọn nhiều slot liên tiếp còn trống, nhập thông tin khách và xem tạm tính.
- Seeder idempotent cho đủ 8 sân, 56 bản ghi giờ hoạt động và 80 quy tắc giá.
- Menu admin, route, policy, validation, transaction và feature tests.

## Các giai đoạn kế tiếp

1. Hoàn thiện luồng booking công khai và quyết định chính sách nhiều slot cho khách tự đặt.
2. Thêm lịch sử thay đổi giá, khóa lịch và audit log đầy đủ cho thao tác nhạy cảm.
3. Bổ sung quy trình xử lý booking khi admin cần khóa một khoảng đã có khách đặt.
4. Xác nhận chính sách thanh toán/thu tiền cho booking do admin tạo hộ.

## Ghi chú tránh màn hình booking trắng

- Trang `/booking` được render ở client: `welcome.blade.php` để `#app` trống và `src/app.js` mount Vue Router. Vì vậy build thành công hoặc API trả `200` riêng lẻ chưa chứng minh giao diện đã mount.
- Lỗi trắng vừa tái hiện được trên trình duyệt: route booking tải bất đồng bộ nhưng `RouterView` không có nội dung dự phòng, nên `#app` trống trong lúc chờ component. Blade nay hiển thị trạng thái tải ban đầu; `src/App.vue` cũng hiển thị trạng thái tải trong khi route component chưa sẵn sàng. Không trì hoãn `mount()` bằng `router.isReady()`; việc đó khiến trang tiếp tục mắc ở trạng thái chờ trong kiểm tra runtime.
- Một lỗi khởi tạo trước đây do gọi `venueDate()` khi `venueTimezone` chưa được khởi tạo (Temporal Dead Zone). Khi thêm biến khởi tạo ở đầu `<script setup>`, phải khai báo giá trị phụ thuộc trước giá trị gọi hàm; `vue-tsc` và Vite build không phát hiện được lỗi thứ tự runtime này.
- `src/app.js` bắt lỗi khởi chạy/render của Vue và lỗi tải route. Khi lỗi xảy ra ở `/booking`, trang hiện thông báo có nút tải lại thay vì giữ màn hình trắng; nguyên nhân chi tiết được ghi trong console.
- Khi thay đổi luồng booking, kiểm tra trực tiếp `/booking` trong trình duyệt: xác nhận tiêu đề và nội dung đã render, không chỉ `#app`; xác nhận `/booking/data?date=YYYY-MM-DD` trả JSON thành công; kiểm tra console không có `ReferenceError`, lỗi render hoặc lỗi tải chunk. Chuyển sang chế độ nhiều sân, cuộn ma trận, chọn ô và gửi booking để kiểm tra lại cả luồng tương tác.
- Nếu trang chỉ hiện trạng thái tải, kiểm tra request dữ liệu và trạng thái `loading`; nếu thông báo lỗi khởi chạy xuất hiện, dùng lỗi console để sửa nguyên nhân rồi lặp lại kiểm tra trên trình duyệt trước khi bàn giao. Build/type-check là bước bổ sung, không thay cho kiểm tra runtime.
