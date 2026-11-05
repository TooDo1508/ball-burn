<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kept as a no-op for databases that have not run this migration yet.
        // The next migration normalizes the existing column to the two approved order states.
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_orders') || Schema::hasColumn('booking_orders', 'status')) {
            return;
        }

        Schema::table('booking_orders', function (Blueprint $table): void {
            $table->enum('status', ['PENDING_PAYMENT', 'CONFIRMED', 'EXPIRED', 'CANCELLED', 'COMPLETED'])
                ->default('PENDING_PAYMENT')
                ->after('customer_phone');
            $table->index(['status', 'payment_deadline_at']);
        });

        DB::statement(<<<'SQL'
            UPDATE booking_orders AS orders
            SET orders.status = COALESCE(
                (SELECT segments.status FROM bookings AS segments WHERE segments.booking_order_id = orders.id ORDER BY segments.id LIMIT 1),
                'PENDING_PAYMENT'
            )
        SQL);
    }
};
