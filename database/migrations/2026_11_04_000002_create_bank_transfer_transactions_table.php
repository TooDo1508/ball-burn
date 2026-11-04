<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfer_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('bank_transaction_id', 191)->unique();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->dateTime('transaction_at', 3);
            $table->string('transfer_description', 500)->nullable();
            $table->string('status', 30)->default('UNMATCHED');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at', 3)->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps(3);
            $table->index(['status', 'transaction_at']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfer_transactions');
    }
};
