<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_plan_prices', function (Blueprint $table) {
            $table->string('square_plan_variation_id')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_plan_prices', function (Blueprint $table) {
            $table->dropColumn('square_plan_variation_id');
        });
    }
};
