<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('booking_orders')) {
            return;
        }

        // Move any pre-order bookings into the canonical order record before removing
        // the legacy booking/payment state columns.
        DB::table('bookings')->whereNull('booking_order_id')->orderBy('id')->get()->each(function ($booking): void {
            $oldStatus = $booking->status ?? null;
            $paid = in_array($oldStatus, ['CONFIRMED', 'COMPLETED'], true)
                || DB::table('payments')->where('booking_id', $booking->id)->where('status', 'PAID')->exists();
            $orderId = DB::table('booking_orders')->insertGetId([
                'booking_code' => $booking->booking_code,
                'status' => $paid ? 'PAID' : 'PENDING_PAYMENT',
                'status_token_hash' => hash('sha256', random_bytes(64)),
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'payment_deadline_at' => in_array($oldStatus, ['EXPIRED', 'CANCELLED'], true)
                    ? now()->subSecond()
                    : $booking->payment_deadline_at,
                'base_amount' => $booking->base_amount,
                'discount_amount' => $booking->discount_amount,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'customer_note' => $booking->customer_note,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ]);

            DB::table('bookings')->where('id', $booking->id)->update(['booking_order_id' => $orderId]);
        });

        // Ensure order status reflects any legacy paid/confirmed child record.
        DB::table('booking_orders as orders')->whereExists(function ($query): void {
            $query->selectRaw('1')->from('bookings as segments')
                ->whereColumn('segments.booking_order_id', 'orders.id')
                ->where(function ($states): void {
                    $states->whereIn('segments.status', ['CONFIRMED', 'COMPLETED'])
                        ->orWhereExists(function ($payments): void {
                            $payments->selectRaw('1')->from('payments')
                                ->whereColumn('payments.booking_id', 'segments.id')
                                ->where('payments.status', 'PAID');
                        });
                });
        })->update(['status' => 'PAID']);

        if (Schema::hasColumn('bookings', 'status')) {
            $hasPendingDeadlineCheck = DB::table('information_schema.table_constraints')
                ->where('constraint_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'bookings')
                ->where('constraint_name', 'chk_bookings_pending_deadline')
                ->exists();
            if ($hasPendingDeadlineCheck) {
                DB::statement('ALTER TABLE bookings DROP CHECK chk_bookings_pending_deadline');
            }
            Schema::table('bookings', function ($table): void {
                $table->index(['court_id', 'starts_at', 'ends_at'], 'bookings_court_schedule_index');
                $table->index('booking_order_id', 'bookings_booking_order_id_index');
            });
            Schema::table('bookings', function ($table): void {
                $table->dropIndex('bookings_court_id_starts_at_ends_at_status_index');
                $table->dropIndex('bookings_status_payment_deadline_at_index');
                $table->dropIndex('bookings_booking_order_id_status_index');
                $table->dropUnique('bookings_status_token_hash_unique');
                $table->dropColumn(['status', 'status_token_hash', 'payment_deadline_at', 'cancellation_reason', 'confirmed_at', 'cancelled_at']);
            });
        }

        if (Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function ($table): void {
                $table->index('booking_id', 'payments_booking_id_index');
            });
            Schema::table('payments', function ($table): void {
                $table->dropIndex('payments_booking_id_status_index');
                $table->dropColumn(['status', 'failed_at']);
            });
        }

        if (Schema::hasColumn('booking_orders', 'confirmed_at') || Schema::hasColumn('booking_orders', 'cancelled_at')) {
            Schema::table('booking_orders', function ($table): void {
                $table->dropColumn(array_values(array_filter(['confirmed_at', 'cancelled_at'], fn ($column) => Schema::hasColumn('booking_orders', $column))));
            });
        }
    }

    public function down(): void
    {
        // Workflow state cannot be reconstructed safely after these columns are removed.
        throw new RuntimeException('This cleanup migration is irreversible.');
    }
};
