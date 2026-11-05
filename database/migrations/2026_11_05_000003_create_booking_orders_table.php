<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_code', 32)->unique();
            $table->char('status_token_hash', 64)->unique();
            $table->string('customer_name', 150);
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();
            $table->dateTime('payment_deadline_at', 3)->nullable();
            $table->decimal('base_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->string('customer_note', 1000)->nullable();
            $table->dateTime('confirmed_at', 3)->nullable();
            $table->dateTime('cancelled_at', 3)->nullable();
            $table->timestamps(3);
            $table->index('payment_deadline_at');
            $table->index('customer_email');
            $table->index('created_at');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('booking_order_id')->nullable()->after('id')->constrained('booking_orders')->restrictOnDelete();
            $table->index(['booking_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('booking_order_id');
        });
        Schema::dropIfExists('booking_orders');
    }
};
