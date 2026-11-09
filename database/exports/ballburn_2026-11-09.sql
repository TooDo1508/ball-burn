-- MySQL dump 10.13  Distrib 8.4.13, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.4.13

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `laravel`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `laravel` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `laravel`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_data` json DEFAULT NULL,
  `after_data` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `audit_logs_entity_type_entity_id_created_at_index` (`entity_type`,`entity_id`,`created_at`),
  KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'payment.status_updated','App\\Models\\Payment','5','{\"status\": \"PENDING\"}','{\"note\": null, \"status\": \"FAILED\"}',NULL,NULL,'2026-11-04 16:12:26.000'),(2,1,'bank_transfer.recorded','App\\Models\\BankTransferTransaction','1',NULL,'{\"amount\": \"400000.00\", \"currency\": \"VND\", \"transaction_at\": \"2026-11-04T16:22:00.000000Z\", \"bank_transaction_id\": \"1\"}','172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-11-04 16:23:15.000'),(3,1,'bank_transfer.late_payment','App\\Models\\BankTransferTransaction','1',NULL,'{\"status\": \"LATE_PAYMENT\", \"booking_id\": 7, \"payment_id\": 3}','172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-11-04 16:26:19.000'),(4,1,'booking.order_deleted','App\\Models\\BookingOrder','10','{\"status\": \"PENDING_PAYMENT\", \"booking_code\": \"BB-20261104-CAF1ZP\", \"total_amount\": \"400000.00\"}',NULL,NULL,NULL,'2026-11-05 08:05:23.000'),(5,1,'booking.order_deleted','App\\Models\\BookingOrder','13','{\"status\": \"PENDING_PAYMENT\", \"booking_code\": \"BB-20261104-OOKGCD\", \"total_amount\": \"200000.00\"}',NULL,NULL,NULL,'2026-11-05 08:05:23.000');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_transfer_transactions`
--

DROP TABLE IF EXISTS `bank_transfer_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transfer_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `transaction_at` datetime(3) NOT NULL,
  `transfer_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNMATCHED',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime(3) DEFAULT NULL,
  `review_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_transfer_transactions_bank_transaction_id_unique` (`bank_transaction_id`),
  KEY `bank_transfer_transactions_payment_id_foreign` (`payment_id`),
  KEY `bank_transfer_transactions_recorded_by_foreign` (`recorded_by`),
  KEY `bank_transfer_transactions_reviewed_by_foreign` (`reviewed_by`),
  KEY `bank_transfer_transactions_status_transaction_at_index` (`status`,`transaction_at`),
  KEY `bank_transfer_transactions_booking_id_status_index` (`booking_id`,`status`),
  CONSTRAINT `bank_transfer_transactions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bank_transfer_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bank_transfer_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_transfer_transactions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transfer_transactions`
--

