-- Pickleball Court Booking Website
-- Database: MySQL 8.0.16+
-- Timestamps: store UTC in DATETIME(3); convert to/from the venue's timezone in Laravel.
-- Run this script against a NEW, EMPTY database. Back up production data before applying schema changes.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create the database separately if needed:
-- CREATE DATABASE pickleball_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE pickleball_booking;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'STAFF') NOT NULL DEFAULT 'STAFF',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    email_verified_at DATETIME(3) NULL,
    remember_token VARCHAR(100) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE courts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    surface_type VARCHAR(80) NULL,
    capacity SMALLINT UNSIGNED NULL,
    price_per_hour DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_courts_slug (slug),
    KEY idx_courts_active_sort (is_active, sort_order),
    CONSTRAINT chk_courts_price_nonnegative CHECK (price_per_hour >= 0),
    CONSTRAINT chk_courts_capacity_positive CHECK (capacity IS NULL OR capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE court_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    court_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_court_images_court_sort (court_id, sort_order),
    CONSTRAINT fk_court_images_court FOREIGN KEY (court_id)
        REFERENCES courts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- day_of_week: 1=Monday ... 7=Sunday.
-- Use one row per court/day/time interval. Prevent overlapping intervals in Laravel validation.
CREATE TABLE court_operating_hours (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    court_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    opens_at TIME NOT NULL,
    closes_at TIME NOT NULL,
    is_closed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_operating_hours_court_day (court_id, day_of_week),
    CONSTRAINT fk_operating_hours_court FOREIGN KEY (court_id)
        REFERENCES courts(id) ON DELETE CASCADE,
    CONSTRAINT chk_operating_day CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT chk_operating_times CHECK (is_closed = TRUE OR opens_at < closes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Blocks a court for maintenance, holidays, private events, etc.
CREATE TABLE court_closures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    court_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME(3) NOT NULL,
    ends_at DATETIME(3) NOT NULL,
    reason VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_closures_court_range (court_id, starts_at, ends_at),
    CONSTRAINT fk_closures_court FOREIGN KEY (court_id)
        REFERENCES courts(id) ON DELETE RESTRICT,
    CONSTRAINT fk_closures_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_closures_range CHECK (starts_at < ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_code VARCHAR(32) NOT NULL,
    -- Store a high-entropy random token hash for the guest status page; never expose sequential IDs.
    status_token_hash CHAR(64) NULL,
    court_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    starts_at DATETIME(3) NOT NULL,
    ends_at DATETIME(3) NOT NULL,
    status ENUM('PENDING_PAYMENT', 'CONFIRMED', 'EXPIRED', 'CANCELLED', 'COMPLETED')
        NOT NULL DEFAULT 'PENDING_PAYMENT',
    payment_deadline_at DATETIME(3) NULL,
    base_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    customer_note VARCHAR(1000) NULL,
    cancellation_reason VARCHAR(255) NULL,
    confirmed_at DATETIME(3) NULL,
    cancelled_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_bookings_code (booking_code),
    UNIQUE KEY uq_bookings_status_token_hash (status_token_hash),
    KEY idx_bookings_court_schedule (court_id, starts_at, ends_at, status),
    KEY idx_bookings_status_deadline (status, payment_deadline_at),
    KEY idx_bookings_customer_email (customer_email),
    KEY idx_bookings_created_at (created_at),
    CONSTRAINT fk_bookings_court FOREIGN KEY (court_id)
        REFERENCES courts(id) ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_range CHECK (starts_at < ends_at),
    CONSTRAINT chk_bookings_amounts CHECK (
        base_amount >= 0 AND discount_amount >= 0 AND total_amount >= 0
        AND discount_amount <= base_amount
        AND total_amount = base_amount - discount_amount
    ),
    CONSTRAINT chk_pending_booking_deadline CHECK (
        status <> 'PENDING_PAYMENT' OR payment_deadline_at IS NOT NULL
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_transaction_id VARCHAR(191) NULL,
    provider_order_id VARCHAR(191) NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    status ENUM('PENDING', 'PAID', 'FAILED', 'EXPIRED', 'REFUND_PENDING', 'REFUNDED')
        NOT NULL DEFAULT 'PENDING',
    payment_method VARCHAR(50) NULL,
    qr_payload TEXT NULL,
    checkout_url VARCHAR(1000) NULL,
    provider_metadata JSON NULL,
    paid_at DATETIME(3) NULL,
    failed_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_payments_booking_status (booking_id, status),
    UNIQUE KEY uq_payment_provider_transaction (provider, provider_transaction_id),
    UNIQUE KEY uq_payment_provider_order (provider, provider_order_id),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE RESTRICT,
    CONSTRAINT chk_payments_amount CHECK (amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE payment_webhook_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(50) NOT NULL,
    provider_event_id VARCHAR(191) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    payload JSON NULL,
    processing_status ENUM('RECEIVED', 'PROCESSING', 'PROCESSED', 'FAILED', 'IGNORED')
        NOT NULL DEFAULT 'RECEIVED',
    received_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    processed_at DATETIME(3) NULL,
    last_error TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_webhook_provider_event (provider, provider_event_id),
    KEY idx_webhook_processing (processing_status, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE refunds (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,
    provider_refund_id VARCHAR(191) NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    status ENUM('PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED', 'CANCELLED')
        NOT NULL DEFAULT 'PENDING',
    reason VARCHAR(500) NULL,
    requested_by BIGINT UNSIGNED NULL,
    requested_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    completed_at DATETIME(3) NULL,
    provider_metadata JSON NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_refunds_provider_refund (provider_refund_id),
    KEY idx_refunds_payment_status (payment_id, status),
    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id)
        REFERENCES payments(id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_requested_by FOREIGN KEY (requested_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_refunds_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE booking_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    changed_by BIGINT UNSIGNED NULL,
    change_source ENUM('SYSTEM', 'ADMIN', 'PAYMENT_WEBHOOK', 'CUSTOMER') NOT NULL DEFAULT 'SYSTEM',
    note VARCHAR(500) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_booking_history_booking_time (booking_id, created_at),
    CONSTRAINT fk_booking_history_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_history_user FOREIGN KEY (changed_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE blog_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description VARCHAR(500) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_categories_slug (slug),
    UNIQUE KEY uq_blog_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE blog_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    author_id BIGINT UNSIGNED NULL,
    title VARCHAR(220) NOT NULL,
    slug VARCHAR(240) NOT NULL,
    excerpt VARCHAR(1000) NULL,
    body LONGTEXT NOT NULL,
    cover_image_path VARCHAR(500) NULL,
    status ENUM('DRAFT', 'PUBLISHED', 'ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    published_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_posts_slug (slug),
    KEY idx_blog_posts_status_published (status, published_at),
    KEY idx_blog_posts_author (author_id),
    CONSTRAINT fk_blog_posts_author FOREIGN KEY (author_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE blog_post_categories (
    post_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    KEY idx_post_categories_category (category_id, post_id),
    CONSTRAINT fk_post_categories_post FOREIGN KEY (post_id)
        REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_categories_category FOREIGN KEY (category_id)
        REFERENCES blog_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    body LONGTEXT NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    status ENUM('DRAFT', 'PUBLISHED') NOT NULL DEFAULT 'DRAFT',
    published_at DATETIME(3) NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug (slug),
    CONSTRAINT fk_pages_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE site_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(150) NOT NULL,
    setting_value JSON NULL,
    description VARCHAR(500) NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_site_settings_key (setting_key),
    CONSTRAINT fk_site_settings_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(200) NULL,
    message TEXT NOT NULL,
    status ENUM('NEW', 'IN_PROGRESS', 'RESOLVED', 'SPAM') NOT NULL DEFAULT 'NEW',
    admin_notes TEXT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    resolved_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_contact_status_created (status, created_at),
    KEY idx_contact_email (email),
    KEY idx_contact_assigned_to (assigned_to),
    CONSTRAINT fk_contact_assigned_to FOREIGN KEY (assigned_to)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    recipient VARCHAR(255) NOT NULL,
    channel ENUM('EMAIL', 'SMS', 'IN_APP') NOT NULL DEFAULT 'EMAIL',
    type VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    status ENUM('PENDING', 'PROCESSING', 'SENT', 'FAILED', 'CANCELLED')
        NOT NULL DEFAULT 'PENDING',
    idempotency_key VARCHAR(191) NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    scheduled_at DATETIME(3) NULL,
    sent_at DATETIME(3) NULL,
    last_error TEXT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_notifications_idempotency (idempotency_key),
    KEY idx_notifications_status_schedule (status, scheduled_at),
    KEY idx_notifications_booking (booking_id),
    KEY idx_notifications_user (user_id),
    CONSTRAINT fk_notifications_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id VARCHAR(100) NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_user_time (user_id, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Laravel queue tables are intentionally not duplicated here.
-- Generate them with Laravel's queue migration commands if you use the database queue driver.
-- Also run php artisan migrate after creating your Laravel migrations if your app uses migrations.
--
-- Important application-level rules:
-- 1. Enforce 30-minute slot boundaries and the rolling 30-day booking window in Laravel.
-- 2. On booking creation/payment confirmation, use a DB transaction and lock the court row
--    (SELECT ... FOR UPDATE) so concurrent requests cannot reserve overlapping time ranges.
--    MySQL has no simple UNIQUE constraint for arbitrary overlapping DATETIME ranges.
-- 3. Treat PENDING_PAYMENT bookings whose payment_deadline_at <= UTC_TIMESTAMP(3) as expired
--    in availability checks, even if the scheduled expiry job has not run yet.
-- 4. Set payment_deadline_at to the backend-created time + 5 minutes. The frontend countdown
--    displays this stored deadline; it is not the authority for expiry.
-- 5. Verify webhook signatures, provider, transaction ID, amount, and currency. Make webhook
--    processing idempotent using uq_webhook_provider_event and payment transaction uniqueness.
-- 6. A late payment must not confirm a slot already booked by someone else; reconcile/refund it.
-- 7. Store only a hash of a high-entropy random status-page token in bookings.status_token_hash.
--    Send the raw token only in the guest's secure link; do not use booking_code alone as access.
-- 8. Queue notification emails after the booking/payment transaction commits; never send email
--    while holding database locks.
-- 9. Add CAPTCHA or equivalent anti-abuse protection and rate limiting to contact form endpoints.
-- 10. Review retention, privacy, and payment-provider requirements before production deployment.
