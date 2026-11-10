<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE booking_orders MODIFY status ENUM('PENDING_PAYMENT','PAID','CANCELLED') NOT NULL DEFAULT 'PENDING_PAYMENT'");
    }

    public function down(): void
    {
        if (DB::table('booking_orders')->where('status', 'CANCELLED')->exists()) {
            throw new RuntimeException('Cancelled booking orders must be reconciled before removing the CANCELLED status.');
        }

        DB::statement("ALTER TABLE booking_orders MODIFY status ENUM('PENDING_PAYMENT','PAID') NOT NULL DEFAULT 'PENDING_PAYMENT'");
    }
};
