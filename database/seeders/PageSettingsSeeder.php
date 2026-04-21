<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\HomepageSection;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class PageSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSiteSettings();
        $this->seedHomepageSections();
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            // general
            ['group' => 'general', 'key' => 'site_name', 'value' => 'DigiDittos', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Web, Mobile, AI and SaaS Development Studio', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_url', 'value' => 'https://www.digidittos.com', 'type' => 'string'],

            // seo (global fallback)
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'DigiDittos — Web, Mobile, AI & SaaS Development Studio', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Industry-leading web and mobile development studio specializing in AI-powered solutions, SaaS platforms, and scalable digital products.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => json_encode(['web development', 'mobile development', 'ai solutions', 'saas platforms', 'ui/ux design', 'digidittos']), 'type' => 'json'],
            ['group' => 'seo', 'key' => 'og_title', 'value' => 'DigiDittos — We Build Digital Products That Scale', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'og_description', 'value' => 'From AI-powered platforms to enterprise SaaS and high-traffic web applications — we design, build, and scale digital products.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'DigiDittos',
                'url' => 'https://www.digidittos.com',
                'description' => 'Industry-leading web and mobile development studio specializing in AI-powered solutions, SaaS platforms, and scalable digital products.',
                'foundingDate' => '2020',
            ]), 'type' => 'json'],

            // seo_homepage
            ['group' => 'seo_homepage', 'key' => 'meta_title', 'value' => 'DigiDittos — Web, Mobile, AI & SaaS Development Studio', 'type' => 'string'],
            ['group' => 'seo_homepage', 'key' => 'meta_description', 'value' => 'Driving innovation with AI and modern tech. We build web, mobile, and SaaS products from idea to launch.', 'type' => 'string'],
            ['group' => 'seo_homepage', 'key' => 'meta_keywords', 'value' => json_encode(['web development', 'mobile development', 'ai solutions', 'saas platforms', 'digidittos']), 'type' => 'json'],
            ['group' => 'seo_homepage', 'key' => 'og_title', 'value' => 'DigiDittos — Driving Innovation with AI & Modern Tech', 'type' => 'string'],
            ['group' => 'seo_homepage', 'key' => 'og_description', 'value' => 'Industry-leading web and mobile applications crafted with precision UX and scalable architecture.', 'type' => 'string'],
            ['group' => 'seo_homepage', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo_homepage', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'DigiDittos',
                'url' => 'https://www.digidittos.com',
            ]), 'type' => 'json'],

            // seo_about
            ['group' => 'seo_about', 'key' => 'meta_title', 'value' => 'About DigiDittos — Engineering Software That Moves Industries', 'type' => 'string'],
            ['group' => 'seo_about', 'key' => 'meta_description', 'value' => 'A full-service engineering studio partnering with startups and enterprises to design, build, and scale digital products.', 'type' => 'string'],
            ['group' => 'seo_about', 'key' => 'meta_keywords', 'value' => json_encode(['about digidittos', 'engineering studio', 'software team', 'digital products']), 'type' => 'json'],
            ['group' => 'seo_about', 'key' => 'og_title', 'value' => 'About DigiDittos — Our Team, Process & Beliefs', 'type' => 'string'],
            ['group' => 'seo_about', 'key' => 'og_description', 'value' => 'Meet the engineers, designers, and strategists who turn ambitious ideas into production-grade software.', 'type' => 'string'],
            ['group' => 'seo_about', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo_about', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                'name' => 'About DigiDittos',
                'url' => 'https://www.digidittos.com/about',
            ]), 'type' => 'json'],

            // seo_projects (listing page)
            ['group' => 'seo_projects', 'key' => 'meta_title', 'value' => 'Projects — DigiDittos', 'type' => 'string'],
            ['group' => 'seo_projects', 'key' => 'meta_description', 'value' => "Explore DigiDittos's portfolio of enterprise-grade software projects across fintech, healthcare, e-commerce, IoT, and more.", 'type' => 'string'],
            ['group' => 'seo_projects', 'key' => 'meta_keywords', 'value' => json_encode(['digidittos projects', 'software portfolio', 'case studies', 'fintech projects', 'saas projects', 'healthcare software']), 'type' => 'json'],
            ['group' => 'seo_projects', 'key' => 'og_title', 'value' => 'DigiDittos Projects — Built for Impact', 'type' => 'string'],
            ['group' => 'seo_projects', 'key' => 'og_description', 'value' => 'Real partnerships, precision engineering, deep domain expertise. See the work shaping industries.', 'type' => 'string'],
            ['group' => 'seo_projects', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo_projects', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'DigiDittos Projects',
                'url' => 'https://www.digidittos.com/projects',
                'description' => 'Portfolio of enterprise-grade software projects.',
            ]), 'type' => 'json'],

            // seo_blog (listing page)
            ['group' => 'seo_blog', 'key' => 'meta_title', 'value' => 'Blog — DigiDittos', 'type' => 'string'],
            ['group' => 'seo_blog', 'key' => 'meta_description', 'value' => 'Engineering perspectives, architectural deep dives, and the ideas behind the products — from the DigiDittos team.', 'type' => 'string'],
            ['group' => 'seo_blog', 'key' => 'meta_keywords', 'value' => json_encode(['digidittos blog', 'software engineering blog', 'ai blog', 'saas blog', 'architecture deep dives', 'engineering insights']), 'type' => 'json'],
            ['group' => 'seo_blog', 'key' => 'og_title', 'value' => 'DigiDittos Blog — Thinking That Shapes What We Build', 'type' => 'string'],
            ['group' => 'seo_blog', 'key' => 'og_description', 'value' => 'Insights on software engineering, AI, design systems, and modern tech.', 'type' => 'string'],
            ['group' => 'seo_blog', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo_blog', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Blog',
                'name' => 'DigiDittos Blog',
                'url' => 'https://www.digidittos.com/blog',
                'description' => 'Engineering perspectives and architectural deep dives from the DigiDittos team.',
            ]), 'type' => 'json'],

            // seo_services (listing page)
            ['group' => 'seo_services', 'key' => 'meta_title', 'value' => 'Services — DigiDittos', 'type' => 'string'],
            ['group' => 'seo_services', 'key' => 'meta_description', 'value' => 'Full-service software engineering — web, mobile, AI, UI/UX, SaaS, and ongoing support. We build what moves your business forward.', 'type' => 'string'],
            ['group' => 'seo_services', 'key' => 'meta_keywords', 'value' => json_encode(['web development services', 'mobile development services', 'ai services', 'saas development', 'ui ux design', 'digidittos services']), 'type' => 'json'],
            ['group' => 'seo_services', 'key' => 'og_title', 'value' => 'DigiDittos Services — Engineering Real Business Impact', 'type' => 'string'],
            ['group' => 'seo_services', 'key' => 'og_description', 'value' => 'Strategy, design, development, and growth — under one roof.', 'type' => 'string'],
            ['group' => 'seo_services', 'key' => 'og_image', 'value' => '', 'type' => 'string'],
            ['group' => 'seo_services', 'key' => 'json_ld_schema', 'value' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => 'DigiDittos Services',
                'url' => 'https://www.digidittos.com/services',
                'provider' => [
                    '@type' => 'Organization',
                    'name' => 'DigiDittos',
                ],
            ]), 'type' => 'json'],

            // footer — contact info + social handles + site-wide copy. The
            // Contact page on the business site and the site-wide Footer both
            // consume these keys verbatim. New keys must be seeded here because
            // PageSettingsController::updateSiteSettings only updates rows that
            // already exist (it won't auto-create unknown keys on first save).
            ['group' => 'footer', 'key' => 'footer_copyright', 'value' => '© DigiDittos. All rights reserved.', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'newsletter_label', 'value' => 'Subscribe to our Newsletter', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'contact_email', 'value' => 'contactus@digidittos.com', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'contact_phone', 'value' => '+92 327 9228899', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'contact_address', 'value' => 'Office no #201 Second Floor Al-Hamd Tower 6-6/D Civic Center Barket market Lahore.', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'contact_response_time', 'value' => 'Within 24 hours', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'contact_tagline', 'value' => 'Delivering cutting-edge web and mobile solutions with a focus on UX/UI and scalable architecture.', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_linkedin', 'value' => 'https://www.linkedin.com/company/digidittos/', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_twitter', 'value' => 'https://x.com/DigiDittos', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_instagram', 'value' => '', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_facebook', 'value' => 'https://www.facebook.com/Digidittos', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_github', 'value' => 'https://github.com/DigiDittos', 'type' => 'string'],
            ['group' => 'footer', 'key' => 'social_pinterest', 'value' => 'https://www.pinterest.com/digidittos/', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'sort_order' => 1,
                'content' => [
                    'taglines' => [
                        [
                            'line1' => 'Driving Innovation',
                            'line2' => 'with AI & Modern Tech',
                            'description' => 'Industry-leading web and mobile applications crafted with precision UX and scalable architecture. From idea to launch — results that matter.',
                        ],
                        [
                            'line1' => 'Engineering',
                            'line2' => 'AI-Powered Solutions',
                            'description' => 'Intelligent automation, machine learning integrations, and AI-driven platforms that transform how businesses operate and scale.',
                        ],
                        [
                            'line1' => 'Building Scalable',
                            'line2' => 'SaaS Platforms',
                            'description' => 'Multi-tenant cloud architectures, subscription billing, and enterprise-grade SaaS products built for growth from day one.',
                        ],
                        [
                            'line1' => 'Crafting Digital',
                            'line2' => 'Experiences That Last',
                            'description' => 'High-performance systems designed for millions of users — microservices, real-time data, and infrastructure that never sleeps.',
                        ],
                    ],
                    'tech_icons' => [
                        ['name' => 'Vue.js', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vuejs/vuejs-original.svg'],
                        ['name' => 'Angular', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/angularjs/angularjs-original.svg'],
                        ['name' => 'Node.js', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg'],
                        ['name' => 'Express', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/express/express-original.svg'],
                        ['name' => 'Django', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/django/django-plain.svg'],
                        ['name' => 'Laravel', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg'],
                        ['name' => 'MongoDB', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mongodb/mongodb-original.svg'],
                        ['name' => 'PostgreSQL', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/postgresql/postgresql-original.svg'],
                        ['name' => 'MySQL', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mysql/mysql-original.svg'],
                        ['name' => 'Docker', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/docker/docker-original.svg'],
                        ['name' => 'AWS', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-plain-wordmark.svg'],
                        ['name' => 'React', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/react/react-original.svg'],
                        ['name' => 'Next.js', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nextjs/nextjs-original.svg'],
                        ['name' => 'Flutter', 'src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/flutter/flutter-original.svg'],
                    ],
                    'primary_cta_text' => 'Talk to an Expert',
                    'primary_cta_url' => '/services',
                    'secondary_cta_text' => 'Our Services',
                    'secondary_cta_url' => '#process',
                ],
            ],
            [
                'section_key' => 'about',
                'sort_order' => 2,
                'content' => [
                    'tag' => 'Who We Are',
                    'title_line_1' => 'We Build Products',
                    'title_line_2' => 'That Scale',
                    'lead' => 'DigiDittos is a full-service software engineering studio. We partner with startups and enterprises to design, build, and scale digital products — from AI-powered platforms to enterprise SaaS and high-traffic web applications.',
                    'body' => "Our team combines deep technical expertise with product thinking. We don't just write code — we solve business problems through technology. Every engagement starts with understanding your goals and ends with measurable results.",
                    'steps' => [
                        [
                            'num' => '01',
                            'title' => 'Discovery & Strategy',
                            'desc' => 'We deep-dive into your business goals, users, and market to define a clear technical roadmap. This phase ensures every decision from here is backed by data and aligned with your vision.',
                            'tags' => ['Market Research', 'User Personas', 'Technical Roadmap'],
                        ],
                        [
                            'num' => '02',
                            'title' => 'Architecture & Design',
                            'desc' => 'System design, UI/UX prototyping, and technology selection — all validated before a single line of code. We architect for scale from day one so you never hit a wall.',
                            'tags' => ['System Design', 'UI/UX Prototyping', 'Tech Stack'],
                        ],
                        [
                            'num' => '03',
                            'title' => 'Engineering & Iteration',
                            'desc' => 'Agile sprints with continuous delivery. Clean code, automated testing, and weekly demos keep you in the loop while we ship fast without cutting corners.',
                            'tags' => ['Agile Sprints', 'CI/CD', 'Code Reviews'],
                        ],
                        [
                            'num' => '04',
                            'title' => 'Launch & Scale',
                            'desc' => 'Production deployment, performance optimization, monitoring, and ongoing support. We stay with you post-launch to ensure your product grows reliably.',
                            'tags' => ['Deployment', 'Monitoring', 'Growth Support'],
                        ],
                    ],
                ],
            ],
            [
                'section_key' => 'featured_services',
                'sort_order' => 3,
                'content' => [
                    'tag' => 'What We Do',
                    'title' => 'Featured',
                    'title_accent' => 'Services',
                    'view_all_text' => 'View All Services',
                    'view_all_url' => '/services',
                    'items' => [
                        [
                            'id' => 'web',
                            'num' => '01',
                            'icon' => '🌐',
                            'name' => 'Web Development',
                            'headline' => 'Modern Web Applications Built for Scale',
                            'desc' => 'High-performance web applications built with modern stacks. React, Next.js, Node.js — end-to-end from architecture to deployment. We engineer solutions that handle millions of users without breaking a sweat.',
                            'tags' => ['React', 'Next.js', 'Node.js', 'TypeScript'],
                            'highlights' => ['Server-side rendering & static generation', 'API design & microservices', 'Performance optimization & SEO', 'CI/CD & cloud deployment'],
                            'link_url' => '/services/web-development',
                        ],
                        [
                            'id' => 'mobile',
                            'num' => '02',
                            'icon' => '📱',
                            'name' => 'Mobile Apps',
                            'headline' => 'Native & Cross-Platform Mobile Experiences',
                            'desc' => 'Native and cross-platform iOS & Android apps built for performance, usability, and long-term scale. React Native & Flutter specialists delivering pixel-perfect apps that users love.',
                            'tags' => ['React Native', 'Flutter', 'iOS', 'Android'],
                            'highlights' => ['Cross-platform code sharing', 'Native performance & animations', 'Offline-first architecture', 'App Store optimization'],
                            'link_url' => '/services/android-development',
                        ],
                        [
                            'id' => 'design',
                            'num' => '03',
                            'icon' => '🎨',
                            'name' => 'UI / UX Design',
                            'headline' => 'Research-Driven Design That Converts',
                            'desc' => 'Research-driven design that balances beautiful aesthetics with intuitive, friction-free user flows. From wireframes to polished interfaces — every pixel serves a purpose.',
                            'tags' => ['Figma', 'Prototyping', 'User Research', 'Design Systems'],
                            'highlights' => ['User journey mapping & research', 'Wireframes & interactive prototypes', 'Design system architecture', 'Usability testing & iteration'],
                            'link_url' => '/services/ui-ux-design',
                        ],
                        [
                            'id' => 'ai',
                            'num' => '04',
                            'icon' => '🤖',
                            'name' => 'AI Solutions',
                            'headline' => 'Intelligent Automation & AI Integration',
                            'desc' => 'AI-powered platforms that transform how businesses operate and scale. From intelligent automation to machine learning integrations — we build the future of your product.',
                            'tags' => ['Machine Learning', 'NLP', 'Computer Vision', 'LLMs'],
                            'highlights' => ['Custom model training & fine-tuning', 'LLM integration & prompt engineering', 'Predictive analytics dashboards', 'Intelligent workflow automation'],
                            'link_url' => '/services/ai-agents',
                        ],
                        [
                            'id' => 'saas',
                            'num' => '05',
                            'icon' => '🚀',
                            'name' => 'SaaS Platforms',
                            'headline' => 'Scalable SaaS Products Built for Growth',
                            'desc' => 'Multi-tenant cloud architectures, subscription billing, and enterprise-grade SaaS products built for growth from day one. We handle the complexity so you can focus on your market.',
                            'tags' => ['Multi-tenant', 'Stripe', 'Auth', 'Cloud'],
                            'highlights' => ['Multi-tenant architecture design', 'Subscription & billing integration', 'Role-based access control', 'Analytics & usage tracking'],
                            'link_url' => '/services/saas-platforms',
                        ],
                    ],
                ],
            ],
            [
                'section_key' => 'industries',
                'sort_order' => 4,
                'content' => [
                    'tag' => 'Industries',
                    'title' => 'Industries We',
                    'title_accent' => 'Serve',
                    'subtitle' => "Deep domain expertise across verticals — we don't just build software, we understand your business.",
                    'items' => [
                        ['id' => 'healthcare', 'name' => 'Healthcare', 'tagline' => 'HIPAA-compliant digital health', 'desc' => 'Hospital management, patient portals, appointment booking, and medical record systems built to compliance standards.', 'stats' => '12+ Projects Delivered', 'tools' => ['Patient Portals', 'EHR Systems', 'Telemedicine', 'Compliance']],
                        ['id' => 'education', 'name' => 'Education', 'tagline' => 'Modern learning platforms', 'desc' => 'School management systems, e-learning platforms, student portals, and digital classrooms for modern education.', 'stats' => '15+ Projects Delivered', 'tools' => ['LMS Platforms', 'Student Portals', 'Assessment Tools', 'Live Classes']],
                        ['id' => 'ecommerce', 'name' => 'E-Commerce', 'tagline' => 'High-converting storefronts', 'desc' => 'High-converting storefronts, POS systems, inventory management, and payment gateway integrations.', 'stats' => '20+ Projects Delivered', 'tools' => ['Storefront', 'Payments', 'Inventory', 'Analytics']],
                        ['id' => 'enterprise', 'name' => 'Enterprise & ERP', 'tagline' => 'Operations at scale', 'desc' => 'End-to-end ERP solutions for operations, HR, procurement, and finance — built to scale with your organization.', 'stats' => '8+ Projects Delivered', 'tools' => ['HR & Payroll', 'Procurement', 'Finance', 'Workflows']],
                        ['id' => 'fitness', 'name' => 'Fitness & Wellness', 'tagline' => 'Digital-first wellness brands', 'desc' => 'GYM management, membership tracking, class scheduling, and fitness app development for modern wellness brands.', 'stats' => '10+ Projects Delivered', 'tools' => ['Membership', 'Scheduling', 'Tracking', 'Mobile Apps']],
                        ['id' => 'crm', 'name' => 'CRM & Sales', 'tagline' => 'Revenue-driving platforms', 'desc' => 'Custom CRM platforms, lead pipelines, sales dashboards, and client management tools that drive revenue growth.', 'stats' => '14+ Projects Delivered', 'tools' => ['Lead Pipelines', 'Dashboards', 'Automation', 'Reporting']],
                        ['id' => 'marketing', 'name' => 'Marketing & Media', 'tagline' => 'Content & campaign tools', 'desc' => 'Social media scheduling tools, content dashboards, analytics platforms, and campaign management systems.', 'stats' => '9+ Projects Delivered', 'tools' => ['Scheduling', 'Analytics', 'Campaigns', 'Content CMS']],
                        ['id' => 'saas', 'name' => 'Startups & SaaS', 'tagline' => 'Built for rapid growth', 'desc' => 'Fast-tracked MVP development, product validation sprints, and scalable SaaS platforms built for rapid growth.', 'stats' => '25+ Projects Delivered', 'tools' => ['MVP Sprints', 'SaaS Infra', 'Billing', 'Scaling']],
                    ],
                ],
            ],
            [
                'section_key' => 'blog',
                'sort_order' => 5,
                'content' => [
                    'tag' => 'Blog',
                    'title' => 'Latest',
                    'title_accent' => 'Insights',
                    'view_all_text' => 'View All Posts',
                    'view_all_url' => '/blog',
                    // post_ids is populated below after the blog section is
                    // seeded — it depends on BlogSeeder having run already.
                    'post_ids' => [],
                ],
            ],
            [
                'section_key' => 'faq',
                'sort_order' => 6,
                'content' => [
                    'tag' => 'FAQ',
                    'title' => 'Frequently Asked',
                    'title_accent' => 'Questions',
                    'subtitle' => "Everything you need to know before working with us. Can't find your answer? Just ask us.",
                    'items' => [
                        ['q' => 'How long does it take to build a web application?', 'a' => "It depends on the complexity of the project. A simple website typically takes 2–4 weeks. A full web application or SaaS product can take anywhere from 6–16 weeks. After an initial discovery session, we'll provide you with a clear, detailed timeline."],
                        ['q' => 'What is your development process?', 'a' => 'We follow a four-phase process: Ideation → Design → Development → Deployment. Each phase ends with a review and approval checkpoint so you stay in control throughout. We use Agile sprints with weekly updates so you always know where things stand.'],
                        ['q' => 'Do you provide post-launch support?', 'a' => 'Absolutely. We offer dedicated support packages that include bug fixes, performance monitoring, feature additions, and security updates. All new projects include a 30-day complimentary post-launch support window.'],
                        ['q' => 'Can you work with our existing tech stack?', 'a' => "Yes. We're technology-agnostic and work with a wide range of stacks. Whether you're on React, Laravel, Node, or something else — we'll audit your codebase and integrate seamlessly without forcing a migration."],
                        ['q' => 'How do you handle project pricing?', 'a' => "We offer both fixed-price and time-and-materials models depending on how well-defined your requirements are. For clear-scope projects, fixed pricing works great. For evolving products, a retainer or sprint-based model gives you more flexibility. We'll recommend the right fit after our initial call."],
                        ['q' => 'Do you sign NDAs and handle IP ownership?', 'a' => 'Yes. We sign NDAs before any project discussion if required, and upon project completion, all intellectual property, source code, and assets are fully transferred to you. You own everything we build for you — no licensing fees, no strings attached.'],
                    ],
                ],
            ],
            [
                'section_key' => 'testimonials',
                'sort_order' => 7,
                'content' => [
                    'label' => 'Client Stories',
                    'items' => [
                        ['stars' => 5, 'text' => 'We had an incredible experience working with DigiDittos — impressed by the massive difference they made in only three weeks. Our team is grateful for their rapid grasp of our product concept.', 'name' => 'Hunzila Sameer', 'role' => 'CEO', 'company' => 'Tech Startup'],
                        ['stars' => 5, 'text' => 'DigiDittos built our School Management System from scratch. The attention to detail, the clean UI, and the speed of delivery were beyond what we expected. Highly recommended.', 'name' => 'Ahmed Raza', 'role' => 'Principal', 'company' => 'Horizon Academy'],
                        ['stars' => 5, 'text' => 'Our hospital operations transformed after DigiDittos delivered the HMS. Patient records, billing, and appointments — all unified in one seamless platform. Exceptional work.', 'name' => 'Dr. Farhan Malik', 'role' => 'Medical Director', 'company' => 'MedCare Hospital'],
                        ['stars' => 5, 'text' => 'The CRM system DigiDittos built completely changed how we manage our sales pipeline. Our team onboarded in days and our conversion rate jumped significantly within the first month.', 'name' => 'Sara Khan', 'role' => 'Head of Sales', 'company' => 'Venture Corp'],
                        ['stars' => 5, 'text' => 'From design to deployment, DigiDittos handled our mobile app with precision. The user experience is smooth, intuitive, and our app store ratings reflect that. Great partners.', 'name' => 'Usman Tariq', 'role' => 'Founder & CEO', 'company' => 'LaunchPad'],
                    ],
                ],
            ],
            [
                'section_key' => 'cta',
                'sort_order' => 8,
                'content' => [
                    'heading_line_1' => "Let's build",
                    'heading_line_2' => 'something great',
                    'heading_line_3' => 'together.',
                    'description' => "Whether you're looking to collaborate, hire, or just say hello — feel free to reach out.",
                    'button_text' => 'Send Message',
                    'button_url' => '/contact',
                    'bubbles' => ['UI/UX', 'Manager', 'DevOps', 'Developer', 'Backend', 'Frontend'],
                ],
            ],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                [
                    'title' => null,
                    'content' => $section['content'],
                    'sort_order' => $section['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $this->seedHomepageBlogSelection();
    }

    /**
     * Pick the top 3 most-viewed published posts as the default Blog section
     * selection. Only applies on a fresh seed — if an admin has already chosen
     * posts in the CMS, we don't clobber their choice.
     */
    private function seedHomepageBlogSelection(): void
    {
        $blogSection = HomepageSection::where('section_key', 'blog')->first();
        if (! $blogSection) return;

        $content = $blogSection->content ?? [];
        if (! empty($content['post_ids'])) return;

        $topIds = BlogPost::published()
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit(3)
            ->pluck('id')
            ->all();

        if (empty($topIds)) return;

        $content['post_ids'] = $topIds;
        $blogSection->content = $content;
        $blogSection->save();
    }
}
