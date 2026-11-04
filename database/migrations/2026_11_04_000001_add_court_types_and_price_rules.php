<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->string('court_type', 20)->default('REGULAR')->after('surface_type')->index();
        });

        Schema::create('court_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('day_type', 10);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->decimal('price_per_hour', 10, 2);
            $table->char('currency', 3)->default('VND');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps(3);
            $table->unique(['court_id', 'day_type', 'starts_at', 'effective_from'], 'uq_court_price_rule_start');
            $table->index(['court_id', 'day_type', 'effective_from', 'effective_until'], 'idx_court_price_rules_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_price_rules');

        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn('court_type');
        });
    }
};
