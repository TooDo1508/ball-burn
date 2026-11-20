# Kế hoạch chuẩn hóa SEO cho BALLBURN

## Mục tiêu

Giúp công cụ tìm kiếm và hệ thống trả lời AI khám phá đúng các trang công khai của BALLBURN, hiểu nội dung từng trang, hiển thị thông tin chia sẻ chính xác và tránh lập chỉ mục trang quản trị hoặc dữ liệu cá nhân.

## Hiện trạng đã rà soát

- Tại lần kiểm tra production ngày 2026-11-20, `robots.txt` cho phép mọi crawler nhưng chưa khai báo sitemap; `/sitemap.xml` trả 404.
- Trang `/` dùng Inertia (`resources/js/pages/Home.vue`); production HTML ban đầu chỉ có `<title>BALLBURN</title>`, thiếu description/canonical/schema trước khi JavaScript chạy.
- Domain chuẩn đã được chỉ định là `https://ballburn.site`.
- `/about`, `/blog`, `/blog/{slug}`, `/booking`, `/contact` đã truyền metadata server-rendered qua `resources/views/welcome.blade.php`; một số trang có canonical và JSON-LD.
- Blog bài viết đã có NewsArticle JSON-LD; nội dung blog chỉ lấy bài đã xuất bản qua `PublicBlogService`.
- Sitemap cần cân nhắc các trang công khai có nội dung hữu ích: trang chủ, giới thiệu, blog, bài viết đã xuất bản, đặt sân, liên hệ; chỉ thêm trang sân nếu có route HTML công khai, crawl được và có canonical riêng. Các endpoint `/courts` hiện được khai báo trong `routes/api.php`, nên cần xác nhận chúng trả trang HTML trước khi cân nhắc.
- Tuyệt đối không đưa `/admin`, trang đăng nhập, endpoint API, trang trạng thái booking có token, URL truy vấn nhạy cảm hoặc trang không công khai vào sitemap.

## Phạm vi và thứ tự triển khai

### 1. Chốt host chuẩn và tín hiệu index

- Xác nhận domain sản xuất chính thức và biến `APP_URL` dùng trên production.
- Chọn duy nhất một host chuẩn (`https` và có hoặc không có `www`); chuyển hướng các biến thể HTTP/host về URL chuẩn bằng chuyển hướng 301 ở proxy/CDN hoặc web server.
- Chuẩn hóa canonical thành URL tuyệt đối, cùng host chuẩn, không kèm tham số tracking.
- Tạo Google Search Console Domain property (nếu chưa có), xác minh quyền sở hữu và xác nhận sitemap được gửi từ đúng domain.
- Dùng toán tử `site:domain` như một cách kiểm tra sơ bộ kết quả đã được lập chỉ mục; đây không phải cấu hình SEO và kết quả không thay thế báo cáo URL Inspection/Search Console.

### 2. Hoàn thiện `robots.txt`

- Giữ cho các trang công khai có nội dung hữu ích được crawl.
- Khai báo `Sitemap: https://<domain-chuan>/sitemap.xml`.
- Chặn khu vực quản trị và URL đăng nhập nếu phù hợp, nhưng không dựa vào `robots.txt` để bảo vệ dữ liệu riêng tư; quyền truy cập vẫn phải do xác thực/authorization kiểm soát.
- Không chặn CSS, JavaScript, ảnh hoặc tài nguyên cần thiết để render nội dung công khai.
- Không đặt `noindex` chỉ trong `robots.txt`; crawler cần truy cập trang để đọc meta robots hoặc header `X-Robots-Tag`.

### 3. Tạo sitemap XML động

- Thêm endpoint công khai `/sitemap.xml`, trả XML hợp lệ với `Content-Type: application/xml`.
- Bao gồm URL canonical của các trang tĩnh công khai: `/`, `/about`, `/blog`, `/booking`, `/contact`.
- Thêm URL bài blog đã xuất bản. Chỉ thêm trang sân nếu kiểm tra xác nhận URL đó trả HTML công khai crawl được và có canonical; không đưa endpoint JSON trong `routes/api.php` vào sitemap. Lấy dữ liệu từ service/repository hiện hành, không tạo URL cho bản nháp hoặc nội dung bị ẩn.
- Không đưa API, query pagination/filter, admin, login, booking status token, URL preview hoặc URL trùng canonical vào sitemap.
- Dùng `<lastmod>` chỉ khi ngày sửa nội dung đáng tin cậy; không phát sinh `changefreq`/`priority` máy móc.
- Đảm bảo sitemap không cache lâu sau khi nội dung được đăng, cập nhật hoặc gỡ; cân nhắc cache ngắn nếu truy vấn dữ liệu động gây tải.

### 4. Chuẩn hóa metadata và URL canonical

- Đảm bảo mỗi trang indexable có một `<title>`, một meta description, canonical tuyệt đối duy nhất và đúng mã ngôn ngữ `vi`/`vi-VN`.
- Đồng bộ `og:url` với canonical; bổ sung `og:site_name`, `og:locale` và alt text cho ảnh chia sẻ nếu hỗ trợ.
- Kiểm tra trang chủ đang dùng canonical `/` tương đối và chuyển thành URL tuyệt đối theo host chuẩn.
- Kiểm tra trang đặt sân, giới thiệu, liên hệ, danh sách bài viết, bài viết, trang sân; không để trang thiếu metadata hoặc dùng metadata mặc định không phù hợp.
- Dùng nội dung title/description riêng, mô tả đúng nội dung trang; tránh nhồi từ khóa và tránh trùng lặp giữa các trang.
- Chuẩn hóa redirect cho slash cuối, chữ hoa/thường và tham số tracking theo một quy tắc nhất quán.

