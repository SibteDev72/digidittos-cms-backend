<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seed the 8 DigiDittos blog categories.
 *
 * These are the exact categories used by the 10 seeded blog posts in
 * BlogSeeder. Legacy pre-upgrade healthcare categories (Healthcare, Technology,
 * Product Updates, Industry News) are removed here so a reseed wipes them.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Remove legacy pre-upgrade categories so they don't orphan posts.
        Category::whereIn('slug', [
            'technology',
            'healthcare',
            'product-updates',
            'industry-news',
        ])->delete();

        $categories = [
            ['name' => 'Web Development',        'slug' => 'web-development',        'description' => 'Modern web platforms, frameworks, and performance patterns.',  'sort_order' => 1],
            ['name' => 'Mobile Development',     'slug' => 'mobile-development',     'description' => 'Cross-platform and native mobile engineering.',              'sort_order' => 2],
            ['name' => 'AI & Machine Learning',  'slug' => 'ai-machine-learning',    'description' => 'Applied AI, LLMs, and agentic workflows in production.',    'sort_order' => 3],
            ['name' => 'UI/UX Design',           'slug' => 'ui-ux-design',           'description' => 'Design systems, research, and interfaces that convert.',     'sort_order' => 4],
            ['name' => 'DevOps & Infrastructure','slug' => 'devops-infrastructure',  'description' => 'CI/CD pipelines, cloud, and infrastructure as code.',       'sort_order' => 5],
            ['name' => 'Backend & Architecture', 'slug' => 'backend-architecture',   'description' => 'APIs, databases, and scalable backend architecture.',       'sort_order' => 6],
            ['name' => 'Programming Languages',  'slug' => 'programming-languages',  'description' => 'Language-specific patterns and best practices.',             'sort_order' => 7],
            ['name' => 'SaaS & Product',         'slug' => 'saas-product',           'description' => 'SaaS architecture, multi-tenancy, and product strategy.',    'sort_order' => 8],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
