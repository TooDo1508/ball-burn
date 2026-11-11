# Kế hoạch triển khai Redis cho BALLBURN

**Trạng thái:** Đã triển khai nền tảng Redis cho môi trường Compose local; production/staging chưa cấu hình.
**Mục tiêu:** Giảm truy vấn lặp vào MySQL, giảm tải ghi session/queue và giữ tốc độ ổn định khi tăng số worker hoặc nhiều máy ứng dụng.

## 1. Hiện trạng

- Laravel đã có cấu hình Redis trong `config/database.php`, `config/cache.php`, `config/queue.php` và `config/session.php`; PHP image hiện cài `phpredis` 6.2.0.
- `.env.example` và `.env` local dùng `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`.
- `compose.yaml` có hai Redis service nội bộ: `redis-cache` cho cache với LRU và `redis-queue` cho session/queue với AOF, noeviction và volume riêng.
- Worker đọc queue `payments,default`. Job OCR biên lai chạy queue `payments`; email liên hệ được dispatch sau commit.
- Middleware giới hạn tần suất đang áp dụng cho holds, hướng dẫn thanh toán, upload biên lai, liên hệ, upload ảnh tin tức và một số thao tác cài đặt. Fortify cũng giới hạn login và xác thực hai lớp.
- `/booking/data` mỗi lần đọc đều lấy lịch đặt còn hiệu lực, holds chưa hết hạn và lịch đóng sân từ MySQL. Giá được tính theo rule và ngày.
- Cache Redis đang áp dụng cho nội dung homepage, contact settings công khai, danh mục sân công khai và blog; các cache có TTL và invalidation khi admin cập nhật.

`/booking/data` vẫn đọc dữ liệu động từ MySQL; cache không tham gia quyết định tạo booking. Redis local không có cổng host công khai. Production còn cần managed Redis/private network, TLS/ACL, quan sát metrics, kiểm thử khôi phục queue/session và rollout theo traffic.

## Tiến độ triển khai

- Hoàn tất: Redis cache/queue riêng trong Compose, extension PHP, cấu hình Redis làm cache/session/queue backend và queue `after_commit=true`.
- Hoàn tất: cache array-only cho dữ liệu public; giữ `serializable_classes=false`, không cache model/paginator Eloquent trực tiếp.
- Hoàn tất: invalidation blog khi tạo/sửa/xóa bài, invalidation catalog khi sửa sân, invalidation settings sau cập nhật.
- Đã xác nhận: cả hai Redis healthcheck trả `PONG`; Laravel runtime nhận `cache.default`, `session.driver` và `queue.default` là `redis`; `/up` trả HTTP 200.
- Đã smoke request hai lượt: `/`, `/blog`, `/api/blog/posts`, `/api/site/contact-info`, `/courts` và `/booking/data` đều trả HTTP 200.
- Chưa hoàn tất: benchmark trước/sau, kiểm thử failover/HA, xác nhận queue/session qua restart và cấu hình staging/production.

## 2. Nơi nên áp dụng

| Ưu tiên | Vùng | Cách dùng Redis | Lợi ích và giới hạn |
|---|---|---|---|
| P0 | Cache ứng dụng | Chuyển cache store sang Redis; cache ngắn hạn dữ liệu công khai ít thay đổi như homepage, contact info, metadata sân/giờ hoạt động và rule giá | Giảm các lần đọc lặp vào MySQL. Cần invalidation khi admin cập nhật nội dung, giờ sân, trạng thái sân hoặc giá. |
| P0 | Rate limiting | Đưa Laravel cache/rate limiter sang Redis dùng chung giữa các app instance | Giới hạn login, booking, upload và contact nhất quán khi có nhiều máy. Giữ nguyên giới hạn hiện có trước khi điều chỉnh. |
| P1 | Queue | Chuyển queue database sang Redis, giữ hai queue `payments` và `default` | Job OCR/email không cạnh tranh ghi trên bảng `jobs`; dễ tăng worker độc lập. Cần giám sát độ trễ, retry và job lỗi. |
| P1 | Session | Chuyển session sang Redis khi chạy nhiều app instance hoặc khi đo đạc cho thấy bảng session là tải đáng kể | Không cần sticky session ở load balancer; đăng nhập tiếp tục được khi request sang instance khác. Cần giữ lifetime, cookie và chính sách bảo mật hiện tại. |
| P2 | Cache tổng hợp admin | Chỉ cân nhắc cache dashboard counts/revenue hoặc các thống kê tốn kém nếu trace cho thấy truy vấn là nút thắt | TTL ngắn và xóa cache sau thay đổi đơn hàng; dữ liệu dashboard có thể trễ trong khoảng TTL. |

### Ranh giới dữ liệu đặt sân

