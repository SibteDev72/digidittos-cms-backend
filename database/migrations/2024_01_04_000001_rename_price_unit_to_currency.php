<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->renameColumn('price_unit', 'currency');
        });

        // Update default values from '/user/month' to '$'
        \DB::table('pricing_plans')->where('currency', '/user/month')->update(['currency' => '$']);
    }

    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->renameColumn('currency', 'price_unit');
        });
    }
};
