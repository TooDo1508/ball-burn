<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('surface_type', 80)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->decimal('price_per_hour', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps(3);
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('court_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at', 3)->useCurrent();
            $table->index(['court_id', 'sort_order']);
        });

        Schema::create('court_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps(3);
            $table->index(['court_id', 'day_of_week']);
        });

        Schema::create('court_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at', 3);
            $table->dateTime('ends_at', 3);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(3);
            $table->index(['court_id', 'starts_at', 'ends_at']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 32)->unique();
            $table->char('status_token_hash', 64)->nullable()->unique();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->string('customer_name', 150);
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();
            $table->dateTime('starts_at', 3);
            $table->dateTime('ends_at', 3);
            $table->enum('status', ['PENDING_PAYMENT', 'CONFIRMED', 'EXPIRED', 'CANCELLED', 'COMPLETED'])->default('PENDING_PAYMENT');
            $table->dateTime('payment_deadline_at', 3)->nullable();
            $table->decimal('base_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->string('customer_note', 1000)->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->dateTime('confirmed_at', 3)->nullable();
            $table->dateTime('cancelled_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['court_id', 'starts_at', 'ends_at', 'status']);
            $table->index(['status', 'payment_deadline_at']);
            $table->index('customer_email');
            $table->index('created_at');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 191)->nullable();
            $table->string('provider_order_id', 191)->nullable();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'EXPIRED', 'REFUND_PENDING', 'REFUNDED'])->default('PENDING');
            $table->string('payment_method', 50)->nullable();
            $table->text('qr_payload')->nullable();
            $table->string('checkout_url', 1000)->nullable();
            $table->json('provider_metadata')->nullable();
            $table->dateTime('paid_at', 3)->nullable();
            $table->dateTime('failed_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['booking_id', 'status']);
            $table->unique(['provider', 'provider_transaction_id'], 'uq_payment_provider_transaction');
            $table->unique(['provider', 'provider_order_id'], 'uq_payment_provider_order');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('provider_event_id', 191);
            $table->string('event_type', 120);
            $table->json('payload')->nullable();
            $table->enum('processing_status', ['RECEIVED', 'PROCESSING', 'PROCESSED', 'FAILED', 'IGNORED'])->default('RECEIVED');
            $table->dateTime('received_at', 3)->useCurrent();
            $table->dateTime('processed_at', 3)->nullable();
            $table->text('last_error')->nullable();
            $table->unique(['provider', 'provider_event_id'], 'uq_webhook_provider_event');
            $table->index(['processing_status', 'received_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('provider_refund_id', 191)->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->enum('status', ['PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED', 'CANCELLED'])->default('PENDING');
            $table->string('reason', 500)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at', 3)->useCurrent();
            $table->dateTime('completed_at', 3)->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps(3);
            $table->index(['payment_id', 'status']);
        });

        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('change_source', ['SYSTEM', 'ADMIN', 'PAYMENT_WEBHOOK', 'CUSTOMER'])->default('SYSTEM');
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at', 3)->useCurrent();
            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->string('description', 500)->nullable();
            $table->timestamps(3);
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 220);
            $table->string('slug', 240)->unique();
            $table->string('excerpt', 1000)->nullable();
            $table->longText('body');
            $table->string('cover_image_path', 500)->nullable();
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->dateTime('published_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['status', 'published_at']);
            $table->index('author_id');
        });

        Schema::create('blog_post_categories', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->primary(['post_id', 'category_id']);
            $table->index(['category_id', 'post_id']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->longText('body');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->enum('status', ['DRAFT', 'PUBLISHED'])->default('DRAFT');
            $table->dateTime('published_at', 3)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(3);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 150)->unique();
            $table->json('setting_value')->nullable();
            $table->string('description', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(3);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('message');
            $table->enum('status', ['NEW', 'IN_PROGRESS', 'RESOLVED', 'SPAM'])->default('NEW');
            $table->text('admin_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('assigned_to');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->enum('channel', ['EMAIL', 'SMS', 'IN_APP'])->default('EMAIL');
            $table->string('type', 100);
            $table->string('subject')->nullable();
            $table->text('content');
            $table->enum('status', ['PENDING', 'PROCESSING', 'SENT', 'FAILED', 'CANCELLED'])->default('PENDING');
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('scheduled_at', 3)->nullable();
            $table->dateTime('sent_at', 3)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps(3);
            $table->index(['status', 'scheduled_at']);
            $table->index('booking_id');
            $table->index('user_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120);
            $table->string('entity_type', 120);
            $table->string('entity_id', 100)->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at', 3)->useCurrent();
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        $this->addMySqlCheckConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('blog_post_categories');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('court_closures');
        Schema::dropIfExists('court_operating_hours');
        Schema::dropIfExists('court_images');
        Schema::dropIfExists('courts');
    }

    private function addMySqlCheckConstraints(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $constraints = [
            'ALTER TABLE courts ADD CONSTRAINT chk_courts_price_nonnegative CHECK (price_per_hour >= 0)',
            'ALTER TABLE courts ADD CONSTRAINT chk_courts_capacity_positive CHECK (capacity IS NULL OR capacity > 0)',
            'ALTER TABLE court_operating_hours ADD CONSTRAINT chk_court_hours_day_range CHECK (day_of_week BETWEEN 1 AND 7)',
            'ALTER TABLE court_operating_hours ADD CONSTRAINT chk_court_hours_interval CHECK (is_closed = 1 OR opens_at < closes_at)',
            'ALTER TABLE court_closures ADD CONSTRAINT chk_court_closures_interval CHECK (starts_at < ends_at)',
            'ALTER TABLE bookings ADD CONSTRAINT chk_bookings_interval CHECK (starts_at < ends_at)',
            'ALTER TABLE bookings ADD CONSTRAINT chk_bookings_amounts CHECK (base_amount >= 0 AND discount_amount >= 0 AND total_amount >= 0 AND discount_amount <= base_amount AND total_amount = base_amount - discount_amount)',
            "ALTER TABLE bookings ADD CONSTRAINT chk_bookings_pending_deadline CHECK (status <> 'PENDING_PAYMENT' OR payment_deadline_at IS NOT NULL)",
            'ALTER TABLE payments ADD CONSTRAINT chk_payments_amount_nonnegative CHECK (amount >= 0)',
            'ALTER TABLE refunds ADD CONSTRAINT chk_refunds_amount_positive CHECK (amount > 0)',
        ];

        foreach ($constraints as $constraint) {
            DB::statement($constraint);
        }
    }
};
