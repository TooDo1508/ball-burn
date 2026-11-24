<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_orders', function (Blueprint $table): void {
            $table->string('payment_reference', 32)->nullable()->unique()->after('hold_token_hash');
        });

        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->char('payload_hash', 64)->nullable()->after('payload');
            $table->unsignedInteger('attempts')->default(0)->after('processing_status');
            $table->dateTime('next_attempt_at', 3)->nullable()->after('attempts');
            $table->dateTime('processing_started_at', 3)->nullable()->after('next_attempt_at');
            $table->string('outcome', 30)->nullable()->after('processed_at');
            $table->foreignId('booking_order_id')->nullable()->after('outcome')->constrained('booking_orders')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->after('booking_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_transfer_transaction_id')->nullable()->after('payment_id')->constrained('bank_transfer_transactions')->restrictOnDelete();
            $table->index(['processing_status', 'next_attempt_at'], 'webhook_reprocess_index');
        });

        Schema::table('bank_transfer_transactions', function (Blueprint $table): void {
            $table->string('provider', 50)->nullable()->after('bank_transaction_id');
            $table->string('provider_transaction_id', 191)->nullable()->after('provider');
            $table->string('bank_reference_code', 191)->nullable()->after('provider_transaction_id');
            $table->string('receiver_bank_code', 50)->nullable()->after('bank_reference_code');
            $table->string('receiver_account_number', 100)->nullable()->after('receiver_bank_code');
            $table->string('receiver_sub_account', 100)->nullable()->after('receiver_account_number');
            $table->unique(['provider', 'provider_transaction_id'], 'bank_transfer_provider_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->dropIndex('webhook_reprocess_index');
            $table->dropConstrainedForeignId('bank_transfer_transaction_id');
            $table->dropConstrainedForeignId('payment_id');
            $table->dropConstrainedForeignId('booking_order_id');
            $table->dropColumn(['payload_hash', 'attempts', 'next_attempt_at', 'processing_started_at', 'outcome']);
        });
        Schema::table('bank_transfer_transactions', function (Blueprint $table): void {
            $table->dropUnique('bank_transfer_provider_transaction_unique');
            $table->dropColumn(['provider', 'provider_transaction_id', 'bank_reference_code', 'receiver_bank_code', 'receiver_account_number', 'receiver_sub_account']);
        });
        Schema::table('booking_orders', function (Blueprint $table): void {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn('payment_reference');
        });
    }
};
