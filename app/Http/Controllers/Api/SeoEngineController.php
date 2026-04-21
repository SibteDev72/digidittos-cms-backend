<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceFeature;
use App\Models\SeoRedirect;
use App\Models\SiteSetting;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SeoEngineController extends Controller
{
    use LogsActivity;

    // ─── SEO AUDIT / DASHBOARD ──────────────────────────────────

    /**
     * Field sets audited per content type. Every entry carries:
     *   - `fields`      → actual columns on the model/settings row
     *   - `recommended` → optional length hints used by the UI to flag
     *                     too-short / too-long values even when filled.
     *
     * Keeping this in one place is the source of truth — any future
     * SEO column just needs to be added here to start scoring.
     */
    private function auditFieldConfig(): array
    {
        return [
            'blog' => [
                'fields'      => ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image', 'json_ld_schema'],
                'recommended' => ['meta_title' => [50, 60], 'meta_description' => [150, 160]],
            ],
            'project' => [
                'fields'      => ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image', 'json_ld_schema'],
                'recommended' => ['meta_title' => [50, 60], 'meta_description' => [150, 160]],
            ],
            'feature' => [
                'fields'      => ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image', 'json_ld_schema'],
                'recommended' => ['meta_title' => [50, 60], 'meta_description' => [150, 160]],
            ],
            'service' => [
                'fields'      => ['meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'json_ld_schema'],
                'recommended' => ['meta_title' => [50, 60], 'meta_description' => [150, 160]],
            ],
            'page' => [
                'fields'      => ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image', 'json_ld_schema'],
                'recommended' => ['meta_title' => [50, 60], 'meta_description' => [150, 160]],
            ],
        ];
    }

    /**
     * Filter a field list down to the columns that actually exist on the
     * given table. Guards the audit against schema drift — a column
     * declared in auditFieldConfig() but not yet migrated becomes a
     * "not applicable" (null field) rather than a 500.
     */
    private function existingFieldsFor(string $table, array $fields): array
    {
        return array_values(array_filter(
            $fields,
            fn ($field) => Schema::hasColumn($table, $field),
        ));
    }

    /**
     * Return SEO health scores for all auditable content.
     */
    public function audit(): JsonResponse
    {
        $config = $this->auditFieldConfig();

        $postFields    = $this->existingFieldsFor('blog_posts',      $config['blog']['fields']);
        $serviceFields = $this->existingFieldsFor('services',        $config['service']['fields']);
        $projectFields = $this->existingFieldsFor('projects',        $config['project']['fields']);
        $featureFields = $this->existingFieldsFor('service_features', $config['feature']['fields']);
        $pageFields    = $config['page']['fields']; // site_settings — no schema check needed

        $posts = BlogPost::select(array_merge(['id', 'title', 'slug', 'status', 'featured_image'], $postFields))->get();
        $postAudit = $this->auditCollection($posts, $postFields, 'blog', '/posts/{id}/edit', $config['blog']['recommended']);

        $services = Service::select(array_merge(['id', 'title', 'slug', 'is_active'], $serviceFields))->get();
        $serviceAudit = $this->auditCollection($services, $serviceFields, 'service', '/services-manage/{id}/edit', $config['service']['recommended']);

        $projects = Project::select(array_merge(['id', 'title', 'slug', 'status'], $projectFields))->get();
        $projectAudit = $this->auditCollection($projects, $projectFields, 'project', '/projects/{id}/edit', $config['project']['recommended']);

        $features = ServiceFeature::select(array_merge(['id', 'title', 'slug', 'is_active'], $featureFields))->get();
        $featureAudit = $this->auditCollection($features, $featureFields, 'feature', '/features/{id}/edit', $config['feature']['recommended']);

        // Page-level SEO: homepage, about, and the three listing pages
        // (projects, blog, services). Each maps to a `seo_{slug}` group.
        $pages = [
            'homepage' => ['title' => 'Home Page', 'slug' => '/'],
            'about'    => ['title' => 'About Page', 'slug' => '/about'],
            'projects' => ['title' => 'Projects Page', 'slug' => '/projects'],
            'blog'     => ['title' => 'Blog Page', 'slug' => '/blog'],
            'services' => ['title' => 'Services Page', 'slug' => '/services'],
        ];
        $pageAudit = [];
        foreach ($pages as $page => $meta) {
            $data = SiteSetting::getGroup("seo_{$page}");
            $filled = 0;
            $total = count($pageFields);
            foreach ($pageFields as $field) {
                if (! empty($data[$field] ?? null)) $filled++;
            }
            $pageAudit[] = [
                'id'    => $page,
                'page'  => $page,
                'title' => $meta['title'],
                'slug'  => $meta['slug'],
                'type'  => 'page',
                'edit_path' => "/pages/{$page}/edit",
                'score' => $total > 0 ? (int) round(($filled / $total) * 100) : 0,
                'filled' => $filled,
                'total'  => $total,
                'fields' => $this->buildFieldStatus($data, $pageFields, $config['page']['recommended']),
            ];
        }

        // Roll-up for the summary cards.
        $pageStats = $this->summaryFromItems($pageAudit);

        $allScores = array_merge(
            array_column($postAudit['items'], 'score'),
            array_column($serviceAudit['items'], 'score'),
            array_column($projectAudit['items'], 'score'),
            array_column($featureAudit['items'], 'score'),
            array_column($pageAudit, 'score'),
        );
        $overallScore = count($allScores) > 0 ? (int) round(array_sum($allScores) / count($allScores)) : 0;

        return response()->json([
            'overall_score' => $overallScore,
            'posts'    => $postAudit,
            'services' => $serviceAudit,
            'projects' => $projectAudit,
            'features' => $featureAudit,
            'pages'    => [
                'total'         => $pageStats['total'],
                'average_score' => $pageStats['average_score'],
                'perfect'       => $pageStats['perfect'],
                'needs_work'    => $pageStats['needs_work'],
                'items'         => $pageAudit,
            ],
            'field_config' => $config,
        ]);
    }

    private function auditCollection($items, array $seoFields, string $type, string $editPathTemplate, array $recommended = []): array
    {
        $audited = [];
        foreach ($items as $item) {
            $filled = 0;
            $total = count($seoFields);
            foreach ($seoFields as $field) {
                if (! empty($item->{$field})) $filled++;
            }
            $audited[] = [
                'id'        => $item->id,
                'title'     => $item->title,
                'slug'      => $item->slug,
                'type'      => $type,
                'edit_path' => str_replace('{id}', (string) $item->id, $editPathTemplate),
                'score'     => $total > 0 ? (int) round(($filled / $total) * 100) : 0,
                'filled'    => $filled,
                'total'     => $total,
                'fields'    => $this->buildFieldStatus($item->toArray(), $seoFields, $recommended),
            ];
        }

        return array_merge($this->summaryFromItems($audited), ['items' => $audited]);
    }

    private function summaryFromItems(array $items): array
    {
        $scores = array_column($items, 'score');
        $avg = count($scores) > 0 ? (int) round(array_sum($scores) / count($scores)) : 0;

        return [
            'total'         => count($items),
            'average_score' => $avg,
            'perfect'       => count(array_filter($scores, fn($s) => (int) $s === 100)),
            'needs_work'    => count(array_filter($scores, fn($s) => (int) $s < 100)),
        ];
    }

    /**
     * Build a per-field status map. `filled` answers "is the field set?";
     * `length` lets the UI flag values outside the recommended window
     * (e.g. meta_title > 60 chars truncates in search results).
     */
    private function buildFieldStatus($data, array $seoFields, array $recommended = []): array
    {
        $status = [];
        foreach ($seoFields as $field) {
            $value = is_array($data) ? ($data[$field] ?? null) : ($data->{$field} ?? null);

            // Arrays (json casts, keyword lists) are filled when non-empty.
            if (is_array($value)) {
                $isFilled = count($value) > 0;
                $length = mb_strlen(json_encode($value));
            } else {
                $isFilled = ! empty($value);
                $length = is_string($value) ? mb_strlen($value) : 0;
            }

            $entry = [
                'filled' => $isFilled,
                'length' => $length,
            ];

            if ($isFilled && isset($recommended[$field])) {
                [$min, $max] = $recommended[$field];
                $entry['recommended_min'] = $min;
                $entry['recommended_max'] = $max;
                if ($length < $min) $entry['length_status'] = 'short';
                elseif ($length > $max) $entry['length_status'] = 'long';
                else $entry['length_status'] = 'ideal';
            }

            $status[$field] = $entry;
        }

        return $status;
    }

    // ─── AUTO-GENERATE SEO ──────────────────────────────────────

    /**
     * Auto-generate SEO metadata for a batch of items.
     */
    public function autoGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:blog,service',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'overwrite' => 'boolean',
        ]);

        $type = $validated['type'];
        $ids = $validated['ids'];
        $overwrite = $validated['overwrite'] ?? false;

        $updated = 0;

        DB::transaction(function () use ($type, $ids, $overwrite, &$updated) {
            $model = $type === 'blog' ? BlogPost::class : Service::class;
            $items = $model::whereIn('id', $ids)->get();

            foreach ($items as $item) {
                $changes = $this->generateSeoFields($item, $type, $overwrite);
                if (!empty($changes)) {
                    $item->update($changes);
                    $updated++;
                }
            }
        });

        $this->logActivity('seo_auto_generated', "Auto-generated SEO for {$updated} {$type} items.");

        return response()->json([
            'message' => "SEO generated for {$updated} items.",
            'updated' => $updated,
        ]);
    }

    /**
     * Preview what auto-generate would produce for a single item (without saving).
     */
    public function autoGeneratePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:blog,service',
            'id' => 'required|integer',
        ]);

        $model = $validated['type'] === 'blog' ? BlogPost::class : Service::class;
        $item = $model::findOrFail($validated['id']);
        $generated = $this->generateSeoFields($item, $validated['type'], true);

        return response()->json(['data' => $generated]);
    }

    private function generateSeoFields($item, string $type, bool $overwrite): array
    {
        $changes = [];
        $title = $item->title;
        $description = '';

        if ($type === 'blog') {
            $description = $item->excerpt ?: Str::limit(strip_tags($item->content ?? ''), 155);
        } else {
            $description = $item->description ?: '';
        }

        $metaTitle = Str::limit($title . ' | DigiDittos', 60, '');
        $metaDesc = Str::limit($description, 155, '...');

        if ($overwrite || empty($item->meta_title)) {
            $changes['meta_title'] = $metaTitle;
        }
        if ($overwrite || empty($item->meta_description)) {
            $changes['meta_description'] = $metaDesc;
        }
        if ($overwrite || empty($item->og_title)) {
            $changes['og_title'] = $title;
        }
        if ($overwrite || empty($item->og_description)) {
            $changes['og_description'] = $metaDesc;
        }
        if ($overwrite || empty($item->og_image)) {
            if (!empty($item->featured_image)) {
                $changes['og_image'] = $item->featured_image;
            }
        }

        // Auto-generate JSON-LD
        if ($overwrite || empty($item->json_ld_schema)) {
            $changes['json_ld_schema'] = $this->generateJsonLd($item, $type);
        }

        return $changes;
    }

    private function generateJsonLd($item, string $type): array
    {
        if ($type === 'blog') {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $item->title,
                'description' => Str::limit(strip_tags($item->content ?? ''), 200),
                'image' => $item->featured_image ?? '',
                'datePublished' => $item->published_at?->toIso8601String() ?? now()->toIso8601String(),
                'dateModified' => $item->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'DigiDittos',
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $item->title,
            'description' => $item->description ?? '',
            'provider' => [
                '@type' => 'Organization',
                'name' => 'DigiDittos',
            ],
        ];
    }

    // ─── ROBOTS.TXT ─────────────────────────────────────────────

    public function getRobotsTxt(): JsonResponse
    {
        $content = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'robots_txt')
            ->value('value');

        if (empty($content)) {
            $content = $this->defaultRobotsTxt();
        }

        return response()->json(['data' => $content]);
    }

    public function updateRobotsTxt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        SiteSetting::setValue('robots_txt', $validated['content'], 'string', 'seo_engine');
        $this->logActivity('robots_txt_updated', 'robots.txt content was updated.');

        return response()->json(['message' => 'robots.txt updated successfully.']);
    }

    private function defaultRobotsTxt(): string
    {
        $base = config('app.url', 'https://www.digidittos.com');
        return "User-agent: *\nAllow: /\n\nSitemap: {$base}/sitemap.xml\n";
    }

    // ─── LLMS.TXT ───────────────────────────────────────────────

    public function getLlmsTxt(): JsonResponse
    {
        $content = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'llms_txt')
            ->value('value');

        $contentFull = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'llms_full_txt')
            ->value('value');

        if (empty($content)) {
            $content = $this->defaultLlmsTxt();
        }

        return response()->json([
            'data' => [
                'llms_txt' => $content,
                'llms_full_txt' => $contentFull ?? '',
            ],
        ]);
    }

    public function updateLlmsTxt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'llms_txt' => 'required|string|max:20000',
            'llms_full_txt' => 'nullable|string|max:50000',
        ]);

        DB::transaction(function () use ($validated) {
            SiteSetting::setValue('llms_txt', $validated['llms_txt'], 'string', 'seo_engine');
            if (isset($validated['llms_full_txt'])) {
                SiteSetting::setValue('llms_full_txt', $validated['llms_full_txt'], 'string', 'seo_engine');
            }
        });

        $this->logActivity('llms_txt_updated', 'llms.txt content was updated.');

        return response()->json(['message' => 'llms.txt updated successfully.']);
    }

    private function defaultLlmsTxt(): string
    {
        return "# DigiDittos\n\n> Web, mobile, AI, and SaaS development studio.\n\n## About\n\nDigiDittos is a full-service software engineering studio. We partner with startups and enterprises to design, build, and scale digital products — from AI-powered platforms to enterprise SaaS and high-traffic web applications.\n\n## Services\n\n- Strategic Consultancy — Architecture Review, Code Quality Report, Scalability Assessment\n- Software Engineering — Web, Android, iOS, UI/UX, SaaS Platforms\n- AI Solutions — AI Agents, LangChain, LangGraph, NLP, LLM Integration\n- Digital Marketing — SEO, Meta Marketing, Google Marketing\n- CMS Development — WordPress, Shopify, No-Code Websites\n";
    }

    // ─── SITEMAP ────────────────────────────────────────────────

    public function getSitemapConfig(): JsonResponse
    {
        $config = SiteSetting::getGroup('seo_sitemap');

        if (empty($config)) {
            $config = [
                'include_posts' => true,
                'include_services' => true,
                'include_projects' => true,
                'include_features' => true,
                'include_pages' => true,
                'posts_priority' => '0.7',
                'services_priority' => '0.8',
                'projects_priority' => '0.7',
                'features_priority' => '0.7',
                'pages_priority' => '0.9',
                'posts_changefreq' => 'weekly',
                'services_changefreq' => 'monthly',
                'projects_changefreq' => 'monthly',
                'features_changefreq' => 'monthly',
                'pages_changefreq' => 'monthly',
            ];
        }

        return response()->json(['data' => $config]);
    }

    public function updateSitemapConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'include_posts' => 'boolean',
            'include_services' => 'boolean',
            'include_projects' => 'boolean',
            'include_features' => 'boolean',
            'include_pages' => 'boolean',
            'posts_priority' => 'nullable|string|in:0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1.0',
            'services_priority' => 'nullable|string|in:0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1.0',
            'projects_priority' => 'nullable|string|in:0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1.0',
            'features_priority' => 'nullable|string|in:0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1.0',
            'pages_priority' => 'nullable|string|in:0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1.0',
            'posts_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'services_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'projects_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'features_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'pages_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                $type = is_bool($value) ? 'boolean' : 'string';
                SiteSetting::setValue($key, $value, $type, 'seo_sitemap');
            }
        });

        $this->logActivity('sitemap_config_updated', 'Sitemap configuration was updated.');

        return response()->json([
            'message' => 'Sitemap configuration updated.',
            'data' => SiteSetting::getGroup('seo_sitemap'),
        ]);
    }

    /**
     * Preview the sitemap entries (not the XML, just the data).
     */
    public function sitemapPreview(): JsonResponse
    {
        $config = SiteSetting::getGroup('seo_sitemap');
        $baseUrl = config('app.url', 'https://www.digidittos.com');
        $entries = [];

        // Static pages
        $staticPages = [
            ['loc' => '/',          'label' => 'Homepage'],
            ['loc' => '/about',     'label' => 'About'],
            ['loc' => '/services',  'label' => 'Services'],
            ['loc' => '/projects',  'label' => 'Projects'],
            ['loc' => '/blog',      'label' => 'Blog'],
            ['loc' => '/contact',   'label' => 'Contact'],
        ];

        if ($config['include_pages'] ?? true) {
            foreach ($staticPages as $page) {
                $entries[] = [
                    'loc' => $baseUrl . $page['loc'],
                    'label' => $page['label'],
                    'type' => 'page',
                    'priority' => $config['pages_priority'] ?? '0.9',
                    'changefreq' => $config['pages_changefreq'] ?? 'monthly',
                ];
            }
        }

        // Services
        if ($config['include_services'] ?? true) {
            $services = Service::active()->ordered()->select('slug', 'title', 'updated_at')->get();
            foreach ($services as $svc) {
                $entries[] = [
                    'loc' => $baseUrl . '/services/' . $svc->slug,
                    'label' => $svc->title,
                    'type' => 'service',
                    'lastmod' => $svc->updated_at?->toDateString(),
                    'priority' => $config['services_priority'] ?? '0.8',
                    'changefreq' => $config['services_changefreq'] ?? 'monthly',
                ];
            }
        }

        // Blog Posts
        if ($config['include_posts'] ?? true) {
            $posts = BlogPost::published()->select('slug', 'title', 'updated_at')->orderByDesc('published_at')->get();
            foreach ($posts as $post) {
                $entries[] = [
                    'loc' => $baseUrl . '/blog/' . $post->slug,
                    'label' => $post->title,
                    'type' => 'post',
                    'lastmod' => $post->updated_at?->toDateString(),
                    'priority' => $config['posts_priority'] ?? '0.7',
                    'changefreq' => $config['posts_changefreq'] ?? 'weekly',
                ];
            }
        }

        // Projects
        if ($config['include_projects'] ?? true) {
            $projects = Project::published()->select('slug', 'title', 'updated_at')->get();
            foreach ($projects as $proj) {
                $entries[] = [
                    'loc' => $baseUrl . '/projects/' . $proj->slug,
                    'label' => $proj->title,
                    'type' => 'project',
                    'lastmod' => $proj->updated_at?->toDateString(),
                    'priority' => $config['projects_priority'] ?? '0.7',
                    'changefreq' => $config['projects_changefreq'] ?? 'monthly',
                ];
            }
        }

        // Service Features (render at /services/{slug} on the business site)
        if ($config['include_features'] ?? true) {
            $features = ServiceFeature::active()->ordered()->select('slug', 'title', 'updated_at')->get();
            foreach ($features as $feat) {
                $entries[] = [
                    'loc' => $baseUrl . '/services/' . $feat->slug,
                    'label' => $feat->title,
                    'type' => 'feature',
                    'lastmod' => $feat->updated_at?->toDateString(),
                    'priority' => $config['features_priority'] ?? '0.7',
                    'changefreq' => $config['features_changefreq'] ?? 'monthly',
                ];
            }
        }

        return response()->json(['data' => $entries, 'total' => count($entries)]);
    }

    // ─── PUBLIC: SITEMAP.XML ────────────────────────────────────

    public function publicSitemapXml(): Response
    {
        $config = SiteSetting::getGroup('seo_sitemap');
        $baseUrl = config('app.url', 'https://www.digidittos.com');
        $urls = [];

        // Static pages
        if ($config['include_pages'] ?? true) {
            $pages = ['/', '/about', '/services', '/projects', '/blog', '/contact'];
            foreach ($pages as $path) {
                $urls[] = [
                    'loc' => $baseUrl . $path,
                    'priority' => $config['pages_priority'] ?? '0.9',
                    'changefreq' => $config['pages_changefreq'] ?? 'monthly',
                ];
            }
        }

        if ($config['include_features'] ?? true) {
            $features = ServiceFeature::active()->ordered()->select('slug', 'updated_at')->get();
            foreach ($features as $feat) {
                $urls[] = [
                    'loc' => $baseUrl . '/services/' . $feat->slug,
                    'lastmod' => $feat->updated_at?->toDateString(),
                    'priority' => $config['features_priority'] ?? '0.7',
                    'changefreq' => $config['features_changefreq'] ?? 'monthly',
                ];
            }
        }

        if ($config['include_projects'] ?? true) {
            $projects = Project::published()->select('slug', 'updated_at')->get();
            foreach ($projects as $proj) {
                $urls[] = [
                    'loc' => $baseUrl . '/projects/' . $proj->slug,
                    'lastmod' => $proj->updated_at?->toDateString(),
                    'priority' => $config['projects_priority'] ?? '0.7',
                    'changefreq' => $config['projects_changefreq'] ?? 'monthly',
                ];
            }
        }

        if ($config['include_posts'] ?? true) {
            $posts = BlogPost::published()->select('slug', 'updated_at')->orderByDesc('published_at')->get();
            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => $baseUrl . '/blog/' . $post->slug,
                    'lastmod' => $post->updated_at?->toDateString(),
                    'priority' => $config['posts_priority'] ?? '0.7',
                    'changefreq' => $config['posts_changefreq'] ?? 'weekly',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    // ─── PUBLIC: ROBOTS.TXT ─────────────────────────────────────

    public function publicRobotsTxt(): Response
    {
        $content = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'robots_txt')
            ->value('value');

        if (empty($content)) {
            $content = $this->defaultRobotsTxt();
        }

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    // ─── PUBLIC: LLMS.TXT ───────────────────────────────────────

    public function publicLlmsTxt(): Response
    {
        $content = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'llms_txt')
            ->value('value');

        if (empty($content)) {
            $content = $this->defaultLlmsTxt();
        }

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function publicLlmsFullTxt(): Response
    {
        $content = SiteSetting::where('group', 'seo_engine')
            ->where('key', 'llms_full_txt')
            ->value('value');

        return response($content ?? '', 200, ['Content-Type' => 'text/plain']);
    }

    // ─── PUBLIC: REDIRECTS CHECK ────────────────────────────────

    public function publicRedirects(): JsonResponse
    {
        $redirects = SeoRedirect::active()
            ->select('source_path', 'target_path', 'status_code')
            ->get();

        return response()->json(['data' => $redirects]);
    }

    // ─── ADMIN: REDIRECTS CRUD ──────────────────────────────────

    public function redirects(Request $request): JsonResponse
    {
        $query = SeoRedirect::orderByDesc('updated_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('source_path', 'like', "%{$s}%")
                  ->orWhere('target_path', 'like', "%{$s}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function storeRedirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_path' => 'required|string|max:500|unique:seo_redirects,source_path',
            'target_path' => 'required|string|max:500',
            'status_code' => 'required|integer|in:301,302',
            'is_active' => 'boolean',
        ]);

        $redirect = SeoRedirect::create($validated);
        $this->logActivity('redirect_created', "Redirect created: {$validated['source_path']} → {$validated['target_path']}");

        return response()->json(['message' => 'Redirect created.', 'data' => $redirect], 201);
    }

    public function updateRedirect(Request $request, int $id): JsonResponse
    {
        $redirect = SeoRedirect::findOrFail($id);

        $validated = $request->validate([
            'source_path' => ['sometimes', 'required', 'string', 'max:500', Rule::unique('seo_redirects')->ignore($id)],
            'target_path' => 'sometimes|required|string|max:500',
            'status_code' => 'sometimes|required|integer|in:301,302',
            'is_active' => 'boolean',
        ]);

        $redirect->update($validated);
        $this->logActivity('redirect_updated', "Redirect updated: {$redirect->source_path}");

        return response()->json(['message' => 'Redirect updated.', 'data' => $redirect]);
    }

    public function destroyRedirect(int $id): JsonResponse
    {
        $redirect = SeoRedirect::findOrFail($id);
        $source = $redirect->source_path;
        $redirect->delete();

        $this->logActivity('redirect_deleted', "Redirect deleted: {$source}");

        return response()->json(['message' => 'Redirect deleted.']);
    }
}
