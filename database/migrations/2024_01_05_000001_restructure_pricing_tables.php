<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // a) Create pricing_plan_prices table
        Schema::create('pricing_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->onDelete('cascade');
            $table->string('billing_period'); // 'monthly' or 'annual'
            $table->decimal('price', 8, 2);
            $table->string('currency')->default('$');
            $table->decimal('sale_price', 8, 2)->nullable();
            $table->integer('sale_percentage')->nullable();
            $table->string('sale_label')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['pricing_plan_id', 'billing_period']);
        });

        // b) Create pricing_category_sales table
        Schema::create('pricing_category_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_period'); // 'monthly' or 'annual'
            $table->integer('discount_percentage')->default(0);
            $table->string('label')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // c) Migrate existing data from pricing_plans to pricing_plan_prices
        $plans = DB::table('pricing_plans')->get();

        foreach ($plans as $plan) {
            $currency = '$';

            // Check if currency column exists on the plan
            if (isset($plan->currency) && $plan->currency) {
                $currency = $plan->currency;
            }

            if ($plan->monthly_price !== null) {
                DB::table('pricing_plan_prices')->insert([
                    'pricing_plan_id' => $plan->id,
                    'billing_period' => 'monthly',
                    'price' => $plan->monthly_price,
                    'currency' => $currency,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($plan->annual_price !== null) {
                DB::table('pricing_plan_prices')->insert([
                    'pricing_plan_id' => $plan->id,
                    'billing_period' => 'annual',
                    'price' => $plan->annual_price,
                    'currency' => $currency,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // d) Drop old columns from pricing_plans
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'annual_price', 'annual_discount_percent']);
        });

        // Drop currency column if it exists
        if (Schema::hasColumn('pricing_plans', 'currency')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add columns to pricing_plans
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->decimal('monthly_price', 8, 2)->nullable()->after('description');
            $table->decimal('annual_price', 8, 2)->nullable()->after('monthly_price');
            $table->string('currency')->default('$')->after('annual_price');
            $table->integer('annual_discount_percent')->default(20)->after('is_active');
        });

        // Migrate data back
        $prices = DB::table('pricing_plan_prices')->get();

        foreach ($prices as $price) {
            $column = $price->billing_period === 'monthly' ? 'monthly_price' : 'annual_price';
            DB::table('pricing_plans')
                ->where('id', $price->pricing_plan_id)
                ->update([
                    $column => $price->price,
                    'currency' => $price->currency,
                ]);
        }

        Schema::dropIfExists('pricing_plan_prices');
        Schema::dropIfExists('pricing_category_sales');
    }
};
