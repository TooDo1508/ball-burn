<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_holds') && ! Schema::hasColumn('booking_holds', 'payment_reference')) {
            Schema::table('booking_holds', function (Blueprint $table): void {
                $table->string('payment_reference', 32)->nullable()->after('token_hash');
            });
        }

        if (Schema::hasTable('booking_orders') && ! Schema::hasColumn('booking_orders', 'hold_token_hash')) {
            Schema::table('booking_orders', function (Blueprint $table): void {
                $table->char('hold_token_hash', 64)->nullable()->unique('booking_orders_hold_token_hash_unique')->after('status_token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_orders') && Schema::hasColumn('booking_orders', 'hold_token_hash')) {
            Schema::table('booking_orders', function (Blueprint $table): void {
                $table->dropUnique('booking_orders_hold_token_hash_unique');
                $table->dropColumn('hold_token_hash');
            });
        }

        if (Schema::hasTable('booking_holds') && Schema::hasColumn('booking_holds', 'payment_reference')) {
            Schema::table('booking_holds', function (Blueprint $table): void {
                $table->dropColumn('payment_reference');
            });
        }
    }
};
