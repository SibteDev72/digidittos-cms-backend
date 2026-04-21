<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature parity with the old Node backend:
 *   - views       : view counter for /popular ranking + detail view
 *   - key_insights: 2–4 bullet takeaways rendered on the blog detail page
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unsignedInteger('views')->default(0)->after('reading_time');
            $table->json('key_insights')->nullable()->after('views');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['views', 'key_insights']);
        });
    }
};
