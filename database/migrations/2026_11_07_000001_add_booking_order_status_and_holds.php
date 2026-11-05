<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_orders') && ! Schema::hasColumn('booking_orders', 'status')) {
            Schema::table('booking_orders', function (Blueprint $table): void {
                $table->enum('status', ['PENDING_PAYMENT', 'PAID'])->default('PENDING_PAYMENT')->after('booking_code');
            });
        } elseif (Schema::hasTable('booking_orders')) {
            // Temporarily widen the legacy enum so old states can be mapped without data truncation.
            DB::statement("ALTER TABLE booking_orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'PENDING_PAYMENT'");
        }

        if (Schema::hasTable('booking_orders') && Schema::hasColumn('booking_orders', 'status') && Schema::hasTable('bookings')) {
            DB::table('booking_orders')->whereIn('status', ['CONFIRMED', 'COMPLETED', 'PAID'])->update(['status' => 'PAID']);
            DB::table('booking_orders as orders')->whereExists(function ($query): void {
                $query->selectRaw('1')->from('bookings as segments')->whereColumn('segments.booking_order_id', 'orders.id')
                    ->whereIn('segments.status', ['CONFIRMED', 'COMPLETED']);
            })->update(['status' => 'PAID']);
            DB::table('booking_orders as orders')->whereExists(function ($query): void {
                $query->selectRaw('1')->from('bookings as segments')
                    ->join('payments', 'payments.booking_id', '=', 'segments.id')
                    ->whereColumn('segments.booking_order_id', 'orders.id')->where('payments.status', 'PAID');
            })->update(['status' => 'PAID']);
            DB::table('booking_orders')->where('status', '<>', 'PAID')->update(['status' => 'PENDING_PAYMENT']);
            DB::statement("ALTER TABLE booking_orders MODIFY status ENUM('PENDING_PAYMENT','PAID') NOT NULL DEFAULT 'PENDING_PAYMENT'");
        }

        if (! Schema::hasTable('booking_holds')) {
            Schema::create('booking_holds', function (Blueprint $table): void {
                $table->id();
                $table->char('token_hash', 64);
                $table->foreignId('court_id')->constrained()->restrictOnDelete();
                $table->dateTime('starts_at', 3);
                $table->dateTime('ends_at', 3);
                $table->dateTime('expires_at', 3);
                $table->timestamps(3);
                $table->unique(['token_hash', 'court_id', 'starts_at']);
                $table->index(['court_id', 'starts_at', 'ends_at', 'expires_at']);
                $table->index(['token_hash', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_holds');
        if (Schema::hasTable('booking_orders') && Schema::hasColumn('booking_orders', 'status')) {
            Schema::table('booking_orders', fn (Blueprint $table) => $table->dropColumn('status'));
        }
    }
};
