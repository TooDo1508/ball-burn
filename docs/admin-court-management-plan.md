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
