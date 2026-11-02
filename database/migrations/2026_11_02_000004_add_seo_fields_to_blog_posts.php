<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('cover_image_alt', 255)->nullable()->after('cover_image_path');
            $table->string('meta_title', 255)->nullable()->after('cover_image_alt');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['cover_image_alt', 'meta_title', 'meta_description']);
        });
    }
};
