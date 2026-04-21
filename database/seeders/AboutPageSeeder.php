<?php

namespace Database\Seeders;

use App\Models\AboutSection;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'sort_order' => 1,
                'content' => [
                    'eyebrow' => 'About DigiDittos',
                    'title_line_1' => 'We Engineer Software',
                    'title_line_2' => 'That Moves Industries',
                    'subtitle' => 'A full-service engineering studio partnering with startups and enterprises to design, build, and scale digital products — from AI-powered platforms to enterprise SaaS and high-traffic applications.',
                    'primary_cta_text' => 'Meet the Team',
                    'primary_cta_url' => '#team',
                    'secondary_cta_text' => 'Our Process',
                    'secondary_cta_url' => '#process',
                ],
            ],
            [
                'section_key' => 'beliefs',
                'sort_order' => 2,
                'content' => [
                    'tag' => 'The Beliefs That Shape Us',
                    'items' => [
                        ['word' => 'Discover', 'tag' => 'Research, Define & Strategize', 'align' => 'center'],
                        ['word' => 'Architect', 'tag' => 'Design Systems That Scale', 'align' => 'left'],
                        ['word' => 'Engineer', 'tag' => 'Build With Precision', 'align' => 'right'],
                        ['word' => 'Ship', 'tag' => 'Deploy, Measure & Iterate', 'align' => 'center'],
                    ],
                ],
            ],
            [
                'section_key' => 'values',
                'sort_order' => 3,
                'content' => [
                    'tag' => 'Our Values',
                    'title' => 'Our beliefs are deeply woven into our process, guiding everything we build.',
                    'items' => [
                        [
                            'title' => 'Discover',
                            'body' => "Every great product starts with understanding the problem worth solving. We immerse ourselves in your market, users, and business model — mapping pain points, validating assumptions, and defining a technical roadmap grounded in data. Discovery isn't a phase we rush through; it's the foundation that prevents costly pivots later.",
                        ],
                        [
                            'title' => 'Architect',
                            'body' => 'We design systems, not just screens. From database schemas to API contracts to deployment pipelines — every layer is planned for the load it will carry in two years, not just today. Our architecture decisions are deliberate: we choose boring technology where reliability matters and cutting-edge tools where they create real advantage.',
                        ],
                        [
                            'title' => 'Engineer',
                            'body' => 'Code is craft. We write software that humans can read, test, and extend — not just software that runs. Agile sprints with continuous delivery, automated testing, and rigorous code reviews keep quality high and velocity higher. Every feature ships with monitoring, documentation, and the confidence that it works at scale.',
                        ],
                        [
                            'title' => 'Ship',
                            'body' => "Launching is not the finish line — it's the starting gun. We deploy to production with zero-downtime strategies, set up observability from day one, and stay embedded with your team post-launch. Performance tuning, user feedback loops, and iterative improvements ensure your product doesn't just launch — it grows.",
                        ],
                    ],
                ],
            ],
            // NOTE: testimonials + cta were previously seeded here as duplicates.
            // They are now site-wide content managed under Website Settings and
            // fetched from /api/public/site-content. Storage lives in
            // homepage_sections.
            [
                'section_key' => 'team',
                'sort_order' => 4,
                'content' => [
                    'title' => 'Team',
                    'subtitle' => 'The people behind every product — engineers, designers, and strategists who turn ambitious ideas into production-grade software.',
                    'members' => [
                        ['name' => 'Sibte Hassan', 'role' => 'Founder & CEO', 'img' => '/images/team/sibte.jpg'],
                        ['name' => 'Ali Raza', 'role' => 'Lead Engineer', 'img' => '/images/team/ali.jpg'],
                        ['name' => 'Sara Ahmed', 'role' => 'Product Designer', 'img' => '/images/team/sara.jpg'],
                        ['name' => 'Usman Khan', 'role' => 'Backend Architect', 'img' => '/images/team/usman.jpg'],
                        ['name' => 'Fatima Noor', 'role' => 'Frontend Engineer', 'img' => '/images/team/fatima.jpg'],
                        ['name' => 'Ahmed Bilal', 'role' => 'DevOps Engineer', 'img' => '/images/team/ahmed.jpg'],
                        ['name' => 'Hira Malik', 'role' => 'AI / ML Engineer', 'img' => '/images/team/hira.jpg'],
                        ['name' => 'Zain Ul Abideen', 'role' => 'Mobile Developer', 'img' => '/images/team/zain.jpg'],
                    ],
                ],
            ],
        ];

        // Remove stale duplicates left over from earlier seeds so the About
        // page doesn't double-render these site-wide blocks.
        AboutSection::whereIn('section_key', ['testimonials', 'cta', 'stats', 'mission', 'platform'])->delete();

        foreach ($sections as $section) {
            AboutSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                [
                    'title' => null,
                    'content' => $section['content'],
                    'sort_order' => $section['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
