<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed 15 DigiDittos projects ported from the old Node/MongoDB backend.
 *
 * Every project is `status=published`. The first three (FinFlow, MedSync,
 * ShelfIQ) are flagged featured so the business site has sensible defaults
 * for any "featured projects" strip.
 */
class ProjectSeeder extends Seeder
{
    private const FEATURED_TITLES = [
        'FinFlow',
        'MedSync',
        'ShelfIQ',
    ];

    public function run(): void
    {
        $author = User::where('email', 'admin@digidittos.com')->first() ?? User::first();

        foreach ($this->projects() as $i => $data) {
            $slug = Str::slug($data['title']);

            $isFeatured = false;
            foreach (self::FEATURED_TITLES as $ft) {
                if (str_contains($data['title'], $ft)) { $isFeatured = true; break; }
            }

            $payload = [
                'title'            => $data['title'],
                'slug'             => $slug,
                'excerpt'          => $data['excerpt'] ?? Str::limit(strip_tags($data['description']), 240),
                'description'      => $data['description'],
                'featured_image'   => $data['featured_image'] ?? null,
                'client'           => $data['client'] ?? null,
                'category'         => $data['category'] ?? null,
                'duration'         => $data['duration'] ?? null,
                'year'             => $data['year'] ?? null,
                'live_url'         => $data['live_url'] ?? null,
                'tech_stack'       => $data['tech_stack'] ?? [],
                'tags'             => $data['tags'] ?? [],
                'gallery'          => $data['gallery'] ?? [],
                'highlights'       => $data['highlights'] ?? [],
                'key_features'     => $data['key_features'] ?? [],
                'author_id'        => $author?->id,
                'status'           => 'published',
                'published_at'     => now()->subDays(7 * ($i + 1)),
                'is_featured'      => $isFeatured,
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords'    => $data['meta_keywords'] ?? [],
            ];

            Project::updateOrCreate(['slug' => $slug], $payload);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'title' => 'FinFlow — Enterprise Payment Processing Platform',
                'description' => '<h2>The Situation</h2><ul><li>FinFlow processed $1.2B annually through a 15-year-old Java monolith on bare-metal servers</li><li>Three new enterprise customers signed, each requiring 2x current throughput</li><li>Platform needed PCI DSS Level 1 certification and real-time fraud scoring the legacy system lacked</li></ul><h2>The Challenge</h2><ul><li>Peak throughput capped at 2,000 TPS due to in-memory state preventing horizontal scaling</li><li>A bug in the email renderer once took down the entire payment pipeline for 47 minutes</li><li>Static fraud rules engine had a 4.7% false-positive rate with actual fraud losses climbing 12% QoQ</li></ul><h2>The Solution</h2><ul><li>Decomposed the monolith into four domain-bounded services communicating via Kafka with exactly-once semantics</li><li>Risk Engine scores transactions in under 50ms using 140+ behavioral signals with nightly model retraining</li><li>Stack runs on AWS EKS with ArgoCD-managed blue-green deployments and CloudHSM-backed tokenization for PCI compliance</li></ul><h2>The Results</h2><ul><li>Peak throughput hit 12,400 TPS — a 6.2x improvement — with 99.993% uptime over six months</li><li>ML fraud engine cut false positives from 4.7% to 0.28% and reduced fraud losses by 63%</li><li>Three enterprise customers onboarded, adding $180M in annualized volume</li><li>Deployment frequency increased from bi-weekly to 4-5 releases per week</li></ul><h2>What Made This Work</h2><ul><li>Kafka as the central nervous system provided backpressure handling, replay capability, and a clean audit trail that satisfied PCI auditors</li><li>A two-week architecture spike with load testing saved weeks of rework</li><li>Embedding one of FinFlow\'s engineers in our team full-time eliminated the feedback lag typical of enterprise projects</li></ul>',
                'client' => 'FinFlow Inc.',
                'category' => 'FinTech',
                'tech_stack' => ['Node.js', 'TypeScript', 'Kafka', 'PostgreSQL', 'Redis', 'Docker', 'Kubernetes', 'AWS'],
                'featured_image' => 'https://images.unsplash.com/photo-1563986768609-322da13575f2?w=1200&q=80',
                'tags' => ['fintech', 'payments', 'microservices', 'event-driven'],
                'duration' => '8 months',
                'year' => 2025,
                'highlights' => [
                    '12,000 TPS throughput — 6x improvement over legacy system',
                    '99.99% uptime with zero-downtime deployments',
                    'ML-powered fraud detection in under 50ms',
                ],
                'key_features' => [
                    'Event-Driven Payment Orchestration',
                    'ML-Powered Fraud Detection',
                    'Zero-Downtime Blue-Green Deployments',
                    'Multi-Currency Settlement Engine',
                ],
                'meta_title' => 'FinFlow — Payment Processing Platform | DigiDittos',
                'meta_description' => 'How we rebuilt a legacy payment monolith into a 12,000 TPS event-driven platform with ML fraud detection and 99.99% uptime.',
                'meta_keywords' => ['payment processing', 'fintech', 'microservices', 'kafka', 'fraud detection', 'enterprise payments'],
            ],
            [
                'title' => 'MedSync — Healthcare Patient Management System',
                'description' => '<h2>The Situation</h2><ul><li>MedSync Health operates 43 outpatient clinics across three states with 200+ providers and 120,000 active patients</li><li>Growth through acquisitions left them with four scheduling systems, two EHR platforms, and a standalone billing app requiring triple-entry</li><li>No EHR-integrated telemedicine existed, costing providers 8-10 minutes per virtual visit on manual note transfers</li></ul><h2>The Challenge</h2><ul><li>Legacy systems used incompatible patient ID schemes with ~14,000 duplicate records</li><li>No-show rates averaged 22%, representing a $4.3M annual loss</li><li>12% claim denial rate from manual coding errors meant 60% of billing team time went to rework</li></ul><h2>The Solution</h2><ul><li>Built four core modules sharing a common patient identity layer with probabilistic matching that auto-resolved 13,400 of 14,000 duplicates</li><li>Scheduling module uses Twilio-powered multi-channel reminders with waitlist backfill</li><li>WebRTC-based telehealth embedded directly in the clinical chart with auto-populated encounter metadata</li><li>Billing module validates every claim against payer rules before electronic submission</li></ul><h2>The Results</h2><ul><li>No-shows dropped from 22% to 8%, recovering ~$2.8M annually</li><li>Claim denials fell from 12% to 3.1% and days-in-AR halved from 38 to 19</li><li>Telemedicine adoption rose from 11% to 34% of visits with provider note-entry time dropping to under 2 minutes</li><li>Patient NPS improved from +31 to +58</li></ul><h2>What Made This Work</h2><ul><li>Investing three weeks in patient identity deduplication before building any UI ensured every downstream module worked correctly from day one</li><li>Rolled out clinic by clinic with one-week parallel periods that surfaced workflow gaps early</li><li>Building telehealth natively rather than integrating third-party video saved long-term cost and eliminated context-switching</li></ul>',
                'client' => 'MedSync Health',
                'category' => 'Healthcare',
                'tech_stack' => ['React', 'Node.js', 'PostgreSQL', 'WebRTC', 'AWS', 'Twilio', 'Stripe'],
                'featured_image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&q=80',
                'tags' => ['healthcare', 'hipaa', 'telemedicine', 'saas'],
                'duration' => '6 months',
                'year' => 2025,
                'highlights' => [
                    'HIPAA-compliant with end-to-end encryption and audit logging',
                    'Reduced appointment no-shows from 22% to 8%',
                    'Serving 40+ clinics with 15,000+ monthly patient interactions',
                ],
                'key_features' => [
                    'Unified Patient Timeline',
                    'Integrated Telemedicine with WebRTC',
                    'Automated Insurance Claim Submission',
                    'Smart Appointment Reminders',
                ],
                'meta_title' => 'MedSync — Patient Management System | DigiDittos',
                'meta_description' => 'How we unified 43 clinics onto one HIPAA-compliant platform, cutting no-shows by 64% and billing errors by 74%.',
                'meta_keywords' => ['healthcare software', 'patient management', 'HIPAA', 'telemedicine', 'EHR', 'appointment scheduling'],
            ],
            [
                'title' => 'ShelfIQ — AI-Powered Inventory Management',
                'description' => '<h2>The Situation</h2><ul><li>ShelfIQ Retail operates 12 warehouses supplying 340 storefronts and a fast-growing e-commerce channel with 45,000 active SKUs</li><li>Inventory planning relied on Excel, gut instinct, and a legacy ERP with 24-hour-lagged stock counts</li><li>E-commerce growing 40% YoY with two new warehouses planned, making manual processes unsustainable</li></ul><h2>The Challenge</h2><ul><li>Stockout rates of 15% caused an estimated $1.8M in annual lost revenue</li><li>Overstock on slow-movers tied up $2.1M in working capital</li><li>24-hour inventory lag created blind spots during flash sales, leading to overselling and backorders</li></ul><h2>The Solution</h2><ul><li>Real-time tracking via WebSocket-streamed barcode/RFID scans updating a PostgreSQL ledger within 200ms</li><li>TensorFlow gradient-boosted forecasting model trained on 3 years of data with weather and event signals</li><li>Automated replenishment system that drafts POs and recommends cross-warehouse transfers to balance stock</li></ul><h2>The Results</h2><ul><li>Stockouts dropped from 15% to 3%, recovering ~$1.4M in annual e-commerce revenue</li><li>Carrying costs fell 31%, freeing $620K in working capital</li><li>Purchasing team went from 25+ hours/week of manual ordering to ~4 hours reviewing exceptions</li><li>Forecast accuracy holds steady at 94% at the individual SKU level</li></ul><h2>What Made This Work</h2><ul><li>Three weeks spent cleaning historical data and resolving SKU code mismatches saved the model 15-20 points of accuracy</li><li>Chose gradient-boosted trees over deep learning for interpretability, building trust with the purchasing team</li><li>Phased rollout starting with two high-volume warehouses validated accuracy before scaling to all twelve</li></ul>',
                'client' => 'ShelfIQ Retail',
                'category' => 'E-Commerce & Retail',
                'tech_stack' => ['Python', 'FastAPI', 'React', 'TensorFlow', 'PostgreSQL', 'Redis', 'Docker'],
                'featured_image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1200&q=80',
                'tags' => ['ai', 'inventory', 'machine-learning', 'retail'],
                'duration' => '5 months',
                'year' => 2025,
                'highlights' => [
                    '94% demand forecast accuracy at SKU level',
                    'Stockout rates reduced from 15% to 3%',
                    '31% reduction in carrying costs across 12 warehouses',
                ],
                'key_features' => [
                    'ML Demand Forecasting Engine',
                    'Automated Purchase Order Generation',
                    'Real-Time Multi-Warehouse Visibility',
                ],
                'meta_title' => 'ShelfIQ — AI Inventory Management | DigiDittos',
                'meta_description' => 'How we built an ML-powered inventory platform that cut stockouts from 15% to 3% and freed $620K in working capital for a 12-warehouse retailer.',
                'meta_keywords' => ['inventory management', 'demand forecasting', 'machine learning', 'retail technology', 'supply chain'],
            ],
            [
                'title' => 'Velo — Fitness & Wellness Mobile App',
                'description' => '<h2>The Situation</h2><ul><li>Velo Fitness, founded by two former Peloton PMs, had $1.2M in seed funding and a January marketing campaign locked in with influencer partners</li><li>Needed to ship on both iOS and Android in four months with a 55/45 platform split making single-platform launch unviable</li></ul><h2>The Challenge</h2><ul><li>App needed smooth 60fps animations, real-time social sync, and on-device ML without draining battery</li><li>Wearable fragmentation across Apple Watch, Fitbit, Garmin, and Samsung meant managing different APIs and sync frequencies</li><li>75% of fitness app users abandon within 30 days, so every feature had to drive retention</li></ul><h2>The Solution</h2><ul><li>Flutter delivered 60fps native-feel animations from a single codebase</li><li>Adaptive workout engine runs TensorFlow Lite on-device for zero-latency personalization, adjusting plans based on fatigue and missed sessions</li><li>Social challenges use WebSocket connections for real-time leaderboard updates with push notifications on rank changes achieving 34% tap-through rate</li></ul><h2>The Results</h2><ul><li>Hit 50,000+ downloads in Q1 with a 4.7-star rating</li><li>30-day retention stabilized at 42% vs. 25% industry average, with social challenge participants showing 61% retention</li><li>On-device ML kept server costs at $0.003/MAU</li><li>Strong metrics helped Velo close a $4.8M Series A three months post-launch</li></ul><h2>What Made This Work</h2><ul><li>Flutter\'s single-codebase approach let three engineers deliver a polished dual-platform product in four months</li><li>Running ML on-device provided zero-latency personalization, lower server costs, and privacy as a marketing differentiator</li><li>Social challenges became the primary retention driver after beta data showed 2.4x higher 30-day retention for week-one social users</li></ul>',
                'client' => 'Velo Fitness',
                'category' => 'Mobile App',
                'tech_stack' => ['Flutter', 'Dart', 'Node.js', 'Firebase', 'TensorFlow Lite', 'PostgreSQL', 'WebSocket'],
                'featured_image' => 'https://images.unsplash.com/photo-1526256262350-7da7584cf5eb?w=1200&q=80',
                'tags' => ['mobile', 'fitness', 'flutter', 'ai-personalization'],
                'duration' => '4 months',
                'year' => 2024,
                'highlights' => [
                    '50,000+ downloads in the first quarter',
                    '4.7-star average across App Store and Google Play',
                    '42% DAU retention — well above the 25% industry average',
                ],
                'key_features' => [
                    'Adaptive Workout Engine',
                    'Real-Time Social Challenges',
                    'HealthKit & Google Fit Integration',
                ],
                'meta_title' => 'Velo — Fitness & Wellness App | DigiDittos',
                'meta_description' => 'How we built a cross-platform fitness app with on-device AI personalization that hit 50K downloads and 42% retention in its first quarter.',
                'meta_keywords' => ['fitness app', 'flutter', 'mobile development', 'AI personalization', 'workout app', 'health tech'],
            ],
            [
                'title' => 'Nexus CRM — Sales Pipeline Automation Platform',
                'description' => '<h2>The Situation</h2><ul><li>Nexus Solutions had 45 sales reps generating $28M ARR with an average deal size of $85K and 60-90 day cycles</li><li>Reps spent ~13 hours/week on manual CRM data entry</li><li>Forecast accuracy of 62% made confident resource planning nearly impossible</li></ul><h2>The Challenge</h2><ul><li>34% of open opportunities hadn\'t been updated in two weeks, and 22% of closed deals had incomplete histories</li><li>Q3 revenue came in 28% below forecast the previous year, forcing deferred hires</li><li>Integration complexity across Gmail, Outlook, two VoIP providers, and a meeting scheduler compounded data quality issues</li></ul><h2>The Solution</h2><ul><li>Built a React/TypeScript SPA with a GraphQL API that fetches nested deal data in a single request</li><li>Automatic activity capture via OAuth-based email/calendar APIs and VoIP webhooks eliminates manual tagging</li><li>Logistic regression model scores leads using 28 features including firmographic data and email sentiment</li><li>Monte Carlo simulation layers scores with stage-conversion rates for probabilistic revenue forecasts</li></ul><h2>The Results</h2><ul><li>Forecast accuracy improved from 62% to 91%, enabling confident hiring of 8 deferred engineers</li><li>Reps cut admin time from 13 to 2 hours/week — an 85% reduction</li><li>Average sales cycles shortened 18% from 78 to 64 days</li><li>Pipeline reviews went from 90-minute subjective discussions to focused 30-minute data-driven sessions</li></ul><h2>What Made This Work</h2><ul><li>Making automatic activity capture the foundation solved CRM data quality by eliminating manual entry entirely</li><li>GraphQL cut dashboard load times from 1.2s to 180ms, driving adoption in a tool reps use hundreds of times daily</li><li>A four-week pilot with one regional team produced 88% forecast accuracy that became the most persuasive adoption argument</li></ul>',
                'client' => 'Nexus Solutions',
                'category' => 'SaaS Platform',
                'tech_stack' => ['React', 'TypeScript', 'Node.js', 'GraphQL', 'PostgreSQL', 'Elasticsearch', 'Redis', 'AWS'],
                'featured_image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80',
                'tags' => ['crm', 'saas', 'sales-automation', 'graphql'],
                'duration' => '7 months',
                'year' => 2024,
                'highlights' => [
                    '91% revenue forecast accuracy — up from 62%',
                    'Sales cycle shortened by 18% on average',
                    'Reps save 11 hours/week on data entry and admin tasks',
                ],
                'key_features' => [
                    'Automatic Activity Capture',
                    'ML-Driven Lead Scoring',
                    'Pipeline Risk Alerts',
                    'Revenue Forecasting Dashboard',
                ],
                'meta_title' => 'Nexus CRM — Sales Pipeline Automation | DigiDittos',
                'meta_description' => 'How we built a CRM with automatic activity capture and ML lead scoring that improved revenue forecast accuracy from 62% to 91%.',
                'meta_keywords' => ['CRM', 'sales automation', 'lead scoring', 'revenue forecasting', 'SaaS', 'GraphQL'],
            ],
            [
                'title' => 'GreenGrid — Smart Energy Monitoring Dashboard',
                'description' => '<h2>The Situation</h2><ul><li>GreenGrid Energy manages energy systems for 18 Class A office buildings totaling 4.2M sq ft with ~$16M in annual energy costs</li><li>Each building had a different BMS vendor with no centralized visibility</li><li>Problems like a stuck-open damper could run 23 days undetected, costing $14K</li><li>New tenant ESG requirements and EPA mandates created urgency</li></ul><h2>The Challenge</h2><ul><li>18 buildings used five different BMS platforms with proprietary protocols (BACnet/IP, Modbus TCP, serial BACnet MS/TP)</li><li>8,000+ sensors generated 2.4M readings per day needing sub-second dashboard latency and multi-year historical analysis</li><li>Previous monitoring attempts failed due to false alarm fatigue, so alert specificity needed to stay below 5% false positives</li></ul><h2>The Solution</h2><ul><li>Edge gateways normalize BMS data into a common MQTT topic structure with local buffering for outages</li><li>High-frequency electrical data flows into InfluxDB while other sensors aggregate at 1-minute intervals in TimescaleDB</li><li>Statistical process control establishes dynamic baselines per sensor by hour/day/season, alerting only on sustained deviations</li><li>React dashboard uses D3.js for real-time energy heatmaps and trend charts</li></ul><h2>The Results</h2><ul><li>Energy costs dropped 23% (~$3.7M annually) from optimized HVAC scheduling</li><li>Anomaly system identified 340 equipment issues in six months, cutting detection-to-resolution from 18 days to 8 hours</li><li>Achieved only a 3.2% false-positive rate on alerts</li><li>Two buildings achieved ENERGY STAR certification for the first time</li></ul><h2>What Made This Work</h2><ul><li>Edge gateways solved the restrictive building OT network problem with a single encrypted outbound MQTT connection plus local buffering</li><li>Statistical process control over ML provided explainable alerts that operators actually trusted and acted on</li><li>A two-day site survey per building calibrated baselines before go-live, ensuring accurate insights from day one</li></ul>',
                'client' => 'GreenGrid Energy',
                'category' => 'IoT & Analytics',
                'tech_stack' => ['React', 'D3.js', 'Node.js', 'TimescaleDB', 'MQTT', 'InfluxDB', 'Docker', 'Azure'],
                'featured_image' => 'https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?w=1200&q=80',
                'tags' => ['iot', 'energy', 'analytics', 'real-time-dashboard'],
                'duration' => '5 months',
                'year' => 2024,
                'highlights' => [
                    '23% average reduction in energy costs across managed buildings',
                    'Real-time ingestion of 2.4 million sensor readings per day',
                    'Anomaly detection catches HVAC faults 6 hours before occupant complaints',
                ],
                'key_features' => [
                    'Real-Time Sensor Data Ingestion',
                    'Anomaly Detection & Predictive Alerts',
                    'Automated Optimization Rules',
                ],
                'meta_title' => 'GreenGrid — Smart Energy Dashboard | DigiDittos',
                'meta_description' => 'How we built an IoT energy monitoring platform that reduced costs by 23% across 18 commercial buildings with real-time anomaly detection.',
                'meta_keywords' => ['energy monitoring', 'IoT', 'smart buildings', 'analytics dashboard', 'sustainability', 'BMS integration'],
            ],
            [
                'title' => 'LegalPad — Document Automation for Law Firms',
                'description' => '<h2>The Situation</h2><ul><li>LegalPad serves 14 mid-size law firms handling ~3,200 transactions per year</li><li>Junior associates spent an average of 4.5 hours assembling first drafts by copy-pasting clauses from prior deals</li><li>Partners spent 2-3 hours reviewing issues that a systematic checklist would have prevented</li></ul><h2>The Challenge</h2><ul><li>A typical M&A agreement has 80-120 conditional sections varying by deal structure, jurisdiction, and regulations</li><li>Defined-term inconsistencies across 60+ page documents created legal ambiguity</li><li>Attorneys are justifiably conservative about tools affecting document accuracy, making change management equally hard</li></ul><h2>The Solution</h2><ul><li>Visual logic builder compiles plain-language conditional rules into decision trees that generate documents in seconds</li><li>Elasticsearch-backed clause library with OpenAI-powered contextual ranking suggests relevant provisions with full provenance</li><li>Collaborative review module supports real-time co-editing with operational transformation</li><li>Continuous compliance engine validates defined-term consistency, required clauses, and cross-references before finalization</li></ul><h2>The Results</h2><ul><li>First-draft time dropped from 4.5 hours to 48 minutes — an 82% reduction — saving ~14,000 associate-hours annually valued at $5.6M</li><li>Review cycles fell from 4.2 to 1.8 rounds as compliance checklists caught 91% of issues previously requiring partner markup</li><li>Template adoption reached 78% across the consortium within six months</li></ul><h2>What Made This Work</h2><ul><li>Visual logic builder put template authorship in the hands of attorneys who understand legal content, eliminating developer dependency</li><li>AI clause suggestion system was designed for transparency — attorneys see why each clause was suggested and can trace it to a vetted source</li><li>Piloting with three firms refined the template migration process before opening to the full consortium</li></ul>',
                'client' => 'LegalPad Technologies',
                'category' => 'LegalTech',
                'tech_stack' => ['Next.js', 'TypeScript', 'Python', 'PostgreSQL', 'Elasticsearch', 'Redis', 'AWS', 'OpenAI API'],
                'featured_image' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=1200&q=80',
                'tags' => ['legaltech', 'document-automation', 'ai', 'saas'],
                'duration' => '6 months',
                'year' => 2024,
                'highlights' => [
                    '82% faster first-draft generation compared to manual drafting',
                    'Review cycles reduced from 4.2 to 1.8 rounds on average',
                    'Clause library covers 2,400+ pre-vetted legal provisions',
                ],
                'key_features' => [
                    'Conditional Logic Template Engine',
                    'AI-Assisted Clause Suggestions',
                    'Collaborative Review with Tracked Changes',
                    'Compliance Checklist Automation',
                ],
                'meta_title' => 'LegalPad — Document Automation for Law Firms | DigiDittos',
                'meta_description' => 'How we built a legal document automation platform that cut first-draft time by 82% and reduced review cycles from 4.2 to 1.8 rounds.',
                'meta_keywords' => ['legal tech', 'document automation', 'law firm software', 'clause library', 'AI legal tools'],
            ],
            [
                'title' => 'Atlas — Multi-Tenant Property Management Platform',
                'description' => '<h2>The Situation</h2><ul><li>Atlas Property Group manages 38 client portfolios totaling 6,200+ units across greater Philadelphia</li><li>Outgrown a patchwork of Yardi, AppFolio, and a custom Access database requiring triple data entry</li><li>5 business days each month spent just generating owner statements</li><li>Planned to offer their platform as a white-label service to other regional firms</li></ul><h2>The Challenge</h2><ul><li>Data migration from three disconnected systems — 8 years of Yardi financials, active AppFolio leases, 23,000 Access maintenance records — required careful ETL without breaking audit trails</li><li>Multi-tenancy needed complete data isolation with centralized admin</li><li>Only 34% of tenants paid rent online, creating 120+ hours/month of manual check processing</li></ul><h2>The Solution</h2><ul><li>Shared-database, separate-schema architecture with PostgreSQL row-level security and automated tenant provisioning in under 60 seconds</li><li>Lease lifecycle module integrates TransUnion screening and DocuSign e-signatures</li><li>Stripe Connect handles ACH, card, and digital wallet payments with magic-link auth flow (no passwords)</li><li>Progressive nudge system for on-time payment through a minimal tenant portal</li></ul><h2>The Results</h2><ul><li>Online rent collection jumped from 34% to 89% in three months, with on-time rates improving from 76% to 94%</li><li>Vacancy turnaround decreased from 34 to 20 days (41% improvement), recovering ~$4.2M in annual revenue</li><li>Owner statement generation went from a 5-day manual process to an automated overnight run</li><li>Three external firms onboarded within four months, adding 1,800 units</li></ul><h2>What Made This Work</h2><ul><li>Database-level row isolation (not just application filtering) passed institutional investor security audits and became a sales differentiator</li><li>Magic-link auth flow eliminated the #1 payment portal abandonment point, boosting completion rates 28%</li><li>A pre-tested ETL pipeline migrated all three legacy systems in a single weekend with zero downtime</li></ul>',
                'client' => 'Atlas Property Group',
                'category' => 'PropTech',
                'tech_stack' => ['React', 'Node.js', 'PostgreSQL', 'Redis', 'Stripe', 'Twilio', 'AWS', 'Docker'],
                'featured_image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1200&q=80',
                'tags' => ['proptech', 'property-management', 'multi-tenant', 'saas'],
                'duration' => '7 months',
                'year' => 2024,
                'highlights' => [
                    '6,200+ units managed across 38 property portfolios',
                    'Vacancy turnaround reduced from 34 days to 20 days',
                    'Online rent collection adoption reached 89% within 3 months',
                ],
                'key_features' => [
                    'Multi-Tenant Architecture with Portfolio Isolation',
                    'Automated Lease Lifecycle Management',
                    'Maintenance Request Workflow Engine',
                    'Owner Financial Reporting',
                ],
                'meta_title' => 'Atlas — Property Management Platform | DigiDittos',
                'meta_description' => 'How we built a multi-tenant property management platform serving 6,200+ units that reduced vacancy turnaround by 41% and hit 89% online rent adoption.',
                'meta_keywords' => ['property management', 'proptech', 'multi-tenant SaaS', 'rent collection', 'lease management'],
            ],
            [
                'title' => 'Pulse — Real-Time Team Collaboration Platform',
                'description' => '<h2>The Situation</h2><ul><li>Founded by three former Slack engineers, Pulse targeted distributed engineering teams of 20-200 people using 4-5 separate tools</li><li>Context-switching caused decisions to get lost between chat, docs, tasks, and video tools</li><li>With $3.5M in seed funding, they needed an MVP onboarding 10 design partners within eight months</li></ul><h2>The Challenge</h2><ul><li>Platform needed sub-100ms message delivery globally and conflict-free concurrent document editing for 15+ simultaneous editors</li><li>Threading UX had to feel natural for both async and sync users</li><li>Search had to help async users find the 5 messages that matter out of 200+ overnight messages in under 30 seconds</li></ul><h2>The Solution</h2><ul><li>WebSocket servers deployed in three AWS regions with Redis Pub/Sub achieve sub-100ms delivery for 95% of messages</li><li>Collaborative documents use the Yjs CRDT library for deterministic conflict-free merging that handles offline editing</li><li>Personalized catch-up feed aggregates unread mentions, task updates, and high-engagement messages powered by Elasticsearch with custom relevance scoring</li></ul><h2>The Results</h2><ul><li>Design partners reported 35% fewer scheduled meetings and 6.4 recovered hours per person per week</li><li>Message delivery hit P95 of 87ms across all regions</li><li>Document editing handled 32 simultaneous editors without issues</li><li>DAU reached 92% among onboarded teams, with the catch-up feed cited as the single most valuable feature</li></ul><h2>What Made This Work</h2><ul><li>CRDTs over operational transformation was the right bet for async-first editing — changes merge deterministically regardless of timing</li><li>Iterated through four ranking models for the catch-up feed during the design partner phase before finding the right balance</li><li>Co-developing with 10 design partner teams meant the product was battle-tested by 180 daily users before launch</li></ul>',
                'client' => 'Pulse Collaboration',
                'category' => 'SaaS Platform',
                'tech_stack' => ['React', 'TypeScript', 'Node.js', 'WebSocket', 'PostgreSQL', 'Redis', 'Elasticsearch', 'AWS'],
                'featured_image' => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1200&q=80',
                'tags' => ['collaboration', 'real-time', 'saas', 'remote-work'],
                'duration' => '8 months',
                'year' => 2025,
                'highlights' => [
                    '35% reduction in scheduled meeting time',
                    'Sub-100ms message delivery across global regions',
                    '92% daily active usage rate among onboarded teams',
                ],
                'key_features' => [
                    'Thread-Based Async Communication',
                    'Collaborative Documents with Real-Time Editing',
                    'Integrated Task Board',
                ],
                'meta_title' => 'Pulse — Team Collaboration Platform | DigiDittos',
                'meta_description' => 'How we built a real-time collaboration platform for distributed teams that cut meetings by 35% with sub-100ms messaging and CRDT-based document editing.',
                'meta_keywords' => ['collaboration platform', 'real-time messaging', 'remote work', 'async collaboration', 'CRDT', 'SaaS'],
            ],
            [
                'title' => 'TradeVault — Cryptocurrency Portfolio Tracker',
                'description' => '<h2>The Situation</h2><ul><li>TradeVault Capital administers 12 crypto hedge funds with $200M combined AUM, trading across 8+ exchanges each</li><li>Daily NAV calculation took three analysts ~4 hours each morning using exchange dashboards and spreadsheets</li><li>Year-end tax reporting was a multi-week ordeal, and two funds had already experienced reporting errors requiring investor restatements</li></ul><h2>The Challenge</h2><ul><li>24 target exchanges use different APIs, auth methods, data formats, and fee structures</li><li>DeFi positions across Aave, Uniswap, and staking contracts require direct blockchain state queries with dynamic valuation</li><li>Crypto tax-lot accounting is uniquely complex — single DEX trades can involve multiple swaps, gas fees in different assets, and varying IRS interpretations</li></ul><h2>The Solution</h2><ul><li>Exchange abstraction layer with independently deployable adapter modules normalizing all data into a canonical trade model</li><li>DeFi indexer nodes query on-chain contract state every block with protocol-specific decoders</li><li>Tax-lot engine supports four accounting methods (FIFO, LIFO, HIFO, specific ID) with side-by-side comparison</li><li>NAV calculated every 5 seconds using VWAP from three independent data providers with automatic failover</li></ul><h2>The Results</h2><ul><li>Daily NAV went from a 4-hour manual process to a real-time automated feed</li><li>Year-end tax reporting dropped from 3 weeks to 2 days per fund — a 90% reduction</li><li>Reconciliation engine caught 47 balance discrepancies in month one, including a $340K exchange-side error</li><li>TradeVault onboarded 4 new funds in Q1 post-launch, growing tracked AUM from $200M to $310M</li></ul><h2>What Made This Work</h2><ul><li>Adapter pattern for exchange integrations meant API changes could be fixed in 45 minutes without touching other code</li><li>Supporting multiple tax-lot methods side-by-side became a competitive differentiator for fund tax advisors</li><li>Involving fund administrators in every design review surfaced critical workflow needs like transaction annotations and manual overrides</li></ul>',
                'client' => 'TradeVault Capital',
                'category' => 'FinTech',
                'tech_stack' => ['React', 'TypeScript', 'Node.js', 'PostgreSQL', 'Redis', 'WebSocket', 'Docker', 'GCP'],
                'featured_image' => 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=1200&q=80',
                'tags' => ['fintech', 'cryptocurrency', 'portfolio-management', 'real-time'],
                'duration' => '6 months',
                'year' => 2025,
                'highlights' => [
                    '24 exchange integrations with real-time balance sync',
                    'Automated tax-lot accounting saved 200+ hours per fund per year',
                    '$200M+ combined AUM tracked across client portfolios',
                ],
                'key_features' => [
                    'Multi-Exchange Portfolio Aggregation',
                    'Real-Time P&L and NAV Calculation',
                    'Automated Tax-Lot Accounting',
                ],
                'meta_title' => 'TradeVault — Crypto Portfolio Tracker | DigiDittos',
                'meta_description' => 'How we built a crypto portfolio platform tracking $200M+ AUM across 24 exchanges with real-time NAV and automated tax-lot accounting.',
                'meta_keywords' => ['cryptocurrency', 'portfolio management', 'crypto tax', 'fund administration', 'fintech', 'DeFi'],
            ],
            [
                'title' => 'EduForge — Online Learning Management System',
                'description' => '<h2>The Situation</h2><ul><li>EduForge Learning certifies ~8,000 professionals per year across cybersecurity, cloud, data engineering, and project management programs</li><li>Moodle-based platform suffered from poor video performance with 23% buffering complaints</li><li>No proctoring capability forced $85/exam outsourcing to physical testing centers</li><li>Corporate clients wanted remote certification options</li></ul><h2>The Challenge</h2><ul><li>Completion rates averaged 38%, with most dropouts in week 1 (content overload) and weeks 3-4 (no adaptive support)</li><li>Video delivery failed on connections below 5 Mbps, affecting global learners</li><li>Remote proctoring needed reliable cheating detection without excessive false positives while complying with GDPR and PIPEDA</li></ul><h2>The Solution</h2><ul><li>Next.js with SSR delivers fast loads, while adaptive bitrate HLS streaming via CloudFront serves video at 6 quality tiers down to 360p</li><li>Knowledge-graph adaptive engine decomposes each program into 40-80 skill nodes, updating mastery estimates after each assessment</li><li>AI proctoring runs TensorFlow.js models client-side for face detection and gaze tracking with region-specific encrypted storage and 90-day auto-deletion</li></ul><h2>The Results</h2><ul><li>Completion rates jumped from 38% to 73%, with adaptive-path learners hitting 81%</li><li>Video buffering complaints dropped from 23% to 0.8%</li><li>Remote proctoring replaced physical testing centers entirely, saving $680K annually</li><li>Three enterprise clients signed specifically for remote proctoring, adding $1.2M in annual contract value</li></ul><h2>What Made This Work</h2><ul><li>Knowledge-graph approach identified root-cause skill gaps rather than simply repeating failed material</li><li>Client-side AI proctoring eliminated bandwidth requirements and became a competitive advantage through its privacy-first architecture</li><li>Two weeks of dropout interviews before development directly shaped every major product decision</li></ul>',
                'client' => 'EduForge Learning',
                'category' => 'EdTech',
                'tech_stack' => ['Next.js', 'TypeScript', 'Node.js', 'PostgreSQL', 'Redis', 'AWS S3', 'CloudFront', 'WebRTC'],
                'featured_image' => 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=1200&q=80',
                'tags' => ['edtech', 'lms', 'e-learning', 'certification'],
                'duration' => '7 months',
                'year' => 2025,
                'highlights' => [
                    '28,000+ active learners across 14 certification programs',
                    '73% course completion rate — vs. 40% industry average',
                    'Proctored exams with 99.7% integrity validation rate',
                ],
                'key_features' => [
                    'Adaptive Learning Paths',
                    'AI-Proctored Examinations',
                    'Interactive Assessment Engine',
                    'Instructor Analytics Dashboard',
                ],
                'meta_title' => 'EduForge — Learning Management System | DigiDittos',
                'meta_description' => 'How we built an adaptive LMS for professional certifications that achieved 73% completion rates with AI proctoring and personalized learning paths.',
                'meta_keywords' => ['LMS', 'e-learning', 'EdTech', 'certification platform', 'AI proctoring', 'adaptive learning'],
            ],
            [
                'title' => 'Carto — Fleet Management & Route Optimization',
                'description' => '<h2>The Situation</h2><ul><li>Carto Logistics operates a 340-vehicle fleet completing ~1,800 deliveries per day across the northeastern US</li><li>Route planning relied on dispatcher experience and a basic point-to-point tool with no multi-stop optimization</li><li>Fleet averaged 2.3 roadside breakdowns per week at ~$1,200 each</li></ul><h2>The Challenge</h2><ul><li>Multi-stop optimization with 15-25 stops, time windows, capacity constraints, and DOT hours-of-service rules is computationally hard</li><li>Telematics data was inconsistent across three GPS device models with rural connectivity gaps up to 20 minutes</li><li>Previous tech rollouts had been abandoned after driver pushback, so the system had to make drivers\' jobs easier</li></ul><h2>The Solution</h2><ul><li>Hybrid genetic algorithm + constraint-satisfaction solver processes 340 routes in under 90 seconds each morning</li><li>Real-time re-optimization pushes updated routes within 30 seconds of disruptions</li><li>MQTT adapter layer normalizes data from all GPS device models into PostGIS with dead-reckoning interpolation during connectivity gaps</li><li>Gradient-boosted predictive maintenance model estimates failure probability across six component categories</li></ul><h2>The Results</h2><ul><li>Fuel costs dropped 18% (~$420K annually) from optimized routes reducing average daily mileage from 187 to 162 miles per vehicle</li><li>On-time delivery improved from 82% to 96%, with the re-optimizer rerouting 23 vehicles within 45 seconds during a highway accident</li><li>Roadside breakdowns fell from 2.3 to 0.8 per week (64% reduction), saving $156K annually</li></ul><h2>What Made This Work</h2><ul><li>Experienced dispatchers agreed in blind testing that the algorithm\'s routes were better than their manual plans</li><li>Driver app was designed as a navigation tool first and tracking tool second, replacing three separate tools with one interface</li><li>Four weeks of structured data collection with the maintenance shop created the clean labeled data the predictive model needed</li></ul>',
                'client' => 'Carto Logistics',
                'category' => 'Logistics & Supply Chain',
                'tech_stack' => ['React', 'Node.js', 'Python', 'PostGIS', 'Redis', 'MQTT', 'TensorFlow', 'AWS'],
                'featured_image' => 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=1200&q=80',
                'tags' => ['logistics', 'fleet-management', 'route-optimization', 'iot'],
                'duration' => '6 months',
                'year' => 2024,
                'highlights' => [
                    '18% reduction in fuel costs across 340 vehicles',
                    'On-time delivery improved from 82% to 96%',
                    'Predictive maintenance reduced roadside breakdowns by 64%',
                ],
                'key_features' => [
                    'AI Route Optimization Engine',
                    'Real-Time Fleet Tracking',
                    'Predictive Maintenance Scheduling',
                ],
                'meta_title' => 'Carto — Fleet Management & Route Optimization | DigiDittos',
                'meta_description' => 'How we built an AI-powered fleet management platform that cut fuel costs by 18% and improved on-time deliveries from 82% to 96% for a 340-vehicle fleet.',
                'meta_keywords' => ['fleet management', 'route optimization', 'logistics', 'predictive maintenance', 'IoT', 'GPS tracking'],
            ],
            [
                'title' => 'Bloom — E-Commerce Platform for Artisan Brands',
                'description' => '<h2>The Situation</h2><ul><li>Bloom Marketplace had validated demand through pop-up shops and 85K Instagram followers but needed digital infrastructure to scale</li><li>Multi-seller model with 200+ independent artisans required capabilities beyond standard Shopify</li><li>Needed split payments, per-seller storefronts, and a cohesive brand experience rivaling luxury e-commerce</li></ul><h2>The Challenge</h2><ul><li>A single checkout could span items from three sellers with different shipping origins, tax jurisdictions, and processing times</li><li>Wildly varying product photography quality (DSLR to smartphone) needed to look consistent in a grid layout</li><li>With 4,000+ products, shoppers needed intelligent recommendations, not just category navigation</li></ul><h2>The Solution</h2><ul><li>Headless architecture with Next.js on Vercel and Node.js API delivers sub-2-second loads via static generation with incremental regeneration</li><li>Stripe Connect handles multi-party split payments with automatic commission retention</li><li>Cloudinary pipeline normalizes image dimensions, applies white-balance correction, and generates responsive WebP/AVIF assets (68% bandwidth reduction)</li><li>Collaborative filtering transitions from content-based cold-start to behavioral recommendations after 60 days</li></ul><h2>The Results</h2><ul><li>200+ sellers onboarded with 4,200+ products in six months</li><li>AOV increased 34% over pop-up baseline, with the recommendation carousel driving 22% of total revenue</li><li>Lighthouse scores averaged 97, and mobile conversion improved from 1.8% to 3.4%</li><li>Platform achieved profitability within 11 months, ahead of the 18-month projection</li></ul><h2>What Made This Work</h2><ul><li>Headless architecture let a three-developer team support 45 independent content updates in six months</li><li>Cloudinary pipeline automated image normalization that would otherwise require a full-time role</li><li>Content-based cold-start strategy avoided the empty-recommendations problem, transitioning to collaborative filtering once sufficient data accumulated</li></ul>',
                'client' => 'Bloom Marketplace',
                'category' => 'E-Commerce & Retail',
                'tech_stack' => ['Next.js', 'TypeScript', 'Node.js', 'PostgreSQL', 'Elasticsearch', 'Stripe Connect', 'Cloudinary', 'Vercel'],
                'featured_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&q=80',
                'tags' => ['e-commerce', 'marketplace', 'headless-commerce', 'artisan'],
                'duration' => '5 months',
                'year' => 2024,
                'highlights' => [
                    '200+ artisan sellers onboarded in the first 6 months',
                    '34% increase in average order value through personalization',
                    'Sub-2-second page loads with 97 Lighthouse performance score',
                ],
                'key_features' => [
                    'Headless Commerce Architecture',
                    'Multi-Seller Marketplace with Split Payments',
                    'AI-Powered Product Discovery',
                ],
                'meta_title' => 'Bloom — Artisan E-Commerce Platform | DigiDittos',
                'meta_description' => 'How we built a headless e-commerce marketplace for 200+ artisan brands with sub-2-second loads and 34% higher average order value.',
                'meta_keywords' => ['e-commerce', 'marketplace', 'headless commerce', 'artisan brands', 'Next.js', 'Stripe Connect'],
            ],
            [
                'title' => 'Sentinel — Cybersecurity Threat Detection Platform',
                'description' => '<h2>The Situation</h2><ul><li>Sentinel Cyber provides managed security for 28 mid-market companies across 14,000 endpoints, 200+ cloud accounts, and 40+ network perimeters</li><li>Patchwork of ELK, Snort, and Wazuh generated ~4,200 uncorrelated alerts per day, 89% of which were false positives</li><li>A post-incident review revealed that a data exfiltration had generated three alerts, all dismissed by fatigued analysts</li></ul><h2>The Challenge</h2><ul><li>Processing 2.8 billion daily events to find genuine threats was a needle-in-a-haystack problem</li><li>Alert fatigue from the 89% false-positive rate had created dangerous dismissal patterns</li><li>Multi-stage attacks spanning phishing, privilege escalation, and data exfiltration could only be detected by correlating events across multiple sources</li></ul><h2>The Solution</h2><ul><li>Go-based collectors normalize 40+ data source types into a common schema on Kafka</li><li>Rule-based correlation engine detects known attack patterns while a parallel ML model scores alerts using 64 features</li><li>Automated response playbooks execute containment via CrowdStrike, Okta, Palo Alto, and AWS APIs within 60 seconds</li><li>Every action is fully logged with complete audit trails</li></ul><h2>The Results</h2><ul><li>MTTD dropped from 14 hours to 12 minutes</li><li>First multi-stage attack was detected within three weeks and contained in 90 seconds</li><li>False positives fell from 89% to 26% (71% reduction), with analysts now investigating 480 meaningful alerts instead of 4,200</li><li>MITRE ATT&CK coverage rose from 31% to 84%, and the platform won 8 new contracts ($1.4M ARR)</li></ul><h2>What Made This Work</h2><ul><li>Normalizing all sources into a common event schema at ingestion made every detection rule vendor-agnostic</li><li>Training the ML model on actual analyst labeling history captured environment-specific noise patterns</li><li>Automated playbooks were rolled out in \'recommend\' mode first, building trust before enabling full automation</li></ul>',
                'client' => 'Sentinel Cyber',
                'category' => 'Cybersecurity',
                'tech_stack' => ['Python', 'Go', 'React', 'Kafka', 'Elasticsearch', 'PostgreSQL', 'TensorFlow', 'Kubernetes'],
                'featured_image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200&q=80',
                'tags' => ['cybersecurity', 'threat-detection', 'siem', 'machine-learning'],
                'duration' => '9 months',
                'year' => 2025,
                'highlights' => [
                    'Mean time to detect reduced from 14 hours to 12 minutes',
                    'False-positive rate cut by 71% through ML alert triage',
                    'Processes 2.8 billion events per day across client environments',
                ],
                'key_features' => [
                    'Real-Time Event Correlation Engine',
                    'ML-Powered Alert Triage',
                    'Automated Incident Response Playbooks',
                    'Threat Intelligence Integration',
                ],
                'meta_title' => 'Sentinel — Threat Detection Platform | DigiDittos',
                'meta_description' => 'How we built a cybersecurity platform that cut threat detection time from 14 hours to 12 minutes and reduced false positives by 71%.',
                'meta_keywords' => ['cybersecurity', 'threat detection', 'SIEM', 'SOC', 'machine learning', 'incident response'],
            ],
            [
                'title' => 'Aura — Mental Health & Wellness Platform',
                'description' => '<h2>The Situation</h2><ul><li>Aura Wellness was founded to address the US therapy access crisis with average wait times exceeding 6 weeks</li><li>With $2.8M in seed funding, founders needed a platform matching individuals with therapists within 48 hours</li><li>Key insight: therapeutic progress happens between sessions, so between-session engagement had to be a core feature</li></ul><h2>The Challenge</h2><ul><li>State-specific therapist licensure rules required dynamic jurisdiction matching</li><li>Clinical safety demanded robust crisis detection with minimal false negatives plus fail-safe mechanisms for intermittent connectivity</li><li>Therapist utilization rates of 60-65% on competing platforms meant low earnings and high attrition</li></ul><h2>The Solution</h2><ul><li>React Native cross-platform mobile with Node.js API and matching algorithm optimizing across 14 compatibility dimensions plus therapist utilization</li><li>Hybrid model uses WebRTC video sessions plus encrypted async messaging, letting therapists serve 30% more clients</li><li>Fine-tuned NLP model (94% recall, 6% false-positive) triggers simultaneous crisis resources, therapist alerts, and safety team escalation via PagerDuty</li></ul><h2>The Results</h2><ul><li>18,000 users and 240 therapists onboarded in 10 months with 91% first-match satisfaction</li><li>67% of 8-week users showed clinically significant PHQ-9/GAD-7 improvement</li><li>Therapist utilization reached 88% with earnings 28% above competing platforms and only 8% annual attrition</li><li>Crisis system has intervened in 140+ situations with 94% appropriate escalation</li></ul><h2>What Made This Work</h2><ul><li>Clinical advisory board actively participated in every product decision, vetoing three technically impressive but clinically inappropriate features</li><li>Matching algorithm\'s dual optimization for quality and utilization solved a problem competitors handle manually</li><li>Tabletop exercises simulating crisis system failures produced specific mitigations including offline message queuing and manual \'I need help now\' button</li></ul>',
                'client' => 'Aura Wellness',
                'category' => 'Healthcare',
                'tech_stack' => ['React Native', 'Node.js', 'PostgreSQL', 'WebRTC', 'AWS', 'Twilio', 'Stripe', 'Redis'],
                'featured_image' => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?w=1200&q=80',
                'tags' => ['healthcare', 'mental-health', 'telehealth', 'wellness'],
                'duration' => '7 months',
                'year' => 2025,
                'highlights' => [
                    '18,000+ active users with 4.8-star app rating',
                    '67% of users report symptom improvement within 8 weeks',
                    'Therapist utilization rate of 88% — vs. 65% industry average',
                ],
                'key_features' => [
                    'Therapist Matching Algorithm',
                    'Hybrid Sync/Async Therapy Model',
                    'Guided Self-Care Programs',
                    'Crisis Detection & Safety Protocols',
                ],
                'meta_title' => 'Aura — Mental Health & Wellness Platform | DigiDittos',
                'meta_description' => 'How we built a digital therapy platform serving 18,000 users with a 91% first-match rate and 67% symptom improvement within 8 weeks.',
                'meta_keywords' => ['mental health', 'teletherapy', 'digital wellness', 'healthcare platform', 'therapist matching', 'crisis detection'],
            ],
        ];
    }
}
