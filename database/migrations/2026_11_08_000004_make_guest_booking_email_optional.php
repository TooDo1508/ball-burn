<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('customer_email')->nullable()->change();
        });

        Schema::table('booking_orders', function (Blueprint $table): void {
            $table->string('customer_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->whereNull('customer_email')->update(['customer_email' => '']);
        DB::table('booking_orders')->whereNull('customer_email')->update(['customer_email' => '']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('customer_email')->nullable(false)->change();
        });

        Schema::table('booking_orders', function (Blueprint $table): void {
            $table->string('customer_email')->nullable(false)->change();
        });
    }
};