- Không cache toàn bộ trạng thái `available/booked/closed` của `/booking/data` ở pha đầu. Dữ liệu này phụ thuộc booking, hold, closure, deadline và token hold của khách; cache chung có thể hiển thị sai cho khách.
- Có thể cache riêng dữ liệu tương đối tĩnh như court catalog, operating hours và quy tắc giá với invalidation rõ ràng. Không lấy giá trong cache làm căn cứ duy nhất để tạo đơn.
- MySQL vẫn là nguồn sự thật cho booking, booking holds, closures, giá tại thời điểm xác nhận, payment, idempotency và deadline.
- Mọi thao tác ghi lịch vẫn phải lock hàng sân trong transaction và kiểm tra xung đột lại trên MySQL. Redis lock không thay thế quy tắc này.

## 3. Kiến trúc đề xuất

### Môi trường phát triển

1. Thêm service `redis` vào Compose, chỉ mở cổng cho mạng nội bộ Compose, có healthcheck và phiên bản image cố định.
2. Cho `app` và `queue-worker` chờ Redis healthy khi bật backend Redis.
3. Có thể dùng một Redis service cho dev; cache và queue dùng prefix/database index riêng để dễ kiểm tra. Không ghi đè dữ liệu MySQL hiện có.
4. Cấu hình `.env.example` với các biến cần thiết, không thêm password thật vào repository.

### Môi trường production

- Ưu tiên managed Redis/private network, bật TLS và ACL/password theo nền tảng triển khai; không public cổng 6379.
- Khuyến nghị tách cache và queue thành hai Redis instance hoặc hai dịch vụ riêng. Database index khác nhau trên cùng instance không cô lập bộ nhớ hay eviction policy.
- Queue cần durability phù hợp với yêu cầu job: bật persistence/HA theo nền tảng và không dùng eviction policy có thể xóa job. Cache có thể dùng eviction; tránh để cache eviction xóa queue data.
- Giới hạn kết nối, memory, timeout và retry; phát cảnh báo trước ngưỡng đầy. Không để app khởi động âm thầm trong trạng thái Redis không sẵn sàng nếu backend bắt buộc.

