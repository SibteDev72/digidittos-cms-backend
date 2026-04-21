<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed the set of tags referenced by the 10 DigiDittos blog posts.
 * Storage uses kebab-case slugs (matching the old Node backend so any
 * external deep-links keep working). Display names are humanised.
 *
 * Legacy pre-upgrade tags (HIPAA, EHR, Digital Health, etc.) are removed here
 * so a reseed leaves a clean tag set.
 */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        // Remove legacy pre-upgrade tags.
        Tag::whereIn('slug', [
            'hipaa', 'ehr', 'digital-health', 'compliance',
            'innovation', 'security', 'updates', 'tips',
        ])->delete();

        // slug => display name
        $tags = [
            'agentic-ai'            => 'Agentic AI',
            'artificial-intelligence' => 'Artificial Intelligence',
            'automation'            => 'Automation',
            'software-development'  => 'Software Development',

            'nextjs'                => 'Next.js',
            'react'                 => 'React',
            'web-development'       => 'Web Development',
            'server-components'     => 'Server Components',

            'flutter'               => 'Flutter',
            'mobile-development'    => 'Mobile Development',
            'dart'                  => 'Dart',
            'cross-platform'        => 'Cross-Platform',

            'ui-ux'                 => 'UI/UX',
            'design-systems'        => 'Design Systems',
            'frontend'              => 'Frontend',
            'accessibility'         => 'Accessibility',

            'saas'                  => 'SaaS',
            'architecture'          => 'Architecture',
            'microservices'         => 'Microservices',
            'backend'               => 'Backend',

            'typescript'            => 'TypeScript',
            'best-practices'        => 'Best Practices',
            'javascript'            => 'JavaScript',

            'devops'                => 'DevOps',
            'ci-cd'                 => 'CI/CD',
            'infrastructure'        => 'Infrastructure',

            'api-design'            => 'API Design',
            'rest'                  => 'REST',

            'react-native'          => 'React Native',

            'postgresql'            => 'PostgreSQL',
            'database'              => 'Database',
            'performance'           => 'Performance',
        ];

        foreach ($tags as $slug => $name) {
            Tag::updateOrCreate(
                ['slug' => $slug],
                ['slug' => Str::slug($slug), 'name' => $name]
            );
        }
    }
}
