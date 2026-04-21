<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceFeature;
use Illuminate\Database\Seeder;

/**
 * Seed the 19 DigiDittos service features (sub-services) the business
 * site renders under /services/{slug}. Data ported verbatim from
 * digiditttos-business/data/service-details.ts (Overview / Approach /
 * Technologies) plus per-feature SEO.
 *
 * Legacy pre-upgrade features (client-management, document-workflow,
 * drag-drop-builder, …) are deleted on reseed so admins don't end up
 * with orphaned rows.
 */
class FeatureSeeder extends Seeder
{
    /** Default approach steps shared by features that don't override. */
    private const DEFAULT_APPROACH = [
        ['title' => 'Discovery & Strategy', 'description' => 'We dive deep into your goals, audience, and challenges to build a clear roadmap for success.'],
        ['title' => 'Design & Prototyping', 'description' => 'Transforming insights into bold, user-focused designs that connect and convert.'],
        ['title' => 'Development & Launch', 'description' => 'From pixel to code, we craft high-performing solutions and launch them flawlessly.'],
        ['title' => 'Optimization & Scale', 'description' => 'We monitor, refine, and enhance to ensure continuous growth and lasting impact.'],
    ];

    public function run(): void
    {
        // Strip legacy pre-upgrade feature slugs.
        ServiceFeature::whereIn('slug', [
            'client-management', 'document-workflow', 'service-authorization',
            'timesheet-management', 'hipaa-compliance',
            'drag-drop-builder', 'multi-tenant-workspaces',
            'form-templates', 'submission-management', 'role-based-access-control',
        ])->each(function (ServiceFeature $f) {
            $f->services()->detach();
            $f->media()->delete();
            $f->delete();
        });

        // Map parent service slug → Service model
        $serviceBySlug = Service::whereIn('slug', [
            'strategic-consultancy', 'software-engineering',
            'ai-solutions', 'digital-marketing', 'cms-development',
        ])->get()->keyBy('slug');

        foreach ($this->features() as $sortOrder => $data) {
            $feature = ServiceFeature::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'feature_key'          => $data['slug'],
                    'title'                => $data['title'],
                    'description'          => $data['description'],
                    'headline'             => $data['headline'],
                    'hero_description'     => $data['description'],
                    'overview_title'       => $data['overview_title'],
                    'overview_description' => $data['overview_description'],
                    'overview'             => $data['overview'],
                    'process_title'        => 'Our Approach',
                    'process_steps'        => $data['approach'] ?? self::DEFAULT_APPROACH,
                    'technologies'         => $data['technologies'],
                    'items'                => [],
                    'cta_title'            => null,
                    'cta_description'      => null,
                    'cta_button_text'      => null,
                    'cta_button_url'       => null,
                    'is_active'            => true,
                    'sort_order'           => $sortOrder,
                    'meta_title'           => $data['meta_title'],
                    'meta_description'     => $data['meta_description'],
                    'meta_keywords'        => $data['meta_keywords'] ?? [],
                    'og_title'             => $data['meta_title'],
                    'og_description'       => $data['meta_description'],
                    'og_image'             => null,
                    'json_ld_schema'       => null,
                    'field_types'          => null,
                ],
            );

