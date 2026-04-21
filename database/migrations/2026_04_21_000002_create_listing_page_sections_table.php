<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listing-page sections (projects, blog, services listing pages).
 *
 * Each listing page owns a small set of section rows keyed by
 * (page, section_key) — e.g. ('projects', 'hero'), ('services', 'approach').
 * Mirrors the `about_sections` pattern but scoped by `page` so multiple
 * listing pages share one table without key collisions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page');          // e.g. projects, blog, services
            $table->string('section_key');   // e.g. hero, approach
            $table->string('title')->nullable();
            $table->json('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['page', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_page_sections');
    }
};
