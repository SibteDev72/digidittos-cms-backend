<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            PricingSeeder::class,
            ServiceSeeder::class,
            FeatureSeeder::class,
            // Blog must run categories → tags → posts so FK lookups succeed
            CategorySeeder::class,
            TagSeeder::class,
            BlogSeeder::class,
            ProjectSeeder::class,
            PageSettingsSeeder::class,
            ServicePanelSeeder::class,
            AboutPageSeeder::class,
            ListingPageSeeder::class,
        ]);
    }
}
