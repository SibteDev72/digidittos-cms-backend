<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects and service features already track meta_keywords; blog posts
 * didn't. Adding it so the SEO audit scores every content type on the
 * same field set, and the blog form's SEO tab gets feature parity with
 * the rest of the CMS.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blog_posts', 'meta_keywords')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->json('meta_keywords')->nullable()->after('meta_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('blog_posts', 'meta_keywords')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->dropColumn('meta_keywords');
            });
        }
    }
};