### 5. Structured data và nội dung chia sẻ

- Rà soát JSON-LD hiện có theo loại nội dung thật: `SportsActivityLocation`/`LocalBusiness` phù hợp cho địa điểm, `Organization`, `Blog`, `Article`/`NewsArticle`, `ContactPage`.
- Bổ sung các thuộc tính địa chỉ, điện thoại, giờ hoạt động, tọa độ, URL và ảnh chỉ khi dữ liệu nguồn chính xác và được phép công khai.
- Đảm bảo dữ liệu có cấu trúc khớp nội dung nhìn thấy trên trang; không thêm review/rating hoặc thông tin không có thật.
- Xác thực JSON-LD bằng Rich Results Test và Schema Markup Validator; sửa lỗi escape/JSON và URL ảnh.

### 6. Nội dung và SEO kỹ thuật cơ bản

- Kiểm tra mỗi trang có một H1 rõ ràng, heading theo thứ bậc, liên kết nội bộ crawl được và văn bản thay thế ảnh có ý nghĩa.
- Duy trì HTML server-rendered có nội dung chính cho crawler; kiểm tra HTML trả về trực tiếp cho trang chủ, bài viết và trang sân, không chỉ nội dung sau JavaScript.
- Kiểm tra ảnh chia sẻ có thể truy cập công khai, kích thước phù hợp và URL HTTPS.
- Xác định mã phản hồi chuẩn: trang hợp lệ trả 200; URL không tồn tại trả 404/410; redirect dùng 301/308 khi phù hợp; lỗi không trả trang 200 giả.
- Không index trang quản trị, trang đăng nhập/reset mật khẩu, preview, trạng thái booking cá nhân và endpoint dữ liệu JSON. Dùng `noindex`/header phù hợp trên trang có thể truy cập công khai nhưng không nên index.

### 7. Đo lường và theo dõi sau phát hành

- Đăng ký/kiểm tra Search Console; gửi sitemap và theo dõi trạng thái sitemap, indexing, lỗi crawl, canonical do Google chọn và Core Web Vitals.
- Kiểm tra thủ công một số URL bằng URL Inspection; dùng `site:<domain>` để quan sát sơ bộ phạm vi kết quả, không xem đây là số liệu chính xác.
- Lưu lại baseline số URL hợp lệ, lỗi index, lượt click/impression trước và sau phát hành để đánh giá theo thời gian.
- Khi thêm/gỡ bài viết hoặc trang sân, xác nhận sitemap cập nhật và URL cũ được redirect hoặc trả 404/410 thích hợp.

## Tiêu chí hoàn thành

- `https://<domain-chuan>/robots.txt` trả 200, cho phép crawl nội dung công khai và công bố sitemap đúng host.
- `https://<domain-chuan>/sitemap.xml` trả XML hợp lệ, chỉ liệt kê URL canonical công khai và cập nhật theo nội dung đã xuất bản.
- Tất cả trang công khai trọng yếu có title, description, canonical tuyệt đối và social metadata phù hợp; không có canonical xung đột.
- Không URL admin, API, preview, booking token hoặc tham số lọc bị đưa vào sitemap.
- JSON-LD hợp lệ, đúng với dữ liệu hiển thị và không chứa thông tin giả định.
- Domain chuẩn được xác minh trong Search Console; sitemap đã gửi và các lỗi ban đầu được ghi nhận để theo dõi.

## Ngoài phạm vi của kế hoạch này

- Cam kết thứ hạng tìm kiếm hoặc thời điểm Google index.
- Tạo nội dung/bài blog SEO mới, nghiên cứu bộ từ khóa hoặc chạy quảng cáo.
- Thay đổi giao diện, quy trình booking hoặc nội dung marketing chưa được duyệt.
- Khai báo noindex/robots như một cơ chế bảo vệ dữ liệu riêng tư.

## Tình trạng triển khai trong repository

- [x] Thêm cấu hình URL canonical, mặc định dùng `https://ballburn.site` trong production và cho phép override bằng `SEO_CANONICAL_URL`.
- [x] Cập nhật `robots.txt` trỏ đến sitemap chuẩn.
- [x] Thêm `/sitemap.xml` động với các trang công khai và bài blog đã xuất bản; không thêm URL API, admin hoặc booking status.
- [x] Thêm `/llms.txt` với thông tin địa điểm, trang chính và bài viết đã xuất bản. Đây là một quy ước đang được đề xuất, không phải yêu cầu chính thức của Google hay bảo đảm được crawler AI sử dụng.
- [x] Xuất metadata và JSON-LD của trang chủ trong HTML ban đầu; thêm thông tin liên hệ, địa chỉ, tọa độ và hồ sơ mạng xã hội vào entity địa điểm.
- [x] Thêm `X-Robots-Tag: noindex` cho admin, API JSON, availability JSON và booking status.
- [ ] Chạy kiểm thử PHP bằng cấu hình MySQL hỗ trợ dự án, kiểm tra types/build frontend trên Node 22.12+ và kiểm tra đầu ra live sau khi triển khai.
- [ ] Gửi sitemap và xác minh URL trong Google Search Console; cần quyền truy cập Search Console của BALLBURN.
