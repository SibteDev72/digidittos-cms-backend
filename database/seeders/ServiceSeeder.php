<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceDetail;
use Illuminate\Database\Seeder;

/**
 * Seed the 5 DigiDittos parent services that group features on the
 * business site's /services page. Legacy pre-upgrade services (ehr,
 * form-factory, clearinghouse, etc.) are removed on reseed.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Strip legacy pre-upgrade services + their details/pivot rows.
        Service::whereIn('slug', [
            'ehr', 'form-factory', 'clearinghouse',
            'project-management', 'crm',
            'web-development', 'mobile-app-development', 'software-development',
        ])->each(function (Service $s) {
            $s->features()->detach();
            optional($s->detail)->delete();
            $s->processSteps()->delete();
            $s->delete();
        });

        foreach ($this->services() as $i => $data) {
            $service = Service::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'number'           => $data['number'],
                    'title'            => $data['title'],
                    'short_title'      => $data['short_title'],
                    'description'      => $data['description'],
                    'video_url'        => null,
                    'route'            => '/services',
                    'is_available'     => true,
                    'sort_order'       => $i + 1,
                    'is_active'        => true,
                    'meta_title'       => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                    'og_title'         => $data['og_title'],
                    'og_description'   => $data['og_description'],
                    'og_image'         => null,
                    'json_ld_schema'   => null,
                ],
            );

            // Detail row — kept populated so the admin UI has sensible
            // defaults, even though the public business site now flows
            // /services → feature detail, not per-service detail page.
            ServiceDetail::updateOrCreate(
                ['service_id' => $service->id],
                [
                    'hero_label'      => strtoupper($data['short_title']),
                    'hero_headline'   => $data['hero_headline'],
                    'hero_subtitle'   => $data['description'],
                    'media_type'      => 'image',
                    'process_title'   => null,
                    'cta_title'       => "Let's build with {$data['short_title']}",
                    'cta_description' => 'Tell us about your project — we\'ll scope, cost, and timeline it in under 48 hours.',
                    'cta_button_text' => 'Get a Quote',
                    'is_coming_soon'  => false,
                    'coming_soon_description' => null,
                    'field_types'     => null,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function services(): array
    {
        return [
            [
                'slug'            => 'strategic-consultancy',
                'number'          => '01',
                'title'           => 'Strategic Consultancy',
                'short_title'     => 'Strategic Consultancy',
                'description'     => 'We audit, assess, and advise — so you build the right thing, the right way, from day one.',
                'hero_headline'   => ['Strategic', 'Consultancy', 'Services'],
                'meta_title'      => 'Strategic Consultancy — Architecture, Code Quality, Scalability | DigiDittos',
                'meta_description' => 'Technical audits, architecture reviews, code quality reports, and scalability assessments — before you write a line of code.',
                'og_title'        => 'Strategic Consultancy by DigiDittos',
                'og_description' => 'We audit, assess, and advise so you build the right thing, the right way.',
            ],
            [
                'slug'            => 'software-engineering',
                'number'          => '02',
                'title'           => 'Software Engineering',
                'short_title'     => 'Software Engineering',
                'description'     => 'End-to-end product development — from pixel-perfect frontends to bulletproof backends, across web, mobile, and cloud.',
                'hero_headline'   => ['Software', 'Engineering', 'Services'],
                'meta_title'      => 'Software Engineering — Web, Mobile, SaaS | DigiDittos',
                'meta_description' => 'Web development, Android, iOS, UI/UX design, and SaaS platforms engineered to scale.',
                'og_title'        => 'Software Engineering by DigiDittos',
                'og_description' => 'End-to-end product development across web, mobile, and cloud.',
            ],
            [
                'slug'            => 'ai-solutions',
                'number'          => '03',
                'title'           => 'AI Solutions',
                'short_title'     => 'AI Solutions',
                'description'     => 'Intelligent automation, machine learning integrations, and AI-driven platforms — from prototype to production.',
                'hero_headline'   => ['AI &', 'ML', 'Services'],
                'meta_title'      => 'AI Solutions — Agents, LangChain, LLMs | DigiDittos',
                'meta_description' => 'Production-grade AI agents, LangChain pipelines, LangGraph workflows, NLP, and LLM integrations.',
                'og_title'        => 'AI Solutions by DigiDittos',
                'og_description' => 'Intelligent automation and AI-driven platforms, prototype to production.',
            ],
            [
                'slug'            => 'digital-marketing',
                'number'          => '04',
                'title'           => 'Digital Marketing',
                'short_title'     => 'Digital Marketing',
                'description'     => 'Search, social, and performance marketing backed by data — campaigns that actually move the numbers.',
                'hero_headline'   => ['Digital', 'Marketing', 'Services'],
                'meta_title'      => 'Digital Marketing — SEO, Meta Ads, Google Ads | DigiDittos',
                'meta_description' => 'Technical SEO, Meta advertising, and Google Ads strategy that drives measurable growth.',
                'og_title'        => 'Digital Marketing by DigiDittos',
                'og_description' => 'Search, social, and performance marketing that moves the numbers.',
            ],
            [
                'slug'            => 'cms-development',
                'number'          => '05',
                'title'           => 'CMS Development',
                'short_title'     => 'CMS Development',
                'description'     => 'Custom WordPress builds, Shopify storefronts, and no-code websites — tailored to your editorial workflow.',
                'hero_headline'   => ['CMS', 'Development', 'Services'],
                'meta_title'      => 'CMS Development — WordPress, Shopify, No-Code | DigiDittos',
                'meta_description' => 'Custom WordPress themes and plugins, Shopify stores, and Webflow/Framer sites your team can manage.',
                'og_title'        => 'CMS Development by DigiDittos',
                'og_description' => 'Custom WordPress, Shopify, and no-code websites tailored to your workflow.',
            ],
        ];
    }
}
