<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description');
            $table->string('featured_image')->nullable();

            // Client & classification
            $table->string('client')->nullable();
            $table->string('category')->nullable();
            $table->string('duration')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('live_url')->nullable();

            // Array content stored as JSON
            $table->json('tech_stack')->nullable();
            $table->json('tags')->nullable();
            $table->json('gallery')->nullable();
            $table->json('highlights')->nullable();
            $table->json('key_features')->nullable();

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);

            // SEO — matches the blog module so the React CMS SEO tab is reusable
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->json('json_ld_schema')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
