<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_features', function (Blueprint $table) {
            $table->string('process_title')->nullable(); // "Our Process", "Getting Started"
            $table->json('process_steps')->nullable();   // [{ "title": "Assessment", "description": "..." }, ...]
            $table->json('field_types')->nullable();     // ["Text Input", "Email", ...]
        });
    }

    public function down(): void
    {
        Schema::table('service_features', function (Blueprint $table) {
            $table->dropColumn(['process_title', 'process_steps', 'field_types']);
        });
    }
};
