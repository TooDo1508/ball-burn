<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifications', 'read_at')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dateTime('read_at', 3)->nullable()->after('sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'read_at')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropColumn('read_at');
            });
        }
    }
};
