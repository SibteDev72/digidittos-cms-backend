<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('title');
            $table->string('short_title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('video_url')->nullable();
            $table->string('route');
            $table->boolean('is_available')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('hero_label');
            $table->json('hero_headline');
            $table->text('hero_subtitle');
            $table->string('media_type');
            $table->string('process_title')->nullable();
            $table->string('cta_title')->nullable();
            $table->text('cta_description')->nullable();
            $table->string('cta_button_text')->nullable();
            $table->boolean('is_coming_soon')->default(false);
            $table->text('coming_soon_description')->nullable();
            $table->json('field_types')->nullable();
            $table->timestamps();
        });

        Schema::create('service_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('feature_key');
            $table->string('title');
            $table->text('description');
            $table->json('items');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'feature_key']);
        });

        Schema::create('service_feature_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_feature_id')->constrained('service_features')->onDelete('cascade');
            $table->string('type');
            $table->string('primary_url');
            $table->string('secondary_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_process_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_process_steps');
        Schema::dropIfExists('service_feature_media');
        Schema::dropIfExists('service_features');
        Schema::dropIfExists('service_details');
        Schema::dropIfExists('services');
    }
};
