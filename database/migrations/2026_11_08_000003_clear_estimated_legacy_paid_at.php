<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The initial paid_at migration inferred payment times from updated_at.
        // That timestamp does not prove when payment happened, so discard those
        // estimates. New status changes continue to record their actual time.
        DB::table('booking_orders')->whereNotNull('paid_at')->update(['paid_at' => null]);
    }

    public function down(): void
    {
        // Estimated legacy payment times cannot be reconstructed reliably.
    }
};