            // Assign to parent service with positional sort_order.
            $parent = $serviceBySlug->get($data['parent_slug']);
            if ($parent) {
                $parent->features()->syncWithoutDetaching([
                    $feature->id => ['sort_order' => $data['parent_sort']],
                ]);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function features(): array
    {
        return [
            // ── Strategic Consultancy ──────────────────────────────
            [
                'slug'            => 'architecture-review',
                'parent_slug'     => 'strategic-consultancy',
                'parent_sort'     => 0,
                'title'           => 'Architecture Review',
                'headline'        => "Identify what's holding your system back",
                'description'     => 'Deep analysis of your existing system — identifying bottlenecks, single points of failure, and opportunities to simplify.',
                'overview_title'  => 'Thorough Architecture Review',
                'overview_description' => "We examine every layer of your system to find what's slowing you down and what needs to change before you scale.",
                'overview' => [
                    ['title' => 'System Mapping.',     'desc' => 'Complete visualization of your current architecture — services, data flows, dependencies, and failure points.'],
                    ['title' => 'Bottleneck Analysis.', 'desc' => 'Identify the exact components causing latency, downtime, or scaling limitations under real-world load.'],
                    ['title' => 'Risk Assessment.',    'desc' => 'Evaluate single points of failure, security gaps, and technical debt that could derail future growth.'],
                    ['title' => 'Actionable Roadmap.', 'desc' => 'A prioritized plan of improvements with effort estimates, so you know exactly what to fix first.'],
                ],
                'technologies' => [
                    ['name' => 'AWS', 'category' => 'Cloud Platforms'], ['name' => 'Azure', 'category' => 'Cloud Platforms'], ['name' => 'Google Cloud', 'category' => 'Cloud Platforms'],
                    ['name' => 'Docker', 'category' => 'Containerization'], ['name' => 'Kubernetes', 'category' => 'Containerization'],
                    ['name' => 'React', 'category' => 'Frontend'], ['name' => 'Node.js', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'], ['name' => 'Redis', 'category' => 'Databases'],
                    ['name' => 'Grafana', 'category' => 'Monitoring'], ['name' => 'Elasticsearch', 'category' => 'Monitoring'],
                ],
                'meta_title'       => 'Architecture Review — Strategic Consultancy | DigiDittos',
                'meta_description' => 'Thorough system architecture review — mapping, bottlenecks, risks, and an actionable roadmap to scale.',
                'meta_keywords'    => ['architecture review', 'system audit', 'technical debt', 'scalability', 'consultancy'],
            ],
            [
                'slug'            => 'code-quality-report',
                'parent_slug'     => 'strategic-consultancy',
                'parent_sort'     => 1,
                'title'           => 'Code Quality Report',
                'headline'        => 'Know the real health of your codebase',
                'description'     => 'Comprehensive audit of code health — test coverage, technical debt, security vulnerabilities, and maintainability scoring.',
                'overview_title'  => 'Comprehensive Code Quality Audit',
                'overview_description' => "We go beyond surface-level linting to give you an honest assessment of your codebase's real health.",
                'overview' => [
                    ['title' => 'Test Coverage.',       'desc' => 'Analysis of unit, integration, and E2E test coverage with gap identification and priority recommendations.'],
                    ['title' => 'Technical Debt.',      'desc' => "Quantified debt scoring across modules — what's slowing your team down and what's safe to ignore."],
                    ['title' => 'Security Scan.',       'desc' => 'Automated and manual vulnerability detection across dependencies, auth flows, and data handling.'],
                    ['title' => 'Maintainability Score.', 'desc' => 'A clear rating of code readability, documentation, and architectural consistency for your team.'],
                ],
                'technologies' => [
                    ['name' => 'TypeScript', 'category' => 'Languages'], ['name' => 'JavaScript', 'category' => 'Languages'], ['name' => 'Python', 'category' => 'Languages'],
                    ['name' => 'Docker', 'category' => 'Containerization'],
                    ['name' => 'Node.js', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'],
                    ['name' => 'Grafana', 'category' => 'Monitoring'],
                ],
                'meta_title'       => 'Code Quality Report — Strategic Consultancy | DigiDittos',
                'meta_description' => 'Honest audit of test coverage, technical debt, security, and maintainability — so you know where to invest.',
                'meta_keywords'    => ['code quality', 'technical debt', 'security audit', 'test coverage', 'maintainability'],
            ],
            [
                'slug'            => 'scalability-assessment',
                'parent_slug'     => 'strategic-consultancy',
                'parent_sort'     => 2,
                'title'           => 'Scalability Assessment',
                'headline'        => 'Prepare your system for 10x growth',
                'description'     => 'Load testing, capacity planning, and infrastructure recommendations to ensure your system handles real-world scale.',
                'overview_title'  => 'End-to-End Scalability Assessment',
                'overview_description' => 'We stress-test your system and deliver a clear plan to handle 10x your current traffic without breaking.',
                'overview' => [
                    ['title' => 'Load Testing.',         'desc' => 'Simulated traffic at 5x, 10x, and peak levels to identify exactly where your system breaks.'],
                    ['title' => 'Capacity Planning.',    'desc' => 'Right-sized infrastructure recommendations based on your growth projections and budget.'],
                    ['title' => 'Database Optimization.', 'desc' => 'Query analysis, indexing strategy, and connection pool tuning for sustained high throughput.'],
                ],
                'technologies' => [
                    ['name' => 'AWS', 'category' => 'Cloud Platforms'], ['name' => 'Google Cloud', 'category' => 'Cloud Platforms'],
                    ['name' => 'Docker', 'category' => 'Containerization'], ['name' => 'Kubernetes', 'category' => 'Containerization'],
                    ['name' => 'Redis', 'category' => 'Databases'], ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'],
                    ['name' => 'Kafka', 'category' => 'Backend'], ['name' => 'Elasticsearch', 'category' => 'Monitoring'], ['name' => 'Grafana', 'category' => 'Monitoring'],
                ],
                'meta_title'       => 'Scalability Assessment — Strategic Consultancy | DigiDittos',
                'meta_description' => 'Load testing, capacity planning, and database tuning to handle 10x growth without breaking.',
                'meta_keywords'    => ['scalability', 'load testing', 'capacity planning', 'performance', 'database tuning'],
            ],

            // ── Software Engineering ───────────────────────────────
            [
                'slug'            => 'web-development',
                'parent_slug'     => 'software-engineering',
                'parent_sort'     => 0,
                'title'           => 'Web Development',
                'headline'        => 'Modern web applications built for scale',
                'description'     => 'High-performance web applications with React, Next.js, Node.js — server-rendered, SEO-optimized, and built to handle real traffic.',
                'overview_title'  => 'Full-Stack Web Development',
                'overview_description' => 'We build web applications that are fast, accessible, and engineered to grow with your business.',
                'overview' => [
                    ['title' => 'Frontend Engineering.', 'desc' => 'React and Next.js applications with server-side rendering, static generation, and optimized Core Web Vitals.'],
                    ['title' => 'Backend & APIs.',       'desc' => 'Node.js services with RESTful and GraphQL APIs, authentication, and real-time capabilities.'],
                    ['title' => 'Database Design.',      'desc' => 'PostgreSQL, MongoDB, or Redis — schema design, migrations, and query optimization from day one.'],
                    ['title' => 'DevOps & Deployment.',  'desc' => 'CI/CD pipelines, Docker containers, and cloud deployment on AWS, Vercel, or your preferred platform.'],
                ],
                'technologies' => [
                    ['name' => 'React', 'category' => 'Frontend'], ['name' => 'Next.js', 'category' => 'Frontend'], ['name' => 'Vue.js', 'category' => 'Frontend'], ['name' => 'TypeScript', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'], ['name' => 'FastAPI', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'], ['name' => 'Redis', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'], ['name' => 'Vercel', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'Web Development — Software Engineering | DigiDittos',
                'meta_description' => 'High-performance React, Next.js, and Node.js web applications engineered to scale.',
                'meta_keywords'    => ['web development', 'react', 'next.js', 'node.js', 'full stack'],
            ],
            [
                'slug'            => 'android-development',
                'parent_slug'     => 'software-engineering',
                'parent_sort'     => 1,
                'title'           => 'Android Development',
                'headline'        => 'Native Android apps users love',
                'description'     => 'Kotlin-first native apps with Material Design, deep platform integration, and Play Store optimization.',
                'overview_title'  => 'Native Android Development',
                'overview_description' => 'We build Android apps that feel native, perform flawlessly, and leverage the full power of the platform.',
                'overview' => [
                    ['title' => 'Kotlin-First.',             'desc' => 'Modern Kotlin codebases with coroutines, Jetpack Compose, and clean architecture patterns.'],
                    ['title' => 'Material Design.',          'desc' => "Pixel-perfect UI following Google's Material Design 3 guidelines for intuitive user experiences."],
                    ['title' => 'Platform Integration.',     'desc' => 'Deep integration with Android APIs — camera, location, notifications, biometrics, and background services.'],
                    ['title' => 'Play Store Optimization.',  'desc' => 'Store listing optimization, staged rollouts, and crash-free rate monitoring for successful launches.'],
                ],
                'technologies' => [
                    ['name' => 'Kotlin', 'category' => 'Languages'], ['name' => 'Dart', 'category' => 'Languages'],
                    ['name' => 'Flutter', 'category' => 'Frameworks'], ['name' => 'Firebase', 'category' => 'Frameworks'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'Google Cloud', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'Android Development — Software Engineering | DigiDittos',
                'meta_description' => 'Kotlin-first native Android apps with Material Design and Play Store optimization.',
                'meta_keywords'    => ['android development', 'kotlin', 'jetpack compose', 'material design', 'mobile'],
            ],
            [
                'slug'            => 'ios-development',
                'parent_slug'     => 'software-engineering',
                'parent_sort'     => 2,
                'title'           => 'iOS Development',
                'headline'        => 'Polished iOS apps for the Apple ecosystem',
                'description'     => 'Swift-powered native apps — smooth, secure, and App Store ready from day one.',
                'overview_title'  => 'Native iOS Development',
                'overview_description' => "We craft iOS apps that meet Apple's high standards and deliver experiences users trust.",
                'overview' => [
                    ['title' => 'Swift & SwiftUI.',  'desc' => 'Modern Swift codebases with SwiftUI for declarative interfaces and smooth animations.'],
                    ['title' => 'Apple Ecosystem.',  'desc' => 'Integration with HealthKit, CloudKit, Sign in with Apple, widgets, and Apple Watch extensions.'],
                    ['title' => 'Performance.',      'desc' => 'Memory management, lazy loading, and Instruments profiling for butter-smooth 60fps experiences.'],
                    ['title' => 'App Store Ready.',  'desc' => 'Compliance with Apple guidelines, TestFlight distribution, and App Store Connect optimization.'],
                ],
                'technologies' => [
                    ['name' => 'Swift', 'category' => 'Languages'], ['name' => 'Dart', 'category' => 'Languages'],
                    ['name' => 'Flutter', 'category' => 'Frameworks'], ['name' => 'Firebase', 'category' => 'Frameworks'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'iOS Development — Software Engineering | DigiDittos',
                'meta_description' => 'Swift and SwiftUI native iOS apps, App Store ready with full Apple ecosystem integration.',
                'meta_keywords'    => ['ios development', 'swift', 'swiftui', 'apple', 'mobile'],
            ],
            [
                'slug'            => 'ui-ux-design',
                'parent_slug'     => 'software-engineering',
                'parent_sort'     => 3,
                'title'           => 'UI / UX Design',
                'headline'        => 'Research-driven design that converts',
                'description'     => 'Wireframes, prototypes, and design systems that turn visitors into users — every pixel serves a purpose.',
                'overview_title'  => 'Research-Driven UI/UX Design',
                'overview_description' => 'We design interfaces grounded in real user behavior — not guesswork, not trends, just what works.',
                'overview' => [
                    ['title' => 'User Research.',           'desc' => 'Interviews, surveys, and usability testing that reveal what your users actually need and expect.'],
                    ['title' => 'Wireframes & Prototypes.', 'desc' => 'Interactive Figma prototypes that validate ideas before development begins — saving time and money.'],
                    ['title' => 'Visual Design.',           'desc' => 'Polished, on-brand interfaces with consistent typography, spacing, and color systems.'],
                    ['title' => 'Design Systems.',          'desc' => 'Reusable component libraries and design tokens that scale across your entire product.'],
                ],
                'technologies' => [
                    ['name' => 'Figma', 'category' => 'Design'], ['name' => 'Sketch', 'category' => 'Design'], ['name' => 'Adobe XD', 'category' => 'Design'],
                    ['name' => 'React', 'category' => 'Frontend'], ['name' => 'Next.js', 'category' => 'Frontend'], ['name' => 'TypeScript', 'category' => 'Frontend'],
                    ['name' => 'Flutter', 'category' => 'Frameworks'], ['name' => 'Swift', 'category' => 'Frameworks'],
                ],
                'meta_title'       => 'UI / UX Design — Software Engineering | DigiDittos',
                'meta_description' => 'Research-driven UI and UX design — wireframes, prototypes, and design systems that convert.',
                'meta_keywords'    => ['ui design', 'ux design', 'figma', 'design systems', 'user research'],
            ],
            [
                'slug'            => 'saas-platforms',
                'parent_slug'     => 'software-engineering',
                'parent_sort'     => 4,
                'title'           => 'SaaS Platforms',
                'headline'        => 'Scalable SaaS products built for growth',
                'description'     => 'Multi-tenant architectures with subscription billing, RBAC, and cloud infrastructure designed to evolve.',
                'overview_title'  => 'SaaS Platform Engineering',
                'overview_description' => 'We build the technical foundation that lets you focus on your market while the platform handles the complexity.',
                'overview' => [
                    ['title' => 'Multi-Tenancy.',           'desc' => 'Secure tenant isolation with shared infrastructure — efficient, scalable, and cost-effective.'],
                    ['title' => 'Billing & Subscriptions.', 'desc' => 'Stripe integration with usage-based billing, plan management, invoicing, and dunning workflows.'],
                    ['title' => 'Auth & RBAC.',             'desc' => 'Role-based access control, SSO, OAuth, and enterprise identity management out of the box.'],
                    ['title' => 'Analytics.',               'desc' => 'Usage tracking, feature adoption metrics, and admin dashboards for data-driven product decisions.'],
                ],
                'technologies' => [
                    ['name' => 'React', 'category' => 'Frontend'], ['name' => 'Next.js', 'category' => 'Frontend'], ['name' => 'TypeScript', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'], ['name' => 'Stripe', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'Redis', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'Kubernetes', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'SaaS Platforms — Software Engineering | DigiDittos',
                'meta_description' => 'Multi-tenant SaaS platforms with Stripe billing, RBAC, SSO, and cloud infrastructure built to scale.',
                'meta_keywords'    => ['saas', 'multi-tenant', 'stripe', 'rbac', 'platform engineering'],
            ],

            // ── AI Solutions ───────────────────────────────────────
            [
                'slug'            => 'ai-agents',
                'parent_slug'     => 'ai-solutions',
                'parent_sort'     => 0,
                'title'           => 'AI Agents',
                'headline'        => 'Autonomous agents that work for you',
                'description'     => 'Intelligent agents that handle complex workflows, decisions, and multi-step reasoning — replacing manual processes at scale.',
                'overview_title'  => 'Intelligent AI Agent Development',
                'overview_description' => "We build agents that reason, decide, and act — handling the complex workflows your team shouldn't do manually.",
                'overview' => [
                    ['title' => 'Task Automation.',   'desc' => 'Agents that handle multi-step workflows — data extraction, decision-making, and action execution without human intervention.'],
                    ['title' => 'Tool Integration.',  'desc' => 'Connect agents to your APIs, databases, and third-party services for real-world impact.'],
                    ['title' => 'Memory & Context.',  'desc' => 'Persistent memory and conversation context so agents learn and improve across interactions.'],
                    ['title' => 'Guardrails.',        'desc' => 'Safety boundaries, cost controls, and human-in-the-loop checkpoints for production reliability.'],
                ],
                'technologies' => [
                    ['name' => 'Python', 'category' => 'Languages'], ['name' => 'TypeScript', 'category' => 'Languages'],
                    ['name' => 'TensorFlow', 'category' => 'AI / ML'], ['name' => 'FastAPI', 'category' => 'AI / ML'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'Redis', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'], ['name' => 'Elasticsearch', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'AI Agents — AI Solutions | DigiDittos',
                'meta_description' => 'Autonomous AI agents with tool integration, memory, and guardrails for production workflows.',
                'meta_keywords'    => ['ai agents', 'agentic ai', 'automation', 'llm', 'autonomous agents'],
            ],
            [
                'slug'            => 'langchain',
                'parent_slug'     => 'ai-solutions',
                'parent_sort'     => 1,
                'title'           => 'LangChain',
                'headline'        => 'Orchestrated LLM pipelines that deliver',
                'description'     => 'Production-grade LLM pipelines with memory, retrieval-augmented generation, and tool integrations.',
                'overview_title'  => 'LangChain Pipeline Development',
                'overview_description' => 'We build structured LLM workflows that go beyond simple prompts — with memory, tools, and production reliability.',
                'overview' => [
                    ['title' => 'RAG Pipelines.',      'desc' => 'Retrieval-augmented generation with vector databases for accurate, context-aware AI responses.'],
                    ['title' => 'Chain Orchestration.', 'desc' => 'Multi-step LLM chains that break complex tasks into manageable, reliable stages.'],
                    ['title' => 'Tool Calling.',       'desc' => 'LLMs that can search, calculate, call APIs, and interact with your business systems.'],
                ],
                'technologies' => [
                    ['name' => 'Python', 'category' => 'Languages'], ['name' => 'TypeScript', 'category' => 'Languages'],
                    ['name' => 'TensorFlow', 'category' => 'AI / ML'], ['name' => 'FastAPI', 'category' => 'AI / ML'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'Redis', 'category' => 'Databases'], ['name' => 'Elasticsearch', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'LangChain — AI Solutions | DigiDittos',
                'meta_description' => 'Production-grade LangChain pipelines with RAG, chain orchestration, and tool calling.',
                'meta_keywords'    => ['langchain', 'rag', 'llm pipelines', 'vector database', 'ai'],
            ],
            [
                'slug'            => 'langgraph',
                'parent_slug'     => 'ai-solutions',
                'parent_sort'     => 2,
                'title'           => 'LangGraph',
                'headline'        => 'Stateful AI workflows with control',
                'description'     => 'Multi-actor AI workflows with branching logic, human-in-the-loop, and persistent memory for complex use cases.',
                'overview_title'  => 'LangGraph Workflow Engineering',
                'overview_description' => "For AI systems that need state, branching, and human oversight — LangGraph gives you the control LangChain alone can't.",
                'overview' => [
                    ['title' => 'Stateful Graphs.',     'desc' => 'Persistent state across workflow nodes — agents remember context and resume where they left off.'],
                    ['title' => 'Branching Logic.',     'desc' => 'Conditional paths, parallel execution, and dynamic routing based on AI decisions.'],
                    ['title' => 'Human-in-the-Loop.',   'desc' => 'Approval gates, review steps, and manual overrides for high-stakes decisions.'],
                ],
                'technologies' => [
                    ['name' => 'Python', 'category' => 'Languages'], ['name' => 'TypeScript', 'category' => 'Languages'],
                    ['name' => 'TensorFlow', 'category' => 'AI / ML'], ['name' => 'FastAPI', 'category' => 'AI / ML'],
                    ['name' => 'Redis', 'category' => 'Databases'], ['name' => 'PostgreSQL', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'Kubernetes', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'LangGraph — AI Solutions | DigiDittos',
                'meta_description' => 'Stateful LangGraph workflows with branching logic and human-in-the-loop controls.',
                'meta_keywords'    => ['langgraph', 'ai workflows', 'stateful agents', 'human in the loop'],
            ],
            [
                'slug'            => 'nlp-solutions',
                'parent_slug'     => 'ai-solutions',
                'parent_sort'     => 3,
                'title'           => 'NLP Solutions',
                'headline'        => 'Turn unstructured text into business value',
                'description'     => 'Text analysis, sentiment detection, entity extraction, and conversational AI systems.',
                'overview_title'  => 'Natural Language Processing',
                'overview_description' => 'We build systems that understand, analyze, and generate human language for real business applications.',
                'overview' => [
                    ['title' => 'Text Analysis.',      'desc' => 'Classification, summarization, and keyword extraction from documents, emails, and support tickets.'],
                    ['title' => 'Sentiment Detection.', 'desc' => 'Real-time sentiment scoring for reviews, social media, and customer feedback at scale.'],
                    ['title' => 'Entity Extraction.',  'desc' => 'Pull structured data — names, dates, amounts, addresses — from unstructured text automatically.'],
                    ['title' => 'Conversational AI.',  'desc' => 'Chatbots and virtual assistants that handle real customer conversations with context and nuance.'],
                ],
                'technologies' => [
                    ['name' => 'Python', 'category' => 'Languages'],
                    ['name' => 'TensorFlow', 'category' => 'AI / ML'], ['name' => 'FastAPI', 'category' => 'AI / ML'],
                    ['name' => 'Node.js', 'category' => 'Backend'],
                    ['name' => 'Elasticsearch', 'category' => 'Databases'], ['name' => 'PostgreSQL', 'category' => 'Databases'], ['name' => 'MongoDB', 'category' => 'Databases'], ['name' => 'Redis', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'NLP Solutions — AI Solutions | DigiDittos',
                'meta_description' => 'Natural language processing — text analysis, sentiment, entity extraction, and conversational AI.',
                'meta_keywords'    => ['nlp', 'natural language processing', 'sentiment analysis', 'chatbots'],
            ],
            [
                'slug'            => 'llm-integration',
                'parent_slug'     => 'ai-solutions',
                'parent_sort'     => 4,
                'title'           => 'LLM Integration',
                'headline'        => 'Ship AI features with confidence',
                'description'     => 'Production-grade OpenAI, Claude, and open-source model integrations with guardrails and cost optimization.',
                'overview_title'  => 'Production LLM Integration',
                'overview_description' => 'We integrate large language models into your product with the reliability, safety, and cost controls production demands.',
                'overview' => [
                    ['title' => 'Model Selection.',      'desc' => 'Choosing the right model — OpenAI, Claude, Llama, or Mistral — based on your accuracy, cost, and latency needs.'],
                    ['title' => 'Prompt Engineering.',   'desc' => 'Optimized prompts with few-shot examples, chain-of-thought, and structured outputs for consistent results.'],
                    ['title' => 'Cost Optimization.',    'desc' => 'Caching, batching, and model routing strategies that cut API costs by 40-70% without quality loss.'],
                    ['title' => 'Safety & Guardrails.',  'desc' => 'Content filtering, output validation, and fallback logic for production-safe AI features.'],
                ],
                'technologies' => [
                    ['name' => 'Python', 'category' => 'Languages'], ['name' => 'TypeScript', 'category' => 'Languages'],
                    ['name' => 'TensorFlow', 'category' => 'AI / ML'], ['name' => 'FastAPI', 'category' => 'AI / ML'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'],
                    ['name' => 'Redis', 'category' => 'Databases'], ['name' => 'PostgreSQL', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'], ['name' => 'Vercel', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'LLM Integration — AI Solutions | DigiDittos',
                'meta_description' => 'Production-grade LLM integrations with prompt engineering, cost optimization, and guardrails.',
                'meta_keywords'    => ['llm', 'openai', 'claude', 'prompt engineering', 'ai integration'],
            ],

            // ── Digital Marketing ──────────────────────────────────
            [
                'slug'            => 'seo',
                'parent_slug'     => 'digital-marketing',
                'parent_sort'     => 0,
                'title'           => 'SEO',
                'headline'        => 'Rank higher, get found, grow organic',
                'description'     => 'Technical audits, on-page optimization, keyword strategy, and content planning that drive sustainable organic rankings.',
                'overview_title'  => 'Search Engine Optimization',
                'overview_description' => 'We combine technical SEO expertise with content strategy to drive rankings that actually bring qualified traffic.',
                'overview' => [
                    ['title' => 'Technical Audit.',        'desc' => 'Site speed, crawlability, indexation, schema markup, and Core Web Vitals optimization.'],
                    ['title' => 'Keyword Strategy.',       'desc' => 'Data-driven keyword research targeting high-intent queries your competitors are missing.'],
                    ['title' => 'On-Page Optimization.',   'desc' => 'Title tags, meta descriptions, heading structure, and internal linking that search engines reward.'],
                    ['title' => 'Content Planning.',       'desc' => 'Editorial calendars and topic clusters that build topical authority over time.'],
                ],
                'technologies' => [
                    ['name' => 'Next.js', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'Vercel', 'category' => 'Backend'],
                    ['name' => 'Google Cloud', 'category' => 'Analytics'], ['name' => 'Elasticsearch', 'category' => 'Analytics'],
                ],
                'meta_title'       => 'SEO — Digital Marketing | DigiDittos',
                'meta_description' => 'Technical SEO, keyword strategy, and content planning that drives sustainable organic rankings.',
                'meta_keywords'    => ['seo', 'technical seo', 'keyword research', 'on-page seo', 'content strategy'],
            ],
            [
                'slug'            => 'meta-marketing',
                'parent_slug'     => 'digital-marketing',
                'parent_sort'     => 1,
                'title'           => 'Meta Marketing',
                'headline'        => 'Reach the right audience on Meta',
                'description'     => 'Facebook and Instagram ad campaigns — audience targeting, creative optimization, and conversion tracking at scale.',
                'overview_title'  => 'Meta Ads & Social Marketing',
                'overview_description' => 'We run Meta campaigns that find your ideal audience, test creative at scale, and optimize for real conversions.',
                'overview' => [
                    ['title' => 'Audience Targeting.',   'desc' => 'Lookalike audiences, interest stacking, and retargeting funnels built on your customer data.'],
                    ['title' => 'Creative Testing.',     'desc' => 'Systematic A/B testing of ad copy, visuals, and formats to find what converts best.'],
                    ['title' => 'Conversion Tracking.',  'desc' => 'Pixel setup, CAPI integration, and attribution modeling for accurate ROI measurement.'],
                ],
                'technologies' => [
                    ['name' => 'JavaScript', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'Python', 'category' => 'Backend'],
                    ['name' => 'Google Cloud', 'category' => 'Analytics'], ['name' => 'PostgreSQL', 'category' => 'Analytics'],
                ],
                'meta_title'       => 'Meta Marketing — Digital Marketing | DigiDittos',
                'meta_description' => 'Facebook and Instagram ads — audience targeting, creative testing, and conversion tracking at scale.',
                'meta_keywords'    => ['meta ads', 'facebook ads', 'instagram ads', 'social marketing'],
            ],
            [
                'slug'            => 'google-marketing',
                'parent_slug'     => 'digital-marketing',
                'parent_sort'     => 2,
                'title'           => 'Google Marketing',
                'headline'        => 'Maximize ROI on Google Ads',
                'description'     => 'Search campaigns, Display network, Shopping ads, and Analytics setup for measurable return on ad spend.',
                'overview_title'  => 'Google Ads & Analytics',
                'overview_description' => 'We build Google Ads campaigns that maximize every dollar of ad spend with precise targeting and measurement.',
                'overview' => [
                    ['title' => 'Search Campaigns.',       'desc' => 'Keyword-targeted search ads with optimized bidding strategies and quality score improvement.'],
                    ['title' => 'Display & Remarketing.',  'desc' => "Visual ads across the Google Display Network retargeting users who've shown intent."],
                    ['title' => 'Analytics Setup.',        'desc' => 'GA4 configuration, conversion tracking, custom dashboards, and data-driven attribution.'],
                ],
                'technologies' => [
                    ['name' => 'JavaScript', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'Python', 'category' => 'Backend'],
                    ['name' => 'Google Cloud', 'category' => 'Analytics'], ['name' => 'PostgreSQL', 'category' => 'Analytics'], ['name' => 'Grafana', 'category' => 'Analytics'],
                ],
                'meta_title'       => 'Google Marketing — Digital Marketing | DigiDittos',
                'meta_description' => 'Google Ads strategy — search, display, remarketing, and GA4 analytics for measurable ROI.',
                'meta_keywords'    => ['google ads', 'ppc', 'search ads', 'display network', 'ga4'],
            ],

            // ── CMS Development ────────────────────────────────────
            [
                'slug'            => 'wordpress',
                'parent_slug'     => 'cms-development',
                'parent_sort'     => 0,
                'title'           => 'WordPress',
                'headline'        => 'WordPress done right',
                'description'     => 'Custom themes, plugins, and headless WordPress builds tailored to your editorial workflow and business logic.',
                'overview_title'  => 'Custom WordPress Development',
                'overview_description' => 'We build WordPress sites that are fast, secure, and actually enjoyable for your content team to manage.',
                'overview' => [
                    ['title' => 'Custom Themes.',       'desc' => 'Bespoke theme development with clean code, responsive design, and brand-perfect execution.'],
                    ['title' => 'Plugin Development.',  'desc' => "Custom plugins for business logic, integrations, and workflows that off-the-shelf plugins can't handle."],
                    ['title' => 'Headless WordPress.', 'desc' => 'WordPress as a CMS backend with React/Next.js frontends for modern performance and flexibility.'],
                    ['title' => 'Security & Speed.',    'desc' => 'Hardened configurations, caching layers, and CDN setup for fast, secure WordPress sites.'],
                ],
                'technologies' => [
                    ['name' => 'WordPress', 'category' => 'CMS'],
                    ['name' => 'JavaScript', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'], ['name' => 'Next.js', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'MySQL', 'category' => 'Databases'],
                    ['name' => 'Docker', 'category' => 'DevOps'], ['name' => 'AWS', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'WordPress Development — CMS Development | DigiDittos',
                'meta_description' => 'Custom WordPress themes, plugins, and headless builds — fast, secure, and editor-friendly.',
                'meta_keywords'    => ['wordpress', 'wordpress development', 'headless wordpress', 'custom themes', 'plugins'],
            ],
            [
                'slug'            => 'shopify',
                'parent_slug'     => 'cms-development',
                'parent_sort'     => 1,
                'title'           => 'Shopify',
                'headline'        => 'E-commerce that converts',
                'description'     => 'Custom Shopify storefronts, theme development, app integrations, and checkout optimization for growth.',
                'overview_title'  => 'Shopify Store Development',
                'overview_description' => 'We build Shopify stores that look premium, load fast, and are optimized to turn browsers into buyers.',
                'overview' => [
                    ['title' => 'Custom Themes.',         'desc' => 'Unique Shopify themes built with Liquid, tailored to your brand and conversion goals.'],
                    ['title' => 'App Integration.',       'desc' => 'Third-party app setup and custom app development for inventory, shipping, and marketing automation.'],
                    ['title' => 'Checkout Optimization.', 'desc' => 'A/B tested checkout flows, upsells, and cart recovery strategies that increase average order value.'],
                ],
                'technologies' => [
                    ['name' => 'JavaScript', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'],
                    ['name' => 'Node.js', 'category' => 'Backend'], ['name' => 'GraphQL', 'category' => 'Backend'], ['name' => 'Stripe', 'category' => 'Backend'],
                    ['name' => 'PostgreSQL', 'category' => 'Databases'],
                ],
                'meta_title'       => 'Shopify Development — CMS Development | DigiDittos',
                'meta_description' => 'Custom Shopify storefronts, app integrations, and checkout optimization that convert.',
                'meta_keywords'    => ['shopify', 'ecommerce', 'shopify themes', 'checkout optimization'],
            ],
            [
                'slug'            => 'no-code-websites',
                'parent_slug'     => 'cms-development',
                'parent_sort'     => 2,
                'title'           => 'No-Code Websites',
                'headline'        => 'Launch fast without writing code',
                'description'     => 'Professional websites with Webflow, Framer, or Wix — fully responsive, beautifully designed, and client-manageable.',
                'overview_title'  => 'No-Code Website Development',
                'overview_description' => 'We design and launch professional websites on no-code platforms — fast, affordable, and fully in your control.',
                'overview' => [
                    ['title' => 'Webflow Development.', 'desc' => 'Custom Webflow sites with CMS collections, animations, and client-friendly editing capabilities.'],
                    ['title' => 'Framer Sites.',        'desc' => 'Interactive, animated websites built in Framer with component-based design and fast iteration.'],
                    ['title' => 'Client Training.',     'desc' => 'Hands-on training so your team can update content, add pages, and manage the site independently.'],
                ],
                'technologies' => [
                    ['name' => 'Figma', 'category' => 'Design'],
                    ['name' => 'JavaScript', 'category' => 'Frontend'], ['name' => 'React', 'category' => 'Frontend'],
                    ['name' => 'Vercel', 'category' => 'DevOps'],
                ],
                'meta_title'       => 'No-Code Websites — CMS Development | DigiDittos',
                'meta_description' => 'Webflow, Framer, and no-code website development — launch fast without writing code.',
                'meta_keywords'    => ['no-code', 'webflow', 'framer', 'no code websites'],
            ],
        ];
    }
}
