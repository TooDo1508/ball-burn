<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('QUEUED');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->longText('ocr_raw_text')->nullable();
            $table->json('extracted_data')->nullable();
            $table->json('mapping_result')->nullable();
            $table->text('ocr_error')->nullable();
            $table->dateTime('processing_started_at', 3)->nullable();
            $table->dateTime('processed_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