LOCK TABLES `bank_transfer_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transfer_transactions` DISABLE KEYS */;
INSERT INTO `bank_transfer_transactions` VALUES (1,'1',7,3,400000.00,'VND','2026-11-04 16:22:00.000',NULL,'LATE_PAYMENT',1,1,'2026-11-04 16:26:19.000',NULL,'2026-11-04 16:23:14.000','2026-11-04 16:26:19.000');
/*!40000 ALTER TABLE `bank_transfer_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_name_unique` (`name`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES (1,'Luật & nhập môn','luat-nhap-mon','Luật cơ bản, cách tính điểm và hướng dẫn bắt đầu chơi pickleball.','2026-11-02 10:02:04.000','2026-11-02 10:02:04.000'),(2,'Chiến thuật sân đấu','chien-thuat-san-dau','Mẹo di chuyển, phối hợp đôi và xử lý bóng trong trận pickleball.','2026-11-02 10:02:05.000','2026-11-02 10:02:05.000'),(3,'Văn hóa cộng đồng','van-hoa-cong-dong','Cách chơi giao lưu vui vẻ, an toàn và tôn trọng mọi trình độ.','2026-11-02 10:02:05.000','2026-11-02 10:02:05.000'),(4,'Dụng cụ & sức khỏe','dung-cu-suc-khoe','Gợi ý chọn trang bị cơ bản và chuẩn bị thể lực trước khi lên sân.','2026-11-02 10:02:05.000','2026-11-02 10:02:05.000');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_post_categories`
--

DROP TABLE IF EXISTS `blog_post_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_categories` (
  `post_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`category_id`),
  KEY `blog_post_categories_category_id_post_id_index` (`category_id`,`post_id`),
  CONSTRAINT `blog_post_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_categories_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_post_categories`
--

LOCK TABLES `blog_post_categories` WRITE;
/*!40000 ALTER TABLE `blog_post_categories` DISABLE KEYS */;
INSERT INTO `blog_post_categories` VALUES (1,1),(2,2),(3,3),(4,4),(5,4);
/*!40000 ALTER TABLE `blog_post_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author_id` bigint unsigned DEFAULT NULL,
  `title` varchar(220) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','PUBLISHED','ARCHIVED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `published_at` datetime(3) DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  KEY `blog_posts_status_published_at_index` (`status`,`published_at`),
  KEY `blog_posts_author_id_index` (`author_id`),
  CONSTRAINT `blog_posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES (1,NULL,'Luật pickleball cơ bản: 5 điều người mới cần nhớ trước trận đầu','luat-pickleball-co-ban-cho-nguoi-moi','Nắm cách giao bóng chéo sân, luật hai lần nảy, khu vực kitchen và cách tính điểm để tự tin bước vào trận pickleball đầu tiên tại TP.HCM.','Tóm tắt nhanh: Người mới chơi pickleball chỉ cần nhớ năm điểm: giao bóng chéo sân; bóng giao và cú trả giao đều phải nảy một lần; không volley trong kitchen; bóng chạm vạch thường được tính là trong sân; và cách tính điểm có thể khác giữa trận giao lưu với giải đấu.\n\nPickleball kết hợp những yếu tố quen thuộc của các môn vợt nhưng có luật riêng. Nếu bạn đang tìm hiểu pickleball tại TP.HCM, hãy đọc phần tóm tắt dưới đây trước khi đặt sân hoặc tham gia buổi chơi đầu tiên.\n\n1. Giao bóng chéo sân như thế nào?\n\nỞ nội dung đôi, người giao đứng sau đường cuối sân và đưa bóng chéo sang ô giao bóng đối diện. Theo luật thi đấu của USA Pickleball, cú giao phải hợp lệ theo một trong các kiểu giao được cho phép; người mới nên tập động tác kiểm soát, đưa bóng sâu và ổn định trước khi cố giao mạnh.\n\n2. Luật hai lần nảy là gì?\n\nSau cú giao, đội nhận phải để bóng nảy rồi mới trả. Đội giao cũng phải để cú trả nảy trước khi đánh tiếp. Sau hai lần nảy này, hai bên được chọn volley hoặc đánh bóng sau khi nảy. Quy tắc này tạo thời gian cho cả hai đội tiến vào vị trí và bắt đầu rally.\n\n3. Kitchen có cấm bước vào không?\n\nKitchen là vùng không volley sát lưới. Bạn có thể bước vào vùng này để đánh một quả bóng đã nảy, nhưng không được volley khi đang chạm vùng hoặc vạch kitchen. Cũng cần giữ thăng bằng sau cú đánh: nếu đà người khiến bạn chạm kitchen sau khi volley, đó vẫn có thể là lỗi.\n\n4. Bóng chạm vạch là trong hay ngoài?\n\nPhần lớn đường biên được tính là trong nếu bóng chạm vạch. Ngoại lệ quan trọng là vạch kitchen trong cú giao bóng: bóng giao chạm vạch này bị xem là giao ngắn và lỗi. Khi chơi giao lưu, hãy thống nhất cách gọi bóng rõ ràng và tôn trọng quyết định của người ở gần bóng nhất.\n\n5. Tính điểm ra sao?\n\nTrong cách tính điểm side-out truyền thống, chỉ đội đang giao bóng mới ghi điểm; trận thường chơi đến 11 và phải hơn đối thủ ít nhất 2 điểm. Một số giải hoặc nhóm chơi dùng rally scoring, nơi mỗi rally đều có điểm. Vì vậy, hãy hỏi trước khi bắt đầu xem nhóm đang dùng thể thức nào.\n\nCâu hỏi thường gặp\n\nNgười mới có cần thuộc hết luật trước khi ra sân không?\n\nKhông cần. Hãy nhớ luật hai lần nảy, kitchen và cách giao bóng cơ bản; người chơi cùng sân có thể nhắc thêm những tình huống phát sinh.\n\nLuật thi đấu có thể thay đổi không?\n\nCó. Luật chính thức được cập nhật định kỳ. Khi tham gia giải, hãy kiểm tra rulebook và điều lệ của ban tổ chức thay vì chỉ dựa vào hướng dẫn nhập môn.\n\nGợi ý thực hành: Trước trận đầu tại một câu lạc bộ ở TP.HCM, hãy dành vài phút tập giao bóng và trả giao ở tốc độ vừa phải. Khi đã hiểu nhịp bóng, trận đấu sẽ dễ theo dõi hơn nhiều.\n\nNguồn tham khảo: USA Pickleball — Rules Summary (https://usapickleball.org/rules/summary/) và Official Rulebook (https://usapickleball.org/rules/). Bài viết là hướng dẫn nhập môn, không thay thế luật của giải đấu cụ thể.','blog/news/pickleball-luat-cho-nguoi-moi.png','Người chơi pickleball mới tập luyện cùng huấn luyện viên trên sân trong nhà.','Luật pickleball cơ bản cho người mới | BALLBURN','Hướng dẫn luật pickleball dễ hiểu: giao bóng, luật hai lần nảy, kitchen, lỗi thường gặp và cách tính điểm cho người mới chơi.','PUBLISHED','2026-10-21 09:00:00.000','2026-11-02 10:02:05.000','2026-11-02 10:03:52.000'),(2,NULL,'Chiến thuật kitchen trong pickleball: đứng đâu và di chuyển thế nào?','chien-thuat-kitchen-trong-pickleball','Hiểu vùng kitchen, phối hợp cùng đồng đội và tiến lên vạch không volley đúng thời điểm để kiểm soát rally pickleball tốt hơn.','Tóm tắt nhanh: Kitchen là vùng không volley sát lưới. Mục tiêu thường gặp trong đánh đôi là tiến lên vạch kitchen sau khi xử lý đúng luật hai lần nảy, giữ tư thế sẵn sàng và di chuyển ngang đồng bộ với đồng đội. Đứng gần vạch giúp bạn phản ứng sớm, nhưng không có nghĩa là được volley khi chân chạm vùng cấm.\n\nKitchen trong pickleball là gì?\n\nKitchen là cách gọi thông dụng của non-volley zone (NVZ), khu vực kéo dài 7 feet, khoảng 2,13 m, từ lưới ở mỗi bên sân. Luật cấm volley khi người chơi hoặc vật họ đang mang chạm vùng này. Bạn vẫn có thể bước vào kitchen để đánh bóng sau khi bóng đã nảy.\n\nKhi nào nên tiến lên vạch kitchen?\n\nTrong đánh đôi, người nhận bóng thường có thể bắt đầu gần vạch kitchen, còn đồng đội đứng sau để trả giao. Đội giao cần chờ bóng giao và cú trả đều nảy theo luật hai lần nảy, sau đó tìm cơ hội tiến lên. Hãy tiến theo nhịp bóng và giữ thăng bằng thay vì lao lên bất kể đối thủ chuẩn bị đánh.\n\nBa thói quen giúp kiểm soát khu vực lưới\n\n• Giữ paddle trước người: tư thế sẵn sàng giúp rút ngắn thời gian phản ứng.\n• Di chuyển theo bóng: hai người trong đội dịch chuyển ngang cùng nhau, hạn chế mở khoảng trống giữa sân.\n• Chờ bóng phù hợp: bóng thấp thường nên được xử lý mềm và có kiểm soát; tránh cố đập những quả bóng đang ở vị trí bất lợi.\n\nNhững lỗi thường gặp ở kitchen\n\nLỗi phổ biến là volley khi đang đứng trên vạch kitchen, bước vào vùng do đà sau cú volley, hoặc để hai người cùng dồn vào một phía. Khi đánh đôi, hãy trao đổi ngắn gọn như “của tôi”, “của bạn” để tránh va chạm và bỏ trống phần sân còn lại.\n\nCâu hỏi thường gặp\n\nCó được đứng trong kitchen không?\n\nĐược. Bạn chỉ phạm lỗi khi volley trong lúc chạm kitchen hoặc vi phạm liên quan đến đà sau cú volley.\n\nNgười mới nên đứng sát lưới ngay từ đầu không?\n\nHãy bắt đầu ở vị trí phù hợp với luật và cú đánh tiếp theo. Tiến lên có kiểm soát sau khi xử lý cú trả giao thường giúp người mới giữ thăng bằng tốt hơn.\n\nỞ các buổi chơi đôi tại TP.HCM, hãy tập một bài đơn giản: hai người đứng song song, đánh bóng nhẹ qua lại và cùng bước ngang theo hướng bóng. Bài tập này giúp phối hợp vị trí trước khi tăng tốc độ rally.\n\nNguồn tham khảo: USA Pickleball — Rules Summary (https://usapickleball.org/rules/summary/) và Positioning, Patience, and Kitchen Line Strategy (https://usapickleball.org/pickleball-skills/level-two/pickleball-basics-positioning-tips/).','blog/news/pickleball-chien-thuat-kitchen.png','Bốn người chơi pickleball đánh đôi và kiểm soát bóng gần khu vực kitchen.','Chiến thuật kitchen pickleball dễ áp dụng | BALLBURN','Tìm hiểu kitchen pickleball, cách đứng sát vạch non-volley zone, di chuyển đồng bộ và tránh mắc lỗi khi volley trong trận đôi.','PUBLISHED','2026-10-23 09:00:00.000','2026-11-02 10:02:05.000','2026-11-02 10:03:52.000'),(3,NULL,'Văn hóa chơi pickleball giao lưu: 7 phép lịch sự trên sân đôi','van-hoa-choi-pickleball-giao-luu','Từ cách xoay vòng sân đến gọi bóng và giao tiếp với đồng đội: những nguyên tắc nhỏ giúp buổi pickleball giao lưu vui vẻ, công bằng hơn.','Tóm tắt nhanh: Chơi giao lưu vui nhất khi mọi người chia lượt công bằng, gọi bóng trung thực, tôn trọng trình độ và giữ nhịp trận đấu. Người mới không cần đánh thật hay để được chào đón; chỉ cần giao tiếp rõ ràng và sẵn sàng học luật cùng bạn chơi.\n\n1. Hỏi cách xoay vòng trước khi vào sân\n\nMỗi câu lạc bộ có thể dùng cách chờ lượt khác nhau: danh sách, bảng xoay vòng hoặc ghép nhóm theo số người. Hãy hỏi người phụ trách trước để không vô tình chiếm sân quá lâu.\n\n2. Thống nhất điểm số và luật trước trận\n\nChốt thể thức tính điểm, luật giao bóng và thời lượng trận giúp cả nhóm tránh tranh luận giữa chừng. Nếu có khác biệt về luật, dừng lại trao đổi bình tĩnh trước khi tiếp tục.\n\n3. Gọi bóng to, ngắn gọn và trung thực\n\nNếu bóng gần vạch, người ở vị trí quan sát tốt nhất nên gọi rõ “trong” hoặc “ngoài”. Khi không chắc chắn, trao lợi thế cho đối thủ là cách ứng xử đẹp trong trận giao lưu.\n\n4. Giao tiếp với đồng đội\n\nNhững câu đơn giản như “của tôi”, “của bạn” hoặc “đổi bên” giúp tránh va chạm và bỏ bóng. Trong đánh đôi, hãy cùng nhau khích lệ thay vì đổ lỗi sau một pha hỏng.\n\n5. Điều chỉnh lực đánh phù hợp\n\nGhép trận theo trình độ nếu nhóm có quy ước, nhưng đừng xem người mới là mục tiêu để ghi điểm bằng mọi giá. Trận giao lưu có thể là dịp thử cú đánh mới, kéo dài rally và giúp nhau tiến bộ.\n\n6. Giữ khu vực thi đấu an toàn\n\nĐợi rally kết thúc rồi băng qua sân; nhặt bóng rơi khi mọi người đã dừng chơi; không vung vợt gần người khác. Nếu sân đông, đặt túi và chai nước ngoài lối di chuyển.\n\n7. Chào hỏi và cảm ơn sau trận\n\nMột lời chào trước trận, một cái chạm paddle sau trận và lời cảm ơn người chia sân giúp người mới dễ hòa nhập. Ở cộng đồng chơi tại TP.HCM, sự thân thiện thường quan trọng không kém tỷ số.\n\nCâu hỏi thường gặp\n\nTôi chưa có đồng đội thì có thể tham gia không?\n\nHãy hỏi câu lạc bộ về buổi open play, ghép cặp hoặc giờ phù hợp cho người mới. Nhiều nhóm có cách sắp xếp người chơi đến lẻ.\n\nNếu hai bên không thống nhất bóng trong hay ngoài thì sao?\n\nGiữ bình tĩnh, ưu tiên tinh thần giao lưu và làm theo quy ước của ban tổ chức. Trận giải có thể áp dụng quy trình gọi bóng chính thức khác với buổi chơi thường.\n\nPickleball phát triển nhờ cả vận động lẫn kết nối xã hội. Hãy đến sân với tinh thần tôn trọng, cởi mở và sẵn sàng xoay vòng để ai cũng có cơ hội chơi.','blog/news/pickleball-cong-dong-giao-luu.png','Nhóm người chơi nhiều độ tuổi vui vẻ trò chuyện sau một trận pickleball giao lưu.','7 phép lịch sự khi chơi pickleball giao lưu | BALLBURN','Cẩm nang văn hóa pickleball giao lưu: xoay vòng sân công bằng, gọi bóng rõ ràng, tôn trọng trình độ và giao tiếp tích cực.','PUBLISHED','2026-10-25 09:00:00.000','2026-11-02 10:02:05.000','2026-11-02 10:03:52.000'),(4,NULL,'Mới chơi pickleball cần mua gì? Cách chọn vợt, giày và bóng cơ bản','nguoi-moi-choi-pickleball-can-mua-gi','Bắt đầu với vợt vừa tay, giày có độ bám và bóng phù hợp mặt sân. Hướng dẫn ưu tiên món cần thiết trước khi đầu tư thêm phụ kiện.','Tóm tắt nhanh: Người mới chỉ cần một cây vợt vừa tay, giày thể thao có độ bám phù hợp với mặt sân và bóng đúng loại sân. Hãy ưu tiên cảm giác thoải mái, an toàn và ngân sách trước các thông số nâng cao; nhiều câu lạc bộ tại TP.HCM có thể cho thuê hoặc mượn dụng cụ để thử trước.\n\nChọn vợt pickleball như thế nào?\n\nThử cầm vợt để xem cán có vừa tay, trọng lượng có khiến cổ tay nhanh mỏi hay không, và mặt vợt có phù hợp với cách bạn muốn điều khiển bóng. Người mới chưa cần chạy theo công nghệ hay thông số quảng cáo. Nếu có thể, hãy mượn thử vài mẫu trong một buổi chơi rồi mới quyết định mua.\n\nGiày nào phù hợp để chơi pickleball?\n\nPickleball có nhiều bước ngang và đổi hướng. Hãy chọn giày chuyên cho sân tennis hoặc pickleball có độ bám và hỗ trợ ngang phù hợp với mặt sân bạn chơi. Giày chạy bộ thường được thiết kế cho chuyển động thẳng, nên không phải lựa chọn lý tưởng cho mọi sân và mọi người.\n\nBóng trong nhà và ngoài trời có giống nhau không?\n\nBóng pickleball có các lỗ nhưng thiết kế có thể khác nhau theo môi trường sử dụng. Hãy hỏi nơi tổ chức dùng bóng nào và chọn đúng loại được khuyến nghị cho mặt sân; bóng và độ nảy ảnh hưởng đến nhịp rally.\n\nPhụ kiện nào nên mang theo?\n\n• Nước uống cá nhân và khăn nhỏ.\n• Quần áo thể thao thoáng, dễ di chuyển.\n• Vớ phù hợp với giày và một túi đựng vợt.\n• Kem chống nắng hoặc mũ nếu chơi ngoài trời.\n\nCâu hỏi thường gặp\n\nNgười mới có cần mua vợt đắt tiền không?\n\nKhông. Hãy chọn dụng cụ vừa tay và dễ kiểm soát trước. Sau khi chơi đủ để hiểu sở thích, bạn có thể so sánh kỹ hơn về trọng lượng, tay cầm và cảm giác mặt vợt.\n\nCó thể mang giày chạy bộ ra sân không?\n\nCó thể phụ thuộc nội quy và điều kiện sân, nhưng giày hỗ trợ chuyển động ngang thường phù hợp hơn. Nếu chưa rõ, hỏi câu lạc bộ trước khi đến.\n\nNguồn tham khảo an toàn: AAOS OrthoInfo — Pickleball Injury Prevention Tips (https://orthoinfo.aaos.org/globalassets/pdfs/pickleball-injury-prevention-tips.pdf) khuyến nghị giày tennis hoặc pickleball để có hỗ trợ và độ bám phù hợp. Cảm giác và độ vừa vặn khác nhau theo từng người.','blog/news/pickleball-dung-cu-co-ban.png','Vợt, bóng nhựa đục lỗ, giày thể thao và bình nước chuẩn bị bên cạnh sân pickleball.','Người mới chơi pickleball cần mua gì? | BALLBURN','Hướng dẫn chọn đồ pickleball cho người mới: vợt vừa tay, giày sân phù hợp, bóng trong nhà hoặc ngoài trời và phụ kiện cần thiết.','PUBLISHED','2026-10-27 09:00:00.000','2026-11-02 10:02:05.000','2026-11-02 10:03:52.000'),(5,NULL,'Khởi động trước khi chơi pickleball: quy trình 8 phút dễ áp dụng','khoi-dong-truoc-khi-choi-pickleball','Một quy trình khởi động ngắn gồm đi bộ nhanh, vận động khớp và đánh bóng nhẹ giúp cơ thể làm quen với các bước đổi hướng trước trận.','Tóm tắt nhanh: Trước khi chơi pickleball, hãy tăng nhịp vận động từ từ bằng đi bộ nhanh hoặc bước ngang nhẹ, làm nóng vai và chân, sau đó đánh bóng ở cường độ thấp trước khi vào trận. Một lịch khởi động gợi ý trong 8 phút có thể giúp bạn chuẩn bị tốt hơn, nhưng không đảm bảo ngăn ngừa chấn thương.\n\nQuy trình khởi động 8 phút\n\n• Phút 0–2: đi bộ nhanh quanh sân, bước tại chỗ hoặc di chuyển nhẹ để cơ thể ấm lên.\n• Phút 2–4: xoay vai, cổ tay và hông nhẹ nhàng trong biên độ thoải mái; không giật mạnh.\n• Phút 4–6: bước ngang ngắn, gập gối nông và chuyển trọng lượng từ chân này sang chân kia có kiểm soát.\n• Phút 6–8: đánh bóng nhẹ ở cự ly gần, tập vài cú forehand/backhand và tăng tốc dần nếu cảm thấy ổn.\n\nVì sao không nên vào trận với cường độ tối đa ngay?\n\nPickleball có những pha tăng tốc, dừng và đổi hướng. Bắt đầu bằng rally nhẹ giúp bạn cảm nhận mặt sân, độ nảy và nhịp di chuyển trước khi chơi nhanh. AAOS cũng khuyến nghị làm nóng cơ thể, bắt đầu với bài đánh nhẹ, mang giày phù hợp, uống đủ nước và điều chỉnh thời lượng theo khả năng.\n\nĐiều chỉnh theo thời tiết TP.HCM\n\nNếu chơi ngoài trời vào ngày nóng, hãy đến sớm để cơ thể thích nghi, chuẩn bị nước và nghỉ khi thấy mệt. Với sân trong nhà, vẫn nên khởi động vì chuyển hướng nhanh có thể xuất hiện ngay từ những pha đầu tiên. Không cố tiếp tục nếu thấy đau bất thường, chóng mặt hoặc khó thở.\n\nCâu hỏi thường gặp\n\nKhởi động 8 phút có đủ cho tất cả mọi người không?\n\nĐây chỉ là lịch tham khảo ngắn. Thời gian phù hợp phụ thuộc thể lực, tuổi, kinh nghiệm, thời tiết và tình trạng sức khỏe; hãy kéo dài phần chuẩn bị nếu bạn cần.\n\nCó nên chơi tiếp khi thấy đau?\n\nHãy dừng lại nếu xuất hiện đau bất thường hoặc triệu chứng khiến bạn lo lắng. Người có bệnh nền, chấn thương cũ hoặc cần hướng dẫn cá nhân nên hỏi chuyên gia y tế trước khi thay đổi chương trình vận động.\n\nTrước buổi chơi tại câu lạc bộ pickleball ở TP.HCM, hãy dành thời gian làm quen sân và hỏi người hướng dẫn về bài khởi động phù hợp. Thói quen đều đặn và tăng cường độ từ từ quan trọng hơn việc cố làm thật nhanh.\n\nNguồn tham khảo: AAOS OrthoInfo — 5 Pickleball Injury Prevention Tips (https://orthoinfo.aaos.org/globalassets/pdfs/pickleball-injury-prevention-tips.pdf). Nội dung chỉ nhằm cung cấp thông tin chung, không phải chẩn đoán hoặc tư vấn y tế cá nhân.','blog/news/pickleball-khoi-dong-an-toan.png','Hai người chơi pickleball khởi động bằng động tác vận động vai và bước ngang cạnh sân.','Khởi động pickleball trong 8 phút an toàn | BALLBURN','Tham khảo quy trình khởi động pickleball 8 phút gồm đi bộ, vận động linh hoạt và đánh nhẹ; tăng cường độ từ từ, uống đủ nước.','PUBLISHED','2026-10-30 09:00:00.000','2026-11-02 10:02:05.000','2026-11-02 10:03:52.000');
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_holds`
--

DROP TABLE IF EXISTS `booking_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_holds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_reference` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `court_id` bigint unsigned NOT NULL,
  `starts_at` datetime(3) NOT NULL,
  `ends_at` datetime(3) NOT NULL,
  `expires_at` datetime(3) NOT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_holds_token_hash_court_id_starts_at_unique` (`token_hash`,`court_id`,`starts_at`),
  KEY `booking_holds_court_id_starts_at_ends_at_expires_at_index` (`court_id`,`starts_at`,`ends_at`,`expires_at`),
  KEY `booking_holds_token_hash_expires_at_index` (`token_hash`,`expires_at`),
  CONSTRAINT `booking_holds_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_holds`
--

LOCK TABLES `booking_holds` WRITE;
/*!40000 ALTER TABLE `booking_holds` DISABLE KEYS */;
INSERT INTO `booking_holds` VALUES (4,'d9f39688f0548653078f5ce07983b7ed69b36d1672e069d7fc83dbc434ebab85',NULL,1,'2026-11-05 15:30:00.000','2026-11-05 16:00:00.000','2026-11-05 08:13:44.000','2026-11-05 08:08:44.000','2026-11-05 08:08:44.000');
/*!40000 ALTER TABLE `booking_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_orders`
--

DROP TABLE IF EXISTS `booking_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hold_token_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING_PAYMENT','PAID') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING_PAYMENT',
  `payment_deadline_at` datetime(3) DEFAULT NULL,
  `paid_at` datetime(3) DEFAULT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `customer_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_orders_booking_code_unique` (`booking_code`),
  UNIQUE KEY `booking_orders_status_token_hash_unique` (`status_token_hash`),
  UNIQUE KEY `booking_orders_hold_token_hash_unique` (`hold_token_hash`),
  KEY `booking_orders_status_payment_deadline_at_index` (`status`,`payment_deadline_at`),
  KEY `booking_orders_customer_email_index` (`customer_email`),
  KEY `booking_orders_created_at_index` (`created_at`),
  KEY `booking_orders_paid_at_index` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_orders`
--

LOCK TABLES `booking_orders` WRITE;
/*!40000 ALTER TABLE `booking_orders` DISABLE KEYS */;
INSERT INTO `booking_orders` VALUES (1,'BB-20261104-VDVXZW','858039b8396548695faa4bf189ab8cc9b2913efa87bf8de68f730eb40822552b',NULL,'duong','duong@gmail.com',NULL,'PAID','2026-11-04 08:35:01.000',NULL,600000.00,0.00,600000.00,'VND',NULL,'2026-11-04 08:30:01.000','2026-11-04 17:19:23.000'),(6,'BB-20261104-N6TGTS','8185028c1a27f52edc21357403fc49bd7ac62a4b9a7b0fa4c809032435d932ec',NULL,'Anh Lương','anhluong@gmail.com',NULL,'PAID',NULL,NULL,500000.00,0.00,500000.00,'VND',NULL,'2026-11-03 17:57:49.000','2026-11-03 17:57:49.000'),(7,'BB-20261104-LU1L7F','5d59f2725dcc15c0bd56f9d6b11e8c43f0671eecbad8cfb94862b67b26c04291',NULL,'A Quân','aquan@g.com',NULL,'PAID',NULL,NULL,640000.00,0.00,640000.00,'VND',NULL,'2026-11-03 17:59:39.000','2026-11-03 17:59:39.000'),(8,'BB-20261104-NDBPRJ','605b70a8068e84454be5dc9b107c90fd3684f8b7c15d32a0b7b78e8573e2f17b',NULL,'duong','duong@gmail.com',NULL,'PAID',NULL,NULL,800000.00,0.00,800000.00,'VND',NULL,'2026-11-04 07:13:21.000','2026-11-04 07:13:21.000'),(9,'BB-20261104-QX7DUN','2d1898f19afa65bbfa2053259adc2705477255e483f859400e8333b4be1a7833',NULL,'a','duong@gmail.com',NULL,'PAID',NULL,NULL,400000.00,0.00,400000.00,'VND',NULL,'2026-11-04 07:16:18.000','2026-11-04 07:16:18.000'),(11,'BB-20261104-TNFC81','889aad4354eb3743c91ed1bbbba1ee72aa8766d2c459808ee5706cfd976ee5ab',NULL,'duong','duong@gmail.com',NULL,'PENDING_PAYMENT','2026-11-04 08:01:54.000',NULL,200000.00,0.00,200000.00,'VND',NULL,'2026-11-04 07:56:54.000','2026-11-04 07:56:54.000'),(12,'BB-20261104-OKH24H','5074586dfdb8cc5b8e1c78a09556e202f9966a5acc1f84c1ebe30ad76502785f',NULL,'duong','duong@gmail.com',NULL,'PAID','2026-11-04 17:35:03.000',NULL,400000.00,0.00,400000.00,'VND',NULL,'2026-11-04 08:07:38.000','2026-11-04 16:26:19.000'),(14,'BB-20261105-JOMC15','11d933d5e567f5a050b72ddc6f50aef71686c914ba6222ce44cedc861615a79b',NULL,'duong','duong@gmail.com',NULL,'PAID','2026-11-05 08:17:08.000','2026-11-05 08:13:19.000',800000.00,0.00,800000.00,'VND',NULL,'2026-11-05 08:12:08.000','2026-11-05 08:13:19.000'),(15,'BB-20261105-NQQCP8','f486c50f0d8dec538ba44cc9426bd9c9c5c7659bea30eb5ae0d7b97077ea1391',NULL,'duong',NULL,'0987654321','PENDING_PAYMENT','2026-11-05 08:35:26.000',NULL,1798000.00,0.00,1798000.00,'VND','book đánh giải','2026-11-05 08:30:26.000','2026-11-05 08:30:26.000'),(16,'BB-20261105-TZ3L4B','deca65d1710d3360db68edc0438582c5c1c2d8419e4135dde5e8622e337ddf26',NULL,'duong',NULL,'0987654321','PENDING_PAYMENT','2026-11-05 09:19:49.000',NULL,400000.00,0.00,400000.00,'VND','heheee','2026-11-05 09:14:50.000','2026-11-05 09:14:50.000'),(17,'BB-20261105-1EKODH','b9a5a9727940ed076ae549afc0bbf657640f7ad3c8c173db31b851da586414e2',NULL,'duong',NULL,'0987654321','PENDING_PAYMENT','2026-11-05 09:27:32.000',NULL,400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000'),(18,'BB-20261105-LZ0XZN','17df1b888ac8482dcbde835a7395077123f3bf2277ec5215a6ea0e14765be5f0','c9d3f77035652e0d5e50e343ca6393a132bbc06f6090947c4b047eb59464a080','duong',NULL,'0987654321','PENDING_PAYMENT','2026-11-05 10:00:46.000',NULL,400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000'),(19,'BB-20261109-EMKZVC','6da45a4b66013e5e8df3d29af857aa654a4f17e2d5e7027e44925fbecae8e57c','99fe4f023d35774887fd93c681f14533831c97de66bb8e4f9f0c54df003c8af7','duong',NULL,'0987654321','PENDING_PAYMENT','2026-11-09 09:13:35.000',NULL,400000.00,0.00,400000.00,'VND',NULL,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000');
/*!40000 ALTER TABLE `booking_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_status_history`
--

DROP TABLE IF EXISTS `booking_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `old_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `change_source` enum('SYSTEM','ADMIN','PAYMENT_WEBHOOK','CUSTOMER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `booking_status_history_changed_by_foreign` (`changed_by`),
  KEY `booking_status_history_booking_id_created_at_index` (`booking_id`,`created_at`),
  CONSTRAINT `booking_status_history_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `booking_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_status_history`
--

LOCK TABLES `booking_status_history` WRITE;
/*!40000 ALTER TABLE `booking_status_history` DISABLE KEYS */;
INSERT INTO `booking_status_history` VALUES (1,1,NULL,'CONFIRMED',1,'ADMIN','Admin đặt sân thay khách hàng.','2026-11-03 17:57:49.499'),(2,2,NULL,'CONFIRMED',1,'ADMIN','Admin đặt sân thay khách hàng.','2026-11-03 17:59:39.401'),(3,3,NULL,'CONFIRMED',NULL,'CUSTOMER','Khách đặt sân trực tuyến; thanh toán sẽ được bổ sung sau.','2026-11-04 07:13:21.576'),(4,4,NULL,'CONFIRMED',NULL,'CUSTOMER','Khách đặt sân trực tuyến; thanh toán sẽ được bổ sung sau.','2026-11-04 07:16:18.080'),(6,6,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo booking và nhận mã QR tham chiếu; chờ gửi ảnh chuyển khoản.','2026-11-04 07:56:54.659'),(7,7,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo booking và nhận mã QR tham chiếu; chờ gửi ảnh chuyển khoản.','2026-11-04 08:07:38.229'),(9,9,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261104-VDVXZW và chờ thanh toán.','2026-11-04 08:30:01.971'),(10,10,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261104-VDVXZW và chờ thanh toán.','2026-11-04 08:30:02.029'),(11,9,'PENDING_PAYMENT','CANCELLED',1,'ADMIN','Cập nhật trạng thái thủ công bởi quản trị viên.','2026-11-04 16:13:21.464'),(12,10,'PENDING_PAYMENT','CANCELLED',1,'ADMIN','Cập nhật trạng thái thủ công bởi quản trị viên.','2026-11-04 16:13:21.470'),(13,7,'PENDING_PAYMENT','EXPIRED',1,'ADMIN','Payment deadline passed before manual bank reconciliation.','2026-11-04 16:26:19.290'),(14,9,'CANCELLED','PENDING_PAYMENT',1,'ADMIN','Qu?n tr? vi?n c?p nh?t tr?ng th?i ??n.','2026-11-04 17:09:12.733'),(15,10,'CANCELLED','PENDING_PAYMENT',1,'ADMIN','Qu?n tr? vi?n c?p nh?t tr?ng th?i ??n.','2026-11-04 17:09:12.735'),(22,9,'PENDING_PAYMENT','PAID',1,'ADMIN','Qu?n tr? vi?n c?p nh?t tr?ng th?i ??n.','2026-11-04 17:19:23.952'),(23,10,'PENDING_PAYMENT','PAID',1,'ADMIN','Qu?n tr? vi?n c?p nh?t tr?ng th?i ??n.','2026-11-04 17:19:23.955'),(24,14,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-JOMC15 và chờ thanh toán.','2026-11-05 08:12:08.738'),(25,15,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-JOMC15 và chờ thanh toán.','2026-11-05 08:12:08.760'),(26,14,'PENDING_PAYMENT','PAID',1,'ADMIN','Quản trị viên cập nhật trạng thái đơn.','2026-11-05 08:13:19.433'),(27,15,'PENDING_PAYMENT','PAID',1,'ADMIN','Quản trị viên cập nhật trạng thái đơn.','2026-11-05 08:13:19.435'),(28,16,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-NQQCP8 và chờ thanh toán.','2026-11-05 08:30:26.834'),(29,17,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-NQQCP8 và chờ thanh toán.','2026-11-05 08:30:26.855'),(30,18,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-NQQCP8 và chờ thanh toán.','2026-11-05 08:30:26.865'),(31,19,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-NQQCP8 và chờ thanh toán.','2026-11-05 08:30:26.874'),(32,20,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-TZ3L4B và chờ thanh toán.','2026-11-05 09:14:50.135'),(33,21,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-1EKODH và chờ thanh toán.','2026-11-05 09:22:32.539'),(34,22,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261105-LZ0XZN và chờ thanh toán.','2026-11-05 09:56:50.196'),(35,23,NULL,'PENDING_PAYMENT',NULL,'CUSTOMER','Khách tạo đơn đặt sân BB-20261109-EMKZVC và chờ thanh toán.','2026-11-09 09:12:14.630');
/*!40000 ALTER TABLE `booking_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_order_id` bigint unsigned DEFAULT NULL,
  `booking_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `court_id` bigint unsigned NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` datetime(3) NOT NULL,
  `ends_at` datetime(3) NOT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `customer_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  KEY `bookings_customer_email_index` (`customer_email`),
  KEY `bookings_created_at_index` (`created_at`),
  KEY `bookings_court_schedule_index` (`court_id`,`starts_at`,`ends_at`),
  KEY `bookings_booking_order_id_index` (`booking_order_id`),
  CONSTRAINT `bookings_booking_order_id_foreign` FOREIGN KEY (`booking_order_id`) REFERENCES `booking_orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_bookings_amounts` CHECK (((`base_amount` >= 0) and (`discount_amount` >= 0) and (`total_amount` >= 0) and (`discount_amount` <= `base_amount`) and (`total_amount` = (`base_amount` - `discount_amount`)))),
  CONSTRAINT `chk_bookings_interval` CHECK ((`starts_at` < `ends_at`))
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,6,'BB-20261104-N6TGTS',3,'Anh Lương','anhluong@gmail.com',NULL,'2026-11-03 22:30:00.000','2026-11-04 00:30:00.000',500000.00,0.00,500000.00,'VND',NULL,'2026-11-03 17:57:49.000','2026-11-03 17:57:49.000'),(2,7,'BB-20261104-LU1L7F',6,'A Quân','aquan@g.com',NULL,'2026-11-04 00:30:00.000','2026-11-04 04:00:00.000',640000.00,0.00,640000.00,'VND',NULL,'2026-11-03 17:59:39.000','2026-11-03 17:59:39.000'),(3,8,'BB-20261104-NDBPRJ',1,'duong','duong@gmail.com',NULL,'2026-11-04 09:00:00.000','2026-11-04 11:00:00.000',800000.00,0.00,800000.00,'VND',NULL,'2026-11-04 07:13:21.000','2026-11-04 07:13:21.000'),(4,9,'BB-20261104-QX7DUN',1,'a','duong@gmail.com',NULL,'2026-11-04 13:30:00.000','2026-11-04 14:30:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-04 07:16:18.000','2026-11-04 07:16:18.000'),(6,11,'BB-20261104-TNFC81',2,'duong','duong@gmail.com',NULL,'2026-11-04 15:00:00.000','2026-11-04 15:30:00.000',200000.00,0.00,200000.00,'VND',NULL,'2026-11-04 07:56:54.000','2026-11-04 07:56:54.000'),(7,12,'BB-20261104-OKH24H',2,'duong','duong@gmail.com',NULL,'2026-11-04 13:30:00.000','2026-11-04 14:30:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-04 08:07:38.000','2026-11-04 16:26:19.000'),(9,1,'BI-20261104-5F4297E3BE',1,'duong','duong@gmail.com',NULL,'2026-11-04 11:00:00.000','2026-11-04 11:30:00.000',200000.00,0.00,200000.00,'VND',NULL,'2026-11-04 08:30:01.000','2026-11-04 16:13:21.000'),(10,1,'BI-20261104-88C61E4354',1,'duong','duong@gmail.com',NULL,'2026-11-04 12:00:00.000','2026-11-04 13:00:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-04 08:30:02.000','2026-11-04 16:13:21.000'),(14,14,'BI-20261105-A62E2878AD',1,'duong','duong@gmail.com',NULL,'2026-11-05 10:30:00.000','2026-11-05 11:30:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 08:12:08.000','2026-11-05 08:12:08.000'),(15,14,'BI-20261105-CDCB8F2D99',1,'duong','duong@gmail.com',NULL,'2026-11-05 13:30:00.000','2026-11-05 14:30:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 08:12:08.000','2026-11-05 08:12:08.000'),(16,15,'BI-20261105-CFCDE5ABAB',2,'duong',NULL,'0987654321','2026-11-05 12:30:00.000','2026-11-05 14:00:00.000',600000.00,0.00,600000.00,'VND','book đánh giải','2026-11-05 08:30:26.000','2026-11-05 08:30:26.000'),(17,15,'BI-20261105-3334BFCD71',3,'duong',NULL,'0987654321','2026-11-05 12:30:00.000','2026-11-05 14:00:00.000',600000.00,0.00,600000.00,'VND','book đánh giải','2026-11-05 08:30:26.000','2026-11-05 08:30:26.000'),(18,15,'BI-20261105-020A209289',4,'duong',NULL,'0987654321','2026-11-05 12:30:00.000','2026-11-05 14:00:00.000',448500.00,0.00,448500.00,'VND','book đánh giải','2026-11-05 08:30:26.000','2026-11-05 08:30:26.000'),(19,15,'BI-20261105-A957DDBEA9',4,'duong',NULL,'0987654321','2026-11-05 14:30:00.000','2026-11-05 15:00:00.000',149500.00,0.00,149500.00,'VND','book đánh giải','2026-11-05 08:30:26.000','2026-11-05 08:30:26.000'),(20,16,'BI-20261105-757B8F5A3F',1,'duong',NULL,'0987654321','2026-11-05 15:00:00.000','2026-11-05 16:00:00.000',400000.00,0.00,400000.00,'VND','heheee','2026-11-05 09:14:50.000','2026-11-05 09:14:50.000'),(21,17,'BI-20261105-6E53062DC9',1,'duong',NULL,'0987654321','2026-11-05 15:00:00.000','2026-11-05 16:00:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000'),(22,18,'BI-20261105-B2DCDDA8D5',1,'duong',NULL,'0987654321','2026-11-05 15:00:00.000','2026-11-05 16:00:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000'),(23,19,'BI-20261109-D8A41B76BF',1,'duong',NULL,'0987654321','2026-11-09 10:00:00.000','2026-11-09 11:00:00.000',400000.00,0.00,400000.00,'VND',NULL,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('ballburn-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1794215592),('ballburn-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1794215592;',1794215592);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('NEW','IN_PROGRESS','RESOLVED','SPAM') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NEW',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `resolved_at` datetime(3) DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_messages_status_created_at_index` (`status`,`created_at`),
  KEY `contact_messages_email_index` (`email`),
  KEY `contact_messages_assigned_to_index` (`assigned_to`),
  CONSTRAINT `contact_messages_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'Hoang Thuy Dung','test@ga.com','0987654321','Giá sân và dịch vụ','Cho tôi xin giá sân để búc 3 tháng','IN_PROGRESS',NULL,NULL,NULL,'2026-11-03 12:16:40.000','2026-11-03 12:28:40.000'),(2,'duongNT','test@ga.com',NULL,'Đặt sân và lịch chơi','hehee','NEW',NULL,NULL,NULL,'2026-11-09 04:19:27.000','2026-11-09 04:19:27.000'),(3,'duongNT','admin@gmail.com','0978097410','Đặt sân và lịch chơi','heheehhe','NEW',NULL,NULL,NULL,'2026-11-09 04:52:56.000','2026-11-09 04:52:56.000'),(4,'duongNT','admin@gmail.com','0987654321','Đặt sân và lịch chơi','4234324','NEW',NULL,NULL,NULL,'2026-11-09 04:54:19.000','2026-11-09 04:54:19.000'),(5,'duongNT','admin@gmail.com','0978097410','Đặt sân và lịch chơi','fafs','NEW',NULL,NULL,NULL,'2026-11-09 04:55:50.000','2026-11-09 04:55:50.000');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court_closures`
--

DROP TABLE IF EXISTS `court_closures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `court_closures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `court_id` bigint unsigned NOT NULL,
  `starts_at` datetime(3) NOT NULL,
  `ends_at` datetime(3) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `court_closures_created_by_foreign` (`created_by`),
  KEY `court_closures_court_id_starts_at_ends_at_index` (`court_id`,`starts_at`,`ends_at`),
  CONSTRAINT `court_closures_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `court_closures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_court_closures_interval` CHECK ((`starts_at` < `ends_at`))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court_closures`
--

LOCK TABLES `court_closures` WRITE;
/*!40000 ALTER TABLE `court_closures` DISABLE KEYS */;
INSERT INTO `court_closures` VALUES (1,3,'2026-11-02 22:00:00.000','2026-11-03 03:30:00.000','bảo trì',1,'2026-11-03 17:53:28.000','2026-11-03 17:53:28.000'),(2,3,'2026-11-04 08:30:00.000','2026-11-04 13:30:00.000',NULL,1,'2026-11-04 03:47:41.000','2026-11-04 03:47:41.000');
/*!40000 ALTER TABLE `court_closures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court_images`
--

DROP TABLE IF EXISTS `court_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `court_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `court_id` bigint unsigned NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `court_images_court_id_sort_order_index` (`court_id`,`sort_order`),
  CONSTRAINT `court_images_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court_images`
--

LOCK TABLES `court_images` WRITE;
/*!40000 ALTER TABLE `court_images` DISABLE KEYS */;
INSERT INTO `court_images` VALUES (1,1,'/images/pickleball/court-rally-wide.jpg','Sân 1 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.449'),(2,2,'/images/pickleball/court-community.jpg','Sân 2 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.504'),(3,3,'/images/pickleball/player-action.jpg','Sân 3 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.552'),(4,4,'/images/pickleball/paddle-court.jpg','Sân 4 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.584'),(5,5,'/images/pickleball/court-rally-wide.jpg','Sân 5 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.615'),(6,6,'/images/pickleball/court-community.jpg','Sân 6 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.643'),(7,7,'/images/pickleball/player-action.jpg','Sân 7 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.672'),(8,8,'/images/pickleball/paddle-court.jpg','Sân 8 tại BALLBURN PickleClub & Bistro',0,1,'2026-11-03 17:42:42.705');
/*!40000 ALTER TABLE `court_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court_operating_hours`
--

DROP TABLE IF EXISTS `court_operating_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `court_operating_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `court_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `opens_at` time NOT NULL,
  `closes_at` time NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `court_operating_hours_court_id_day_of_week_index` (`court_id`,`day_of_week`),
  CONSTRAINT `court_operating_hours_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_court_hours_day_range` CHECK ((`day_of_week` between 1 and 7)),
  CONSTRAINT `chk_court_hours_interval` CHECK (((`is_closed` = 1) or (`opens_at` < `closes_at`)))
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court_operating_hours`
--

LOCK TABLES `court_operating_hours` WRITE;
/*!40000 ALTER TABLE `court_operating_hours` DISABLE KEYS */;
INSERT INTO `court_operating_hours` VALUES (15,1,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(16,1,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(17,1,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(18,1,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(19,1,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(20,1,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(21,1,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(22,2,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(23,2,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(24,2,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(25,2,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(26,2,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(27,2,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(28,2,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(29,3,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(30,3,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(31,3,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(32,3,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(33,3,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(34,3,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(35,3,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(36,4,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(37,4,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(38,4,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(39,4,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(40,4,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(41,4,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(42,4,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(43,5,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(44,5,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(45,5,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(46,5,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(47,5,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(48,5,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(49,5,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(50,6,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(51,6,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(52,6,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(53,6,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(54,6,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(55,6,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(56,6,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(57,7,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(58,7,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(59,7,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(60,7,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(61,7,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(62,7,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(63,7,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(64,8,1,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(65,8,2,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(66,8,3,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(67,8,4,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(68,8,5,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(69,8,6,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(70,8,7,'05:00:00','23:00:00',0,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000');
/*!40000 ALTER TABLE `court_operating_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `court_price_rules`
--

DROP TABLE IF EXISTS `court_price_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `court_price_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `court_id` bigint unsigned NOT NULL,
  `day_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` time NOT NULL,
  `ends_at` time NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `effective_from` date NOT NULL,
  `effective_until` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_court_price_rule_start` (`court_id`,`day_type`,`starts_at`,`effective_from`),
  KEY `idx_court_price_rules_lookup` (`court_id`,`day_type`,`effective_from`,`effective_until`),
  CONSTRAINT `court_price_rules_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `court_price_rules`
--

LOCK TABLES `court_price_rules` WRITE;
/*!40000 ALTER TABLE `court_price_rules` DISABLE KEYS */;
INSERT INTO `court_price_rules` VALUES (1,1,'WEEKDAY','05:00:00','08:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(2,1,'WEEKDAY','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(3,1,'WEEKDAY','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(4,1,'WEEKDAY','14:00:00','16:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(5,1,'WEEKDAY','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(6,1,'WEEKEND','05:00:00','08:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(7,1,'WEEKEND','08:00:00','12:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(8,1,'WEEKEND','12:00:00','14:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(9,1,'WEEKEND','14:00:00','16:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(10,1,'WEEKEND','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(11,2,'WEEKDAY','05:00:00','08:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(12,2,'WEEKDAY','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(13,2,'WEEKDAY','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(14,2,'WEEKDAY','14:00:00','16:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(15,2,'WEEKDAY','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(16,2,'WEEKEND','05:00:00','08:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(17,2,'WEEKEND','08:00:00','12:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(18,2,'WEEKEND','12:00:00','14:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(19,2,'WEEKEND','14:00:00','16:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(20,2,'WEEKEND','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(21,3,'WEEKDAY','05:00:00','08:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(22,3,'WEEKDAY','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(23,3,'WEEKDAY','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(24,3,'WEEKDAY','14:00:00','16:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(25,3,'WEEKDAY','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(26,3,'WEEKEND','05:00:00','08:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(27,3,'WEEKEND','08:00:00','12:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(28,3,'WEEKEND','12:00:00','14:00:00',300000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(29,3,'WEEKEND','14:00:00','16:00:00',350000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(30,3,'WEEKEND','16:00:00','23:00:00',400000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(31,4,'WEEKDAY','05:00:00','08:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(32,4,'WEEKDAY','08:00:00','12:00:00',190000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(33,4,'WEEKDAY','12:00:00','14:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(34,4,'WEEKDAY','14:00:00','16:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(35,4,'WEEKDAY','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(36,4,'WEEKEND','05:00:00','08:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(37,4,'WEEKEND','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(38,4,'WEEKEND','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(39,4,'WEEKEND','14:00:00','16:00:00',270000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(40,4,'WEEKEND','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(41,5,'WEEKDAY','05:00:00','08:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(42,5,'WEEKDAY','08:00:00','12:00:00',190000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(43,5,'WEEKDAY','12:00:00','14:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(44,5,'WEEKDAY','14:00:00','16:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(45,5,'WEEKDAY','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(46,5,'WEEKEND','05:00:00','08:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(47,5,'WEEKEND','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(48,5,'WEEKEND','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(49,5,'WEEKEND','14:00:00','16:00:00',270000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(50,5,'WEEKEND','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(51,6,'WEEKDAY','05:00:00','08:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(52,6,'WEEKDAY','08:00:00','12:00:00',190000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(53,6,'WEEKDAY','12:00:00','14:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(54,6,'WEEKDAY','14:00:00','16:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(55,6,'WEEKDAY','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(56,6,'WEEKEND','05:00:00','08:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(57,6,'WEEKEND','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(58,6,'WEEKEND','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(59,6,'WEEKEND','14:00:00','16:00:00',270000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(60,6,'WEEKEND','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(61,7,'WEEKDAY','05:00:00','08:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(62,7,'WEEKDAY','08:00:00','12:00:00',190000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(63,7,'WEEKDAY','12:00:00','14:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(64,7,'WEEKDAY','14:00:00','16:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(65,7,'WEEKDAY','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(66,7,'WEEKEND','05:00:00','08:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(67,7,'WEEKEND','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(68,7,'WEEKEND','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(69,7,'WEEKEND','14:00:00','16:00:00',270000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(70,7,'WEEKEND','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(71,8,'WEEKDAY','05:00:00','08:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(72,8,'WEEKDAY','08:00:00','12:00:00',190000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(73,8,'WEEKDAY','12:00:00','14:00:00',140000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(74,8,'WEEKDAY','14:00:00','16:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(75,8,'WEEKDAY','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(76,8,'WEEKEND','05:00:00','08:00:00',220000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(77,8,'WEEKEND','08:00:00','12:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(78,8,'WEEKEND','12:00:00','14:00:00',250000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(79,8,'WEEKEND','14:00:00','16:00:00',270000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(80,8,'WEEKEND','16:00:00','23:00:00',299000.00,'VND','2026-04-01',NULL,1,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000');
/*!40000 ALTER TABLE `court_price_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courts`
--

DROP TABLE IF EXISTS `courts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `surface_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `court_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGULAR',
  `capacity` smallint unsigned DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courts_slug_unique` (`slug`),
  KEY `courts_is_active_sort_order_index` (`is_active`,`sort_order`),
  KEY `courts_court_type_index` (`court_type`),
  CONSTRAINT `chk_courts_capacity_positive` CHECK (((`capacity` is null) or (`capacity` > 0))),
  CONSTRAINT `chk_courts_price_nonnegative` CHECK ((`price_per_hour` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courts`
--

LOCK TABLES `courts` WRITE;
/*!40000 ALTER TABLE `courts` DISABLE KEYS */;
INSERT INTO `courts` VALUES (1,'Sân 1','san-1','Sân trung tâm dành cho trận đấu nổi bật, giải giao lưu và sự kiện câu lạc bộ; góc nhìn rộng, gần khu lounge và hệ thống VAR.','Mái che · Mặt acrylic','CENTER',4,250000.00,'VND',1,1,'2026-11-03 17:23:10.000','2026-11-03 17:42:42.000'),(2,'Sân 2','san-2','Sân trung tâm tiêu chuẩn thi đấu với mặt acrylic êm chân, ánh sáng đồng đều và khu vực chờ thuận tiện cho đội nhóm.','Mái che · Mặt acrylic','CENTER',4,250000.00,'VND',1,2,'2026-11-03 17:26:13.000','2026-11-03 17:42:42.000'),(3,'Sân 3','san-3','Sân trung tâm có vị trí đẹp trong cụm sân, phù hợp tổ chức lớp nâng cao, trận đấu ghi hình và hoạt động cộng đồng.','Mái che · Mặt acrylic','CENTER',4,250000.00,'VND',1,3,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(4,'Sân 4','san-4','Sân thường thân thiện cho người mới, nhóm bạn và các buổi tập kỹ thuật; có mái che và hệ thống làm mát.','Mái che · Mặt acrylic','REGULAR',4,140000.00,'VND',1,4,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(5,'Sân 5','san-5','Sân thường dành cho lịch chơi hằng tuần, mặt sân bám tốt và khoảng trống an toàn quanh đường biên.','Mái che · Mặt acrylic','REGULAR',4,140000.00,'VND',1,5,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(6,'Sân 6','san-6','Sân thường phù hợp lớp học, luyện đôi và nhóm nhỏ; thuận tiện tiếp cận khu thay đồ và quầy dịch vụ.','Mái che · Mặt acrylic','REGULAR',4,140000.00,'VND',1,6,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(7,'Sân 7','san-7','Sân thường có không gian thoáng, phù hợp gia đình, hội bạn và những buổi giao lưu pickleball cuối tuần.','Mái che · Mặt acrylic','REGULAR',4,140000.00,'VND',1,7,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000'),(8,'Sân 8','san-8','Sân thường nằm gần khu nghỉ, thích hợp cho trận vui, hoạt động cộng đồng và khách mới trải nghiệm BALLBURN.','Mái che · Mặt acrylic','REGULAR',4,140000.00,'VND',1,8,'2026-11-03 17:42:42.000','2026-11-03 17:42:42.000');
/*!40000 ALTER TABLE `courts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(5,'2026_11_02_000001_add_role_to_users_table',1),(6,'2026_11_02_000003_add_is_active_to_users_table',2),(7,'2026_11_02_000002_create_pickleball_booking_tables',3),(8,'2026_11_02_000004_add_seo_fields_to_blog_posts',3),(9,'2026_11_04_000001_add_court_types_and_price_rules',4),(10,'2026_11_04_000002_create_bank_transfer_transactions_table',5),(11,'2026_11_05_000002_add_proof_image_path_to_payments_table',6),(12,'2026_11_05_000003_create_booking_orders_table',7),(13,'2026_11_06_000001_remove_duplicate_booking_order_status',8),(14,'2026_11_07_000001_add_booking_order_status_and_holds',8),(15,'2026_11_08_000001_remove_legacy_booking_workflow_columns',9),(16,'2026_11_08_000002_add_paid_at_to_booking_orders_table',10),(17,'2026_11_08_000003_clear_estimated_legacy_paid_at',11),(18,'2026_11_08_000004_make_guest_booking_email_optional',12),(19,'2026_11_08_000005_add_read_at_to_admin_notifications',13),(20,'2026_11_08_000006_defer_guest_orders_until_payment_proof',14),(21,'2026_11_09_000001_create_payment_receipts_table',15);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('EMAIL','SMS','IN_APP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMAIL',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PENDING','PROCESSING','SENT','FAILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `idempotency_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `scheduled_at` datetime(3) DEFAULT NULL,
  `sent_at` datetime(3) DEFAULT NULL,
  `read_at` datetime(3) DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notifications_idempotency_key_unique` (`idempotency_key`),
  KEY `notifications_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `notifications_booking_id_index` (`booking_id`),
  KEY `notifications_user_id_index` (`user_id`),
  CONSTRAINT `notifications_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,20,1,'admin@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-TZ3L4B của duong đang chờ thanh toán.','SENT','booking-pending-order-16-user-1',0,'2026-11-05 09:14:49.000','2026-11-05 09:14:49.000','2026-11-05 09:21:30.000',NULL,'2026-11-05 09:14:49.000','2026-11-05 09:21:30.000'),(2,20,2,'admin2@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-TZ3L4B của duong đang chờ thanh toán.','SENT','booking-pending-order-16-user-2',0,'2026-11-05 09:14:49.000','2026-11-05 09:14:49.000',NULL,NULL,'2026-11-05 09:14:49.000','2026-11-05 09:14:49.000'),(3,21,1,'admin@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-1EKODH của duong đang chờ thanh toán.','SENT','booking-pending-order-17-user-1',0,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000',NULL,NULL,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000'),(4,21,2,'admin2@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-1EKODH của duong đang chờ thanh toán.','SENT','booking-pending-order-17-user-2',0,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000',NULL,NULL,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000'),(5,22,1,'admin@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-LZ0XZN của duong đang chờ thanh toán.','SENT','booking-pending-order-18-user-1',0,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000',NULL,NULL,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000'),(6,22,2,'admin2@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261105-LZ0XZN của duong đang chờ thanh toán.','SENT','booking-pending-order-18-user-2',0,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000',NULL,NULL,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000'),(7,23,1,'admin@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261109-EMKZVC của duong đang chờ thanh toán.','SENT','booking-pending-order-19-user-1',0,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000',NULL,NULL,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000'),(8,23,2,'admin2@gmail.com','IN_APP','BOOKING_PENDING_PAYMENT','Đơn đặt sân mới','Đơn BB-20261109-EMKZVC của duong đang chờ thanh toán.','SENT','booking-pending-order-19-user-2',0,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000',NULL,NULL,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','PUBLISHED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `published_at` datetime(3) DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_updated_by_foreign` (`updated_by`),
  CONSTRAINT `pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_receipts`
--

DROP TABLE IF EXISTS `payment_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'QUEUED',
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `ocr_raw_text` longtext COLLATE utf8mb4_unicode_ci,
  `extracted_data` json DEFAULT NULL,
  `mapping_result` json DEFAULT NULL,
  `ocr_error` text COLLATE utf8mb4_unicode_ci,
  `processing_started_at` datetime(3) DEFAULT NULL,
  `processed_at` datetime(3) DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_receipts_payment_id_unique` (`payment_id`),
  KEY `payment_receipts_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `payment_receipts_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_receipts`
--

LOCK TABLES `payment_receipts` WRITE;
/*!40000 ALTER TABLE `payment_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_webhook_events`
--

DROP TABLE IF EXISTS `payment_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_event_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `processing_status` enum('RECEIVED','PROCESSING','PROCESSED','FAILED','IGNORED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RECEIVED',
  `received_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `processed_at` datetime(3) DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_webhook_provider_event` (`provider`,`provider_event_id`),
  KEY `payment_webhook_events_processing_status_received_at_index` (`processing_status`,`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_webhook_events`
--

LOCK TABLES `payment_webhook_events` WRITE;
/*!40000 ALTER TABLE `payment_webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_order_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_payload` text COLLATE utf8mb4_unicode_ci,
  `checkout_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_metadata` json DEFAULT NULL,
  `proof_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime(3) DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_provider_transaction` (`provider`,`provider_transaction_id`),
  UNIQUE KEY `uq_payment_provider_order` (`provider`,`provider_order_id`),
  KEY `payments_booking_id_index` (`booking_id`),
  CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_payments_amount_nonnegative` CHECK ((`amount` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (2,6,'MANUAL_QR',NULL,NULL,200000.00,'VND','BANK_TRANSFER','BBPAY-5Y1YSZP0KR',NULL,'{\"payment_reference\": \"BBPAY-5Y1YSZP0KR\"}',NULL,NULL,'2026-11-04 07:56:54.000','2026-11-04 07:56:54.000'),(3,7,'BANK_TRANSFER','1',NULL,400000.00,'VND','BANK_TRANSFER','BBPAY-NZ1AFLGFGG',NULL,'{\"recorded_by\": 1, \"review_note\": null, \"payment_reference\": \"BBPAY-NZ1AFLGFGG\", \"transfer_description\": null}','booking-payment-proofs/UUzb0zXOQzXZY5dJlWBDOjTxFzWxymwLFdD0BZ5p.png','2026-11-04 16:22:00.000','2026-11-04 08:07:38.000','2026-11-04 16:26:19.000'),(5,9,'MANUAL_QR',NULL,NULL,600000.00,'VND','BANK_TRANSFER','BBPAY-DEWFKSXE7U',NULL,'{\"payment_reference\": \"BBPAY-DEWFKSXE7U\", \"booking_order_code\": \"BB-20261104-VDVXZW\"}','booking-payment-proofs/vilvyoksRuOIDOGjGE9djX6HNBMnNJXgJQZuyvPd.png',NULL,'2026-11-04 08:30:02.000','2026-11-04 16:12:26.000'),(9,14,'MANUAL_QR',NULL,NULL,800000.00,'VND','BANK_TRANSFER','BBPAY-JPMPLDJI1R',NULL,'{\"payment_reference\": \"BBPAY-JPMPLDJI1R\", \"booking_order_code\": \"BB-20261105-JOMC15\"}','booking-payment-proofs/c9fRvxcvItBbr6YzCYDJY1UwEIhjB09iTIc6sxJ1.png',NULL,'2026-11-05 08:12:08.000','2026-11-05 08:12:32.000'),(10,16,'MANUAL_QR',NULL,NULL,1798000.00,'VND','BANK_TRANSFER','BBPAY-GMYLQDXCAU',NULL,'{\"payment_reference\": \"BBPAY-GMYLQDXCAU\", \"booking_order_code\": \"BB-20261105-NQQCP8\"}','booking-payment-proofs/vI4qLepzFF4IPszHKyRMB2RFPPvflHKDF1cRnMzh.png',NULL,'2026-11-05 08:30:26.000','2026-11-05 08:31:54.000'),(11,20,'MANUAL_QR',NULL,NULL,400000.00,'VND','BANK_TRANSFER','BBPAY-ADYY3YYGBG',NULL,'{\"payment_reference\": \"BBPAY-ADYY3YYGBG\", \"booking_order_code\": \"BB-20261105-TZ3L4B\"}',NULL,NULL,'2026-11-05 09:14:50.000','2026-11-05 09:14:50.000'),(12,21,'MANUAL_QR',NULL,NULL,400000.00,'VND','BANK_TRANSFER','BBPAY-9UACIQVWKE',NULL,'{\"payment_reference\": \"BBPAY-9UACIQVWKE\", \"booking_order_code\": \"BB-20261105-1EKODH\"}',NULL,NULL,'2026-11-05 09:22:32.000','2026-11-05 09:22:32.000'),(13,22,'MANUAL_QR',NULL,NULL,400000.00,'VND','BANK_TRANSFER','BBPAY-KQNLPOGIMT',NULL,'{\"payment_reference\": \"BBPAY-KQNLPOGIMT\", \"booking_order_code\": \"BB-20261105-LZ0XZN\"}','booking-payment-proofs/NS5QYsZVbkW1MVLXBowMbejkF6Ur8d0iyPFe7bM9.png',NULL,'2026-11-05 09:56:50.000','2026-11-05 09:56:50.000'),(14,23,'MANUAL_QR',NULL,NULL,400000.00,'VND','BANK_TRANSFER','BBPAYOFUL0U9POW',NULL,'{\"payment_reference\": \"BBPAYOFUL0U9POW\", \"booking_order_code\": \"BB-20261109-EMKZVC\"}','booking-payment-proofs/r5KSAQK2l7T2Qq9ld6UlBB6Q8ijJOmGOFZv5B9wx.jpg',NULL,'2026-11-09 09:12:14.000','2026-11-09 09:12:14.000');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `provider_refund_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `status` enum('PENDING','PROCESSING','SUCCEEDED','FAILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_by` bigint unsigned DEFAULT NULL,
  `requested_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `completed_at` datetime(3) DEFAULT NULL,
  `provider_metadata` json DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_provider_refund_id_unique` (`provider_refund_id`),
  KEY `refunds_requested_by_foreign` (`requested_by`),
  KEY `refunds_payment_id_status_index` (`payment_id`,`status`),
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `refunds_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_refunds_amount_positive` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('BhZcPDvudQsTSnKSzt06UzhiTihANdNghpUhRy0z',NULL,'172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.19045; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI2SEhWNVRkWndCbXlBN2hvVXJoV2szelpCVThOeHZ2T1FVdHJHSjl5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1794218279),('GSSKHKPB5Yim9YKp3wx57xb1d9TRbpXnX9KV3e36',1,'172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJNQVI0QmVuUmVadHNWcU50aU11UWwzcnNUczJWZXBsdnFnSUNmM3dMIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYm9va2luZ1wvZGF0YT9kYXRlPTIwMjYtMTEtMDkiLCJyb3V0ZSI6ImJvb2tpbmcuYXZhaWxhYmlsaXR5In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1794218531),('RIQQaeCkHr1jeMkpG4UWug1kRP9aSZ2xfZv4aNQ0',NULL,'172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.19045; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJ3eUZwTEZGZnZ2SU92TkVtVWFGSHZnU3N6cks2bTh3azJqaGd0ajhIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9ib29raW5nXC9kYXRhP2RhdGU9MjAyNi0xMS0wOSIsInJvdXRlIjoiYm9va2luZy5hdmFpbGFiaWxpdHkifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794218280),('ZO8eozezPciQV6j9TVmeooxOBGJid5EmlKqXrHfX',NULL,'172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.19045; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI0OFhscGdRamd2T1o2alRnaENIUU9yd1pKRW1NT3NOV0h6MkFaQjVuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1794218201);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` json DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp(3) NULL DEFAULT NULL,
  `updated_at` timestamp(3) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_settings_setting_key_unique` (`setting_key`),
  KEY `site_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `site_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'contact_info','{\"email\": \"admin@ballburn.vn\", \"phone\": \"0886 688 614\", \"address\": \"614 Lạc Long Quân, Tây Hồ, Hà Nội 100000\", \"map_url\": \"https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9\", \"facebook_url\": \"https://www.facebook.com/ballburnvietnam\", \"instagram_url\": \"https://www.instagram.com/ballburnpickleclubbistro\", \"business_hours\": \"Mỗi ngày · 05:00–24:00\", \"venue_description\": \"8 sân pickleball và khu Bistro luôn sẵn sàng đón bạn.\", \"payment_account_number\": \"15081999368\"}','Public website contact details and VietQR payment account',1,'2026-11-09 04:55:18.000','2026-11-09 08:58:27.000');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@gmail.com',NULL,'$2y$12$FcwS/DJBEAVcTtemcxuLa.QqyzuJkicAXYDI5G4ZJSpEQ.fa12GEW',NULL,NULL,NULL,'iZIjBaDgaH1FuLhW7pLhrvfvt7ntShaQVHrU638cDEFMnnaL9AJufioRNcYS','2026-11-02 03:59:53','2026-11-02 03:59:53','admin',1),(2,'admin2','admin2@gmail.com',NULL,'$2y$12$EHFi05.j4npvFctO4yzNV.ukfACSMhK0eRaNQJ1jOnC9x2ItQakQC',NULL,NULL,NULL,NULL,'2026-11-02 08:08:35','2026-11-02 08:08:35','admin',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'laravel'
--

--
-- Dumping routines for database 'laravel'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-09 10:02:23
