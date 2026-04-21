<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_panels', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Display name on carousel button (e.g. "EHR", "Form Factory")
            $table->string('slug')->unique(); // URL-friendly key
            $table->string('image_url')->nullable(); // Uploaded image from CMS (overrides fallback)
            $table->string('link_url')->nullable();  // Where the button links to (e.g. "/services/ehr")
            $table->string('button_text')->default('View Service'); // Button label
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_panels');
    }
};
