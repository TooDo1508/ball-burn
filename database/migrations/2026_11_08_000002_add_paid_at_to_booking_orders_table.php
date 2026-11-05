<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('booking_orders', 'paid_at')) {
            Schema::table('booking_orders', function (Blueprint $table): void {
                $table->dateTime('paid_at', 3)->nullable()->after('payment_deadline_at');
                $table->index('paid_at');
            });
        }

        DB::table('booking_orders')->where('status', 'PAID')->whereNull('paid_at')->update([
            'paid_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('booking_orders', 'paid_at')) {
            Schema::table('booking_orders', function (Blueprint $table): void {
                $table->dropIndex(['paid_at']);
                $table->dropColumn('paid_at');
            });
        }
    }
};
