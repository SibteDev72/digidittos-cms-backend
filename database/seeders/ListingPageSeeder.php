<?php

namespace Database\Seeders;

use App\Models\ListingPageSection;
use Illuminate\Database\Seeder;

/**
 * Seeds hero / approach content for the three catalogue pages
 * (Projects, Blog, Services). Defaults match the original hardcoded
 * copy that used to live in components/pages/*ListPage.tsx on the
 * business site, so the first CMS render is a pixel-perfect match.
 */
class ListingPageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ─── Projects ───────────────────────────────────
            [
                'page' => 'projects',
                'section_key' => 'hero',
                'sort_order' => 1,
                'title' => 'Hero',
                'content' => [
                    'eyebrow' => 'Projects',
                    'title_line_1' => "Results we've delivered",
                    'title_line_2' => 'across industries',
                    'subtitle' => 'Every project here is a real partnership — built with precision engineering, deep domain expertise, and a relentless focus on outcomes that matter.',
                    'primary_cta_text' => 'Start Your Project',
                    'primary_cta_url' => '/contact',
                ],
            ],

            // ─── Blog ───────────────────────────────────────
            [
                'page' => 'blog',
                'section_key' => 'hero',
                'sort_order' => 1,
                'title' => 'Hero',
                'content' => [
                    'eyebrow' => 'Blog',
                    'title_line_1' => 'Thinking that shapes',
                    'title_line_2' => 'what we build',
                    'subtitle' => 'Engineering perspectives, architectural deep dives, and the ideas behind the products — straight from the team that builds them.',
                    'primary_cta_text' => '',
                    'primary_cta_url' => '',
                ],
            ],

            // ─── Services ───────────────────────────────────
            [
                'page' => 'services',
                'section_key' => 'hero',
                'sort_order' => 1,
                'title' => 'Hero',
                'content' => [
                    'eyebrow' => 'Our Services',
                    'title_line_1' => 'Engineering that drives',
                    'title_line_2' => 'real business impact',
                    'subtitle' => 'Strategy, design, development, and growth — under one roof. We partner with ambitious teams to build products that ship fast and scale further.',
                    'primary_cta_text' => 'Start a Project',
                    'primary_cta_url' => '/contact',
                ],
            ],
            [
                'page' => 'services',
                'section_key' => 'approach',
                'sort_order' => 2,
                'title' => 'Our Approach',
                'content' => [
                    'eyebrow' => 'Our Approach',
                    'title_line_1' => 'How we think about',
                    'title_line_2' => 'building software',
                    'subtitle' => 'Every engagement starts with four principles that guide every architecture decision, design choice, and line of code we ship.',
                    'cards' => [
                        [
                            'icon' => 'scale',
                            'title' => "Build to\nScale",
                            'desc' => 'Architecture designed for growth — from MVP to millions of users.',
                            'featured' => true,
                        ],
                        [
                            'icon' => 'security',
                            'title' => "Security\nFirst",
                            'desc' => 'Enterprise-grade security baked into every layer from day one.',
                            'featured' => false,
                        ],
                        [
                            'icon' => 'speed',
                            'title' => "Ship\nFast",
                            'desc' => 'Rapid delivery with CI/CD pipelines and agile sprint cycles.',
                            'featured' => false,
                        ],
                        [
                            'icon' => 'user',
                            'title' => "User\nObsessed",
                            'desc' => 'Every decision starts and ends with the people using the product.',
                            'featured' => false,
                        ],
                    ],
                ],
            ],
        ];

        foreach ($rows as $row) {
            ListingPageSection::updateOrCreate(
                ['page' => $row['page'], 'section_key' => $row['section_key']],
                [
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
