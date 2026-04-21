<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // a) Add slug and is_active columns to service_features
        Schema::table('service_features', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('feature_key');
            $table->boolean('is_active')->default(true)->after('items');
        });

        // b) Populate slug from feature_key for existing rows
        $features = DB::table('service_features')->get();
        foreach ($features as $feature) {
            $slug = Str::slug($feature->feature_key);
            DB::table('service_features')
                ->where('id', $feature->id)
                ->update(['slug' => $slug]);
        }

        // c) Create the pivot table
        Schema::create('service_feature_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('service_feature_id')->constrained('service_features')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'service_feature_id']);
        });

        // d) Migrate existing data: create pivot rows from service_id on service_features
        $existingFeatures = DB::table('service_features')->whereNotNull('service_id')->get();
        foreach ($existingFeatures as $feature) {
            DB::table('service_feature_assignments')->insert([
                'service_id' => $feature->service_id,
                'service_feature_id' => $feature->id,
                'sort_order' => $feature->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // e) Drop the old unique constraint and service_id foreign key/column
        Schema::table('service_features', function (Blueprint $table) {
            $table->dropUnique(['service_id', 'feature_key']);
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });

        // f) Make slug non-nullable and unique, make feature_key unique on its own
        Schema::table('service_features', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
            $table->string('feature_key')->unique()->change();
        });
    }

    public function down(): void
    {
        // Re-add service_id to service_features
        Schema::table('service_features', function (Blueprint $table) {
            $table->dropUnique(['feature_key']);
            $table->dropUnique(['slug']);
        });

        Schema::table('service_features', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('id')->constrained('services')->onDelete('cascade');
        });

        // Restore data from pivot
        $assignments = DB::table('service_feature_assignments')->get();
        foreach ($assignments as $assignment) {
            DB::table('service_features')
                ->where('id', $assignment->service_feature_id)
                ->update(['service_id' => $assignment->service_id]);
        }

        // Re-add the composite unique constraint
        Schema::table('service_features', function (Blueprint $table) {
            $table->unique(['service_id', 'feature_key']);
        });

        // Drop pivot table
        Schema::dropIfExists('service_feature_assignments');

        // Drop added columns
        Schema::table('service_features', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_active']);
        });
    }
};