### Biến cấu hình dự kiến

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1
```

Đây là cấu hình mẫu cho Compose dev, chưa áp dụng production. Môi trường thực tế cần dùng hostname, TLS, credentials, database/index và prefix riêng theo hạ tầng. Nếu tách instance, cấu hình connection Redis cho cache và queue trỏ đúng host của từng dịch vụ.

## 4. Trình tự triển khai

### Pha 0 — Đo baseline và xác nhận hạ tầng

- Ghi lại p50/p95/p99 thời gian phản hồi cho `/`, `/blog`, `/booking`, `/booking/data`, `/booking/holds`, `/admin` và `/admin/users`.
- Ghi số query/request, query chậm, kết nối MySQL, CPU/memory, thời gian chờ queue, throughput OCR và email.
- Xác định số app instance/worker hiện tại, mục tiêu tải, giới hạn RAM và yêu cầu khôi phục job/session.
- Xác minh PHP runtime có extension `phpredis`; kiểm tra kết nối Redis bằng healthcheck và một thao tác cache/queue trong staging.

**Cổng đi tiếp:** Có baseline và Redis staging sẵn sàng; secrets chỉ nằm trong secret manager hoặc cấu hình deploy.

### Pha 1 — Hạ tầng và cache dữ liệu công khai

- Thêm Redis dev/CI/staging và cấu hình healthcheck, timeout, prefix theo môi trường.
- Thêm lớp cache qua service/repository, tránh rải `Cache::remember` vào controllers.
- Cache homepage/contact info, blog list/detail công khai và metadata sân/giá với TTL ban đầu ngắn (ví dụ 1–5 phút).
- Xóa hoặc version key trong cùng transaction/after commit khi admin sửa nội dung, publish bài, cập nhật thông tin liên hệ, sân, operating hours hoặc price rules.
- Với thay đổi rule sân, kiểm tra kỹ cache key theo ngày/loại ngày/effective date; tránh để giá cũ dùng tạo đơn.
- Chạy shadow comparison ở staging: so payload cache với truy vấn gốc; đo hit ratio và p95 trước khi bật rộng.

**Cổng đi tiếp:** Không sai lệch nội dung/giá sau thao tác admin; cache hit ratio và p95 có cải thiện đo được.

### Pha 2 — Rate limiter dùng chung

- Đảm bảo Laravel rate limiter dùng Redis khi `CACHE_STORE=redis`.
- Giữ key hiện tại (user/IP/email theo từng limiter) và giữ ngưỡng giới hạn đang dùng.
- Kiểm tra giới hạn qua nhiều app instance, hết hạn counter đúng, lỗi trả HTTP như trước.
- Quyết định fallback khi Redis mất kết nối. Fallback database có thể tăng tải MySQL; không fallback sang array ở production vì mỗi instance sẽ có giới hạn riêng.

**Cổng đi tiếp:** Thử nghiệm multi-instance xác nhận không thể vượt ngưỡng bằng cách đổi instance; có cảnh báo khi limiter fallback hoặc Redis lỗi.

### Pha 3 — Queue Redis

- Chuyển `QUEUE_CONNECTION` sang Redis trên staging, giữ queue names `payments` và `default` cùng lệnh worker tiêu thụ đúng thứ tự ưu tiên.
- Đặt `after_commit=true` cho Redis queue connection, hoặc giữ `afterCommit()` rõ ràng tại các dispatch. Kiểm tra `retry_after` lớn hơn worker timeout để tránh job chạy trùng khi worker chậm.
- Xác nhận idempotency của xử lý receipt, retry/failure lifecycle, và email contact chỉ được gửi sau transaction commit.
- Chạy worker riêng cho `payments` và `default` khi tải OCR có thể làm email chậm; đo trước khi đặt concurrency.
- Theo dõi queue depth, oldest job age, retry/failure rate và thời gian OCR.

**Cổng đi tiếp:** Job không mất khi restart theo durability policy; không phát sinh gửi trùng hoặc xử lý OCR sai trạng thái; backlog có cảnh báo.

### Pha 4 — Session Redis

- Chỉ chuyển production sau khi xác nhận có nhiều app instance hoặc giảm tải session DB có lợi đáng kể.
- Xác nhận login, logout, password confirmation, 2FA, CSRF, flash messages và session invalidation khi tài khoản bị khóa/vô hiệu hóa.
- Giữ cookie secure/HTTP-only/same-site, encryption và lifetime như hiện tại; session Redis có TTL không ngắn hơn chính sách ứng dụng.
- Kiểm thử rolling deploy và mất một app instance mà người dùng không bị đăng xuất ngoài ý muốn.

**Cổng đi tiếp:** Kiểm thử auth/2FA đa instance đạt; session không bị lẫn giữa môi trường.

### Pha 5 — Tối ưu theo số đo

- Dùng slow query/APM để chọn dashboard aggregate hoặc dữ liệu đọc nhiều khác; không cache danh sách có PII như user directory nếu chưa có yêu cầu rõ.
- Cân nhắc cache stampede protection (`Cache::flexible`, lock cache) cho key tải cao.
- Chỉ dùng distributed lock cho tác vụ cần chống chạy đồng thời ở nhiều worker, không thay transaction/row lock của booking.

## 5. Kiểm thử và tiêu chí nghiệm thu

- PHPUnit/feature tests chạy trên MySQL-compatible test database riêng; không chạy `migrate:fresh` hay `RefreshDatabase` trên database phát triển.
- Test cache hit/miss, hết TTL, invalidation sau admin update, đổi effective date và fallback khi key thiếu.
- Test xác nhận booking submit vẫn kiểm tra lại trạng thái trong transaction, kể cả khi cache availability chưa cập nhật.
- Test rate limiter qua hai app instance hoặc hai worker dùng cùng Redis.
- Test queue sau commit, retry, timeout, worker restart, job lỗi và tiến trình OCR/email.
- Test session đăng nhập/2FA/khóa tài khoản trên nhiều instance.
- Đặt tiêu chí theo baseline: p95 giảm ở endpoint đã cache, MySQL reads/writes giảm, queue oldest-job age không tăng, không tăng booking conflict hoặc dữ liệu giá sai.
- Theo dõi Redis memory, hit/miss, evictions, latency, connections, rejected commands, queue depth và lỗi kết nối; cảnh báo theo ngưỡng vận hành.

## 6. Rollout, rollback và vận hành

- Bật từng backend theo môi trường bằng biến cấu hình; staging trước, production theo traffic nhỏ rồi tăng dần.
- Rollback cache: chuyển `CACHE_STORE` về database và xóa cache Redis; cache mất chỉ làm tăng truy vấn, không mất dữ liệu nghiệp vụ.
- Rollback queue: dừng dispatch mới vào Redis, để worker tiêu thụ hết queue cũ trước khi đổi backend. Không đổi qua lại khi còn job pending nếu chưa có quy trình drain/di chuyển.
- Rollback session: dừng nhận traffic ở backend cũ, chấp nhận người dùng phải đăng nhập lại nếu session store không được đồng bộ; thông báo trước nếu cần.
- Sau đổi env cần restart/reload app và worker, clear config cache theo quy trình deploy, xác nhận healthcheck và smoke test.
- Không chạy Redis `FLUSHALL`/`FLUSHDB` trên môi trường dùng chung; chỉ xóa prefix/key được kiểm soát.

## 7. Các việc ngoài phạm vi

- Không chuyển dữ liệu nghiệp vụ khỏi MySQL.
- Không cache booking availability hoặc thanh toán như nguồn sự thật.
- Không đưa ảnh biên lai vào Redis; file tiếp tục ở private storage, job chỉ mang receipt ID.
- Không bổ sung Redis-based booking lock thay cho khóa hàng sân, kiểm tra conflict và transaction MySQL.
- Không khẳng định mức tăng tốc trước khi có số liệu baseline và staging.
