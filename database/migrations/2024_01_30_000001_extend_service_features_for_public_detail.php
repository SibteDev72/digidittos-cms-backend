<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shape service_features for the business site's 5-section layout
 * (Overview / Approach / Projects / Technologies / CTA) + per-feature SEO.
 *
 *   - headline, hero_description  → hero block on /services/{slug}
 *   - overview_*                  → "Overview" section
 *   - (process_title/process_steps are reused as Approach — no new column)
 *   - technologies                → "Technologies" section (json of {name, category})
 *   - cta_*                       → optional per-feature CTA override
 *   - meta_* / og_* / json_ld     → full per-feature SEO control
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_features', function (Blueprint $table) {
            // Hero block
            $table->string('headline')->nullable()->after('title');
            $table->text('hero_description')->nullable()->after('headline');

            // Overview section
            $table->string('overview_title')->nullable()->after('description');
            $table->text('overview_description')->nullable()->after('overview_title');
            $table->json('overview')->nullable()->after('overview_description');

            // Technologies section — [{ name, category }]
            $table->json('technologies')->nullable()->after('field_types');

            // Per-feature CTA (optional overrides over the shared site CTA)
            $table->string('cta_title')->nullable()->after('technologies');
            $table->text('cta_description')->nullable()->after('cta_title');
            $table->string('cta_button_text')->nullable()->after('cta_description');
            $table->string('cta_button_url')->nullable()->after('cta_button_text');

            // SEO (mirrors the blog + service SEO shape)
            $table->string('meta_title')->nullable()->after('cta_button_url');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->json('json_ld_schema')->nullable()->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('service_features', function (Blueprint $table) {
            $table->dropColumn([
                'headline',
                'hero_description',
                'overview_title',
                'overview_description',
                'overview',
                'technologies',
                'cta_title',
                'cta_description',
                'cta_button_text',
                'cta_button_url',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'json_ld_schema',
            ]);
        });
    }
};
