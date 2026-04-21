<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutSection;
use App\Models\BlogPost;
use App\Models\HomepageSection;
use App\Models\ListingPageSection;
use App\Models\ServicePanel;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;

class PageSettingsController extends Controller
{
    use LogsActivity;
    // ─── PUBLIC ───────────────────────────────────────────────

    /**
     * Public: returns CMS branding settings (logo).
     */
    public function publicCmsSettings(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::getGroup('cms')]);
    }

    /**
     * Public: returns all active homepage sections + site settings needed for rendering.
     */
    public function publicHomepage(): JsonResponse
    {
        $sections = HomepageSection::active()->ordered()->get()
            ->mapWithKeys(fn($section) => [
                $section->section_key => [
                    'title' => $section->title,
                    'content' => $section->content,
                    'sort_order' => $section->sort_order,
                ],
            ])
            ->toArray();

        // Resolve the Blog section's chosen post IDs into full BlogPost
        // objects so the business site can render them in a single round trip.
        if (isset($sections['blog'])) {
            $ids = collect($sections['blog']['content']['post_ids'] ?? [])
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($ids->isNotEmpty()) {
                $posts = BlogPost::published()
                    ->with(['category', 'tags', 'author:id,name,avatar'])
                    ->whereIn('id', $ids)
                    ->get()
                    // preserve the admin-chosen order
                    ->sortBy(fn ($p) => $ids->search($p->id))
                    ->values();

                $sections['blog']['content']['posts'] = $posts;
            } else {
                $sections['blog']['content']['posts'] = [];
            }
        }

        $seo = $this->getPageSeo('homepage');
        $site = SiteSetting::getGroup('general');
        $hero = SiteSetting::getGroup('hero');

        return response()->json([
            'sections' => $sections,
            'seo' => $seo,
            'site' => array_merge($site, $hero),
        ]);
    }

    /**
     * Public: returns public site settings (footer, contact, social, seo defaults).
     */
    public function publicSiteSettings(): JsonResponse
    {
        return response()->json([
            'general' => SiteSetting::getGroup('general'),
            'footer' => SiteSetting::getGroup('footer'),
            'seo' => SiteSetting::getGroup('seo'),
            'hero' => SiteSetting::getGroup('hero'),
        ]);
    }

    /**
     * Public: returns all active service panels ordered for the carousel.
     */
    public function publicServicePanels(): JsonResponse
    {
        $panels = ServicePanel::active()->ordered()->get();

        return response()->json(['panels' => $panels]);
    }

    /**
     * Public: returns site-wide content blocks (FAQ, Testimonials, CTA) that
     * render across multiple pages (Home, About, etc.). These are stored as
     * rows in homepage_sections but exposed here so any page can consume them
     * without fetching the full homepage payload.
     */
    public function publicSiteContent(): JsonResponse
    {
        $keys = ['faq', 'testimonials', 'cta'];
        $sections = HomepageSection::active()
            ->whereIn('section_key', $keys)
            ->get()
            ->mapWithKeys(fn($section) => [
                $section->section_key => [
                    'title' => $section->title,
                    'content' => $section->content,
                ],
            ]);

        return response()->json(['sections' => $sections]);
    }

    // ─── ADMIN: HOMEPAGE SECTIONS ─────────────────────────────

    /**
     * Admin: all homepage sections (including inactive).
     */
    public function indexHomepage(): JsonResponse
    {
        $sections = HomepageSection::ordered()->get();

        return response()->json(['data' => $sections]);
    }

    /**
     * Admin: update a section's content.
     */
    public function updateHomepageSection(Request $request, string $sectionKey): JsonResponse
    {
        $section = HomepageSection::where('section_key', $sectionKey)->firstOrFail();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $section->update($validated);

        $this->logActivity('page_updated', "Homepage section \"{$sectionKey}\" was updated.");

        return response()->json(['data' => $section->fresh()]);
    }

    /**
     * Admin: reorder sections.
     */
    public function reorderHomepageSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.key' => 'required|string',
            'sections.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['sections'] as $item) {
                HomepageSection::where('section_key', $item['key'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'message' => 'Sections reordered successfully.',
            'data' => HomepageSection::ordered()->get(),
        ]);
    }

    // ─── ADMIN: SERVICE PANELS ─────────────────────────────────

    /**
     * Admin: all service panels (including inactive).
     */
    public function indexServicePanels(): JsonResponse
    {
        $panels = ServicePanel::ordered()->get();

        return response()->json(['data' => $panels]);
    }

    /**
     * Admin: create a new service panel.
     */
    public function storeServicePanel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $maxOrder = ServicePanel::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);

        $panel = ServicePanel::create($validated);

        $this->logActivity('panel_created', "Service panel \"{$panel->name}\" was created.");

        return response()->json(['data' => $panel], 201);
    }

    /**
     * Admin: delete a service panel.
     */
    public function destroyServicePanel(int $id): JsonResponse
    {
        $panel = ServicePanel::findOrFail($id);
        $this->logActivity('panel_deleted', "Service panel \"{$panel->name}\" was deleted.");
        $panel->delete();

        return response()->json(['message' => 'Service panel deleted successfully.']);
    }

    /**
     * Admin: update a service panel.
     */
    public function updateServicePanel(Request $request, int $id): JsonResponse
    {
        $panel = ServicePanel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'image_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'button_text' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $panel->update($validated);

        $this->logActivity('panel_updated', "Service panel was updated.");

        return response()->json(['data' => $panel->fresh()]);
    }

    /**
     * Admin: reorder service panels.
     */
    public function reorderServicePanels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panels' => 'required|array',
            'panels.*.id' => 'required|integer|exists:service_panels,id',
            'panels.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['panels'] as $item) {
                ServicePanel::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'message' => 'Service panels reordered successfully.',
            'data' => ServicePanel::ordered()->get(),
        ]);
    }

    // ─── ADMIN: SITE SETTINGS ─────────────────────────────────

    /**
     * Admin: all site settings grouped.
     */
    public function indexSiteSettings(): JsonResponse
    {
        $settings = SiteSetting::all()->groupBy('group')->map(function ($group) {
            return $group->mapWithKeys(function ($setting) {
                return [$setting->key => [
                    'value' => match ($setting->type) {
                        'integer' => (int) $setting->value,
                        'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                        'json' => json_decode($setting->value, true),
                        default => $setting->value,
                    },
                    'type' => $setting->type,
                ]];
            });
        });

        return response()->json(['data' => $settings]);
    }

    /**
     * Known site-setting keys → their canonical group. Used when an admin
     * saves a value for a key the DB doesn't yet have a row for (e.g. a
     * brand-new `social_github` on a CMS that hasn't re-seeded yet). Without
     * this, `updateOrCreate` would place the row in the wrong group and
     * `publicSiteSettings` wouldn't surface it to the business site.
     */
    private const SETTING_GROUP_MAP = [
        // footer group — contact + socials + copyright + tagline
        'footer_copyright'      => 'footer',
        'newsletter_label'      => 'footer',
        'contact_email'         => 'footer',
        'contact_phone'         => 'footer',
        'contact_address'       => 'footer',
        'contact_response_time' => 'footer',
        'contact_tagline'       => 'footer',
        'social_linkedin'       => 'footer',
        'social_twitter'        => 'footer',
        'social_instagram'      => 'footer',
        'social_facebook'       => 'footer',
        'social_github'         => 'footer',
        'social_pinterest'      => 'footer',
        // general group — brand
        'site_name'             => 'general',
        'site_tagline'          => 'general',
        'site_url'              => 'general',
    ];

    /**
     * Admin: bulk update settings. Upserts rows so brand-new keys (e.g.
     * a freshly-added `social_github` field) are created in the right
     * group rather than silently dropped.
     */
    public function updateSiteSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['settings'] as $key => $value) {
                $existing = SiteSetting::where('key', $key)->first();
                $type = $existing->type ?? 'string';
                $group = $existing->group ?? (self::SETTING_GROUP_MAP[$key] ?? null);

                // Skip unknown keys with no existing row AND no group mapping —
                // don't pollute the settings table with arbitrary input.
                if (!$existing && !$group) {
                    continue;
                }

                $storedValue = match ($type) {
                    'json' => json_encode($value),
                    'boolean' => $value ? '1' : '0',
                    default => (string) ($value ?? ''),
                };

                SiteSetting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $storedValue, 'type' => $type],
                );
            }
        });

        $this->logActivity('settings_updated', "Site settings were updated.");

        return response()->json(['message' => 'Settings updated successfully.']);
    }

    // ─── ADMIN: SEO SETTINGS ──────────────────────────────────

    /**
     * Admin: SEO settings for a specific page (or global fallback).
     */
    public function indexSeo(Request $request): JsonResponse
    {
        $page = $request->query('page', 'global');
        $group = $page === 'global' ? 'seo' : "seo_{$page}";

        $data = SiteSetting::getGroup($group);

        // If page-specific SEO is empty, return global as fallback
        if (empty($data) && $page !== 'global') {
            $data = SiteSetting::getGroup('seo');
        }

        return response()->json(['data' => $data, 'page' => $page]);
    }

    /**
     * Admin: update SEO settings for a specific page.
     */
    public function updateSeo(Request $request): JsonResponse
    {
        $page = $request->input('page', 'global');
        $group = $page === 'global' ? 'seo' : "seo_{$page}";

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string',
            'json_ld_schema' => 'nullable',
        ]);

        DB::transaction(function () use ($validated, $group) {
            foreach ($validated as $key => $value) {
                // meta_keywords is a JSON array; json_ld_schema is a JSON blob.
                // Everything else is a plain string stored verbatim.
                $type = in_array($key, ['json_ld_schema', 'meta_keywords'], true) ? 'json' : 'string';
                SiteSetting::setValue($key, $value, $type, $group);
            }
        });

        $this->logActivity('seo_updated', "SEO settings for '{$page}' were updated.");

        return response()->json([
            'message' => 'SEO settings updated successfully.',
            'data' => SiteSetting::getGroup($group),
        ]);
    }

    /**
     * Helper: get SEO data for a specific page with global fallback.
     */
    private function getPageSeo(string $page): array
    {
        $pageSeo = SiteSetting::getGroup("seo_{$page}");
        $globalSeo = SiteSetting::getGroup('seo');

        // Merge: page-specific values override global, but fall back to
        // global for empty fields. meta_keywords included so the
        // business-site generateMetadata can surface it.
        $merged = [];
        $keys = ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image', 'json_ld_schema'];
        foreach ($keys as $key) {
            $pageVal = $pageSeo[$key] ?? null;
            $merged[$key] = (!empty($pageVal)) ? $pageVal : ($globalSeo[$key] ?? null);
        }

        return $merged;
    }

    // ─── PUBLIC: ABOUT PAGE ──────────────────────────────────

    /**
     * Public: returns all active about page sections.
     */
    public function publicAbout(): JsonResponse
    {
        $sections = AboutSection::active()->ordered()->get()
            ->mapWithKeys(fn($section) => [
                $section->section_key => [
                    'title' => $section->title,
                    'content' => $section->content,
                    'sort_order' => $section->sort_order,
                ],
            ]);

        return response()->json([
            'sections' => $sections,
            'seo' => $this->getPageSeo('about'),
        ]);
    }

    // ─── ADMIN: ABOUT PAGE SECTIONS ─────────────────────────

    /**
     * Admin: all about page sections (including inactive).
     */
    public function indexAbout(): JsonResponse
    {
        $sections = AboutSection::ordered()->get();

        return response()->json(['data' => $sections]);
    }

    /**
     * Admin: update an about section's content.
     */
    public function updateAboutSection(Request $request, string $sectionKey): JsonResponse
    {
        $section = AboutSection::where('section_key', $sectionKey)->firstOrFail();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $section->update($validated);

        $this->logActivity('page_updated', "About section \"{$sectionKey}\" was updated.");

        return response()->json(['data' => $section->fresh()]);
    }

    /**
     * Admin: reorder about sections.
     */
    public function reorderAboutSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.key' => 'required|string',
            'sections.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['sections'] as $item) {
                AboutSection::where('section_key', $item['key'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'message' => 'About sections reordered successfully.',
            'data' => AboutSection::ordered()->get(),
        ]);
    }

    // ─── LISTING PAGES (projects / blog / services) ─────────
    // The three index/catalogue pages share one set of endpoints
    // since each page only stores a hero (and, for services, an
    // Approach section). SEO for each page reuses the existing
    // /page-settings/seo?page=... pattern via `seo_{slug}` groups.

    /**
     * Whitelist of listing pages the CMS knows about. Keeps URL
     * slugs from leaking into unbounded SiteSetting groups.
     */
    private const LISTING_PAGES = ['projects', 'blog', 'services'];

    /**
     * Public: listing page content + merged SEO.
     * Consumed by the business site's `/projects`, `/blog`, `/services` routes.
     */
    public function publicListingPage(string $page): JsonResponse
    {
        abort_unless(in_array($page, self::LISTING_PAGES, true), 404);

        $sections = ListingPageSection::forPage($page)->active()->ordered()->get()
            ->mapWithKeys(fn($s) => [
                $s->section_key => [
                    'title' => $s->title,
                    'content' => $s->content,
                    'sort_order' => $s->sort_order,
                ],
            ]);

        return response()->json([
            'page' => $page,
            'sections' => $sections,
            'seo' => $this->getPageSeo($page),
        ]);
    }

    /**
     * Admin: all sections for a listing page (including inactive).
     */
    public function indexListingPage(string $page): JsonResponse
    {
        abort_unless(in_array($page, self::LISTING_PAGES, true), 404);

        $sections = ListingPageSection::forPage($page)->ordered()->get();

        return response()->json(['data' => $sections]);
    }

    /**
     * Admin: update a section's content on a listing page.
     */
    public function updateListingSection(Request $request, string $page, string $sectionKey): JsonResponse
    {
        abort_unless(in_array($page, self::LISTING_PAGES, true), 404);

        $section = ListingPageSection::forPage($page)
            ->where('section_key', $sectionKey)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $section->update($validated);

        $this->logActivity('page_updated', "Listing page '{$page}' section \"{$sectionKey}\" was updated.");

        return response()->json(['data' => $section->fresh()]);
    }

    // ─── ADMIN: CMS SETTINGS ────────────────────────────────

    /**
     * Admin: get CMS settings (logo, etc.).
     */
    public function indexCmsSettings(): JsonResponse
    {
        return response()->json(['data' => SiteSetting::getGroup('cms')]);
    }

    /**
     * Admin: update CMS settings.
     */
    public function updateCmsSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            SiteSetting::setValue($key, $value, 'string', 'cms');
        }

        $this->logActivity('settings_updated', 'CMS settings were updated.');

        return response()->json([
            'message' => 'CMS settings updated successfully.',
            'data' => SiteSetting::getGroup('cms'),
        ]);
    }
}
