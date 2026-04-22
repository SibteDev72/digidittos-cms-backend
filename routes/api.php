<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PageSettingsController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SeoEngineController;
use App\Http\Controllers\Api\LiveAvatarController;
use App\Http\Controllers\Api\DemoRequestController;
use App\Http\Controllers\Api\ContactController;

// Storage diag — confirms the Render persistent disk is mounted,
// the public-storage symlink is intact, and how many uploaded files
// actually made it to disk. Hit GET /api/__storage-diag.
Route::get('/__storage-diag', function () {
    $publicDisk = storage_path('app/public');
    $mediaDir = $publicDisk . '/uploads/media';
    $symlink = public_path('storage');

    $countFiles = function (string $dir) {
        if (!is_dir($dir)) return 0;
        return count(array_filter(scandir($dir), fn ($f) => $f !== '.' && $f !== '..'));
    };

    return response()->json([
        'storage_path' => $publicDisk,
        'storage_exists' => is_dir($publicDisk),
        'storage_writable' => is_writable($publicDisk),
        'media_dir_exists' => is_dir($mediaDir),
        'media_dir_writable' => is_dir($mediaDir) ? is_writable($mediaDir) : null,
        'media_file_count' => $countFiles($mediaDir),
        'public_storage_symlink_exists' => file_exists($symlink),
        'public_storage_is_link' => is_link($symlink),
        'public_storage_link_target' => is_link($symlink) ? readlink($symlink) : null,
        'app_url' => config('app.url'),
        'filesystem_disk' => config('filesystems.default'),
        'public_disk_url' => config('filesystems.disks.public.url'),
        // First 5 filenames in media dir (if any) for sanity check
        'sample_files' => is_dir($mediaDir)
            ? array_slice(array_values(array_filter(scandir($mediaDir), fn ($f) => $f !== '.' && $f !== '..')), 0, 5)
            : [],
    ]);
});

// Deployment diagnostic — safe to leave in place; returns bootstrap
// health (DB connectivity, session/cache drivers, key table presence)
// so we can diagnose 500s without needing shell access on Render.
// Hit GET /api/__diag to see JSON status.
Route::get('/__diag', function () {
    // Inspect env var plumbing at three layers so we can tell whether:
    //   - Render is injecting vars into the PHP process at all (raw)
    //   - Laravel's Dotenv is loading them (env() helper)
    //   - The resulting config array reflects them (config())
    // If `raw` has the value but `config` doesn't, there's a cache
    // file shadowing it. If `raw` is empty, env isn't reaching PHP.
    $keys = ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'APP_KEY',
             'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
             'SESSION_DRIVER', 'CACHE_STORE'];

    $mask = fn ($v) => $v === null || $v === false ? $v
        : (in_array($v, [''], true) ? '' : (strlen((string) $v) > 80 ? substr((string) $v, 0, 40) . '…' : (string) $v));

    $raw = [];
    $viaEnv = [];
    foreach ($keys as $k) {
        $getenv = getenv($k);
        $raw[$k] = [
            'getenv' => $mask($getenv === false ? null : $getenv),
            '_ENV' => $mask($_ENV[$k] ?? null),
            '_SERVER' => $mask($_SERVER[$k] ?? null),
        ];
        $viaEnv[$k] = $mask(env($k));
    }

    $configCacheExists = file_exists(base_path('bootstrap/cache/config.php'));

    $report = [
        'php' => PHP_VERSION,
        'config_cache_exists' => $configCacheExists,
        'env_file_exists' => file_exists(base_path('.env')),
        'env_file_keys' => file_exists(base_path('.env'))
            ? array_values(array_filter(array_map(
                fn ($l) => preg_match('/^([A-Z_][A-Z0-9_]*)=/', trim($l), $m) ? $m[1] : null,
                file(base_path('.env'))
            )))
            : [],

        'raw_process_env' => $raw,
        'laravel_env_helper' => $viaEnv,

        'config' => [
            'env' => app()->environment(),
            'debug' => config('app.debug'),
            'app_key_set' => str_starts_with((string) config('app.key'), 'base64:'),
            'url' => config('app.url'),
            'session_driver' => config('session.driver'),
            'cache_store' => config('cache.default'),
            'db_connection' => config('database.default'),
        ],
    ];

    try {
        \DB::connection()->getPdo();
        $report['db'] = 'connected';
        $report['db_name'] = \DB::connection()->getDatabaseName();
        foreach (['sessions', 'cache', 'users', 'migrations', 'site_settings'] as $t) {
            $report['tables'][$t] = \Schema::hasTable($t);
        }
    } catch (\Throwable $e) {
        $report['db'] = 'FAILED: ' . $e->getMessage();
    }
    return response()->json($report);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/public/pricing', [PricingController::class, 'publicIndex']);
Route::get('/public/services', [ServiceController::class, 'publicIndex']);
Route::get('/public/services/{slug}', [ServiceController::class, 'publicShow']);
// Public feature detail (powers /services/{feature-slug} on the business site)
Route::get('/public/features/{slug}', [\App\Http\Controllers\Api\FeatureController::class, 'publicShow']);

// Public Projects
Route::get('/public/projects/categories', [ProjectController::class, 'publicCategories']);
Route::get('/public/projects/tags', [ProjectController::class, 'publicTags']);
Route::get('/public/projects', [ProjectController::class, 'publicIndex']);
Route::get('/public/projects/{slug}', [ProjectController::class, 'publicShow']);

// Public Blog
Route::get('/public/blog/categories', [BlogController::class, 'publicCategories']);
Route::get('/public/blog/tags', [BlogController::class, 'publicTags']);
Route::get('/public/blog/featured', [BlogController::class, 'publicFeatured']);
Route::get('/public/blog/popular', [BlogController::class, 'publicPopular']);
Route::get('/public/blog', [BlogController::class, 'publicIndex']);
Route::get('/public/blog/{slug}/related', [BlogController::class, 'publicRelated']);
Route::get('/public/blog/{slug}/adjacent', [BlogController::class, 'publicAdjacent']);
Route::post('/public/blog/{slug}/view', [BlogController::class, 'publicIncrementView']);
Route::get('/public/blog/{slug}', [BlogController::class, 'publicShow']);

// Public SEO Engine
Route::get('/public/sitemap.xml', [SeoEngineController::class, 'publicSitemapXml']);
Route::get('/public/robots.txt', [SeoEngineController::class, 'publicRobotsTxt']);
Route::get('/public/llms.txt', [SeoEngineController::class, 'publicLlmsTxt']);
Route::get('/public/llms-full.txt', [SeoEngineController::class, 'publicLlmsFullTxt']);
Route::get('/public/redirects', [SeoEngineController::class, 'publicRedirects']);

// Public Page Settings
Route::get('/public/cms-settings', [PageSettingsController::class, 'publicCmsSettings']);
Route::get('/public/homepage', [PageSettingsController::class, 'publicHomepage']);
Route::get('/public/about', [PageSettingsController::class, 'publicAbout']);
Route::get('/public/site-settings', [PageSettingsController::class, 'publicSiteSettings']);
Route::get('/public/site-content', [PageSettingsController::class, 'publicSiteContent']);
Route::get('/public/service-panels', [PageSettingsController::class, 'publicServicePanels']);

// Public listing page content (projects, blog, services — hero + SEO)
Route::get('/public/listing/{page}', [PageSettingsController::class, 'publicListingPage'])
    ->where('page', 'projects|blog|services');

// Public LiveAvatar
Route::get('/public/liveavatar/embed', [LiveAvatarController::class, 'publicEmbed']);

// Public Demo Request
Route::post('/public/request-demo', [DemoRequestController::class, 'store']);

// Public Contact Form — emails the team via Mailtrap SMTP
Route::post('/public/contact', [ContactController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    // Dashboard
    Route::middleware('permission:dashboard.view')->get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::middleware('permission:dashboard.analytics')->get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

    // User Management
    Route::middleware('permission:users.view')->get('/users', [UserController::class, 'index']);
    Route::middleware('permission:users.create')->post('/users', [UserController::class, 'store']);
    Route::middleware('permission:users.view')->get('/users/{id}', [UserController::class, 'show']);
    Route::middleware('permission:users.edit')->put('/users/{id}', [UserController::class, 'update']);
    Route::middleware('permission:users.delete')->delete('/users/{id}', [UserController::class, 'destroy']);

    // Role Management
    Route::middleware('permission:roles.view')->get('/roles', [RoleController::class, 'index']);
    Route::middleware('permission:roles.create')->post('/roles', [RoleController::class, 'store']);
    Route::middleware('permission:roles.view')->get('/roles/{id}', [RoleController::class, 'show']);
    Route::middleware('permission:roles.edit')->put('/roles/{id}', [RoleController::class, 'update']);
    Route::middleware('permission:roles.delete')->delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Permissions
    Route::middleware('permission:roles.view')->get('/permissions', [PermissionController::class, 'index']);
    Route::middleware('permission:roles.view')->get('/permissions/groups', [PermissionController::class, 'groups']);

    // Pricing Management
    Route::middleware('permission:pricing.view')->get('/pricing/plans', [PricingController::class, 'plans']);
    Route::middleware('permission:pricing.view')->get('/pricing/plans/{id}', [PricingController::class, 'showPlan']);
    Route::middleware('permission:pricing.create')->post('/pricing/plans', [PricingController::class, 'storePlan']);
    Route::middleware('permission:pricing.edit')->put('/pricing/plans/{id}', [PricingController::class, 'updatePlan']);
    Route::middleware('permission:pricing.edit')->put('/pricing/plans-reorder', [PricingController::class, 'reorderPlans']);
    Route::middleware('permission:pricing.delete')->delete('/pricing/plans/{id}', [PricingController::class, 'destroyPlan']);
    Route::middleware('permission:pricing.edit')->post('/pricing/sync-to-square', [PricingController::class, 'syncToSquare']);

    Route::middleware('permission:pricing.view')->get('/pricing/faqs', [PricingController::class, 'faqs']);
    Route::middleware('permission:pricing.create')->post('/pricing/faqs', [PricingController::class, 'storeFaq']);
    Route::middleware('permission:pricing.edit')->put('/pricing/faqs/{id}', [PricingController::class, 'updateFaq']);
    Route::middleware('permission:pricing.delete')->delete('/pricing/faqs/{id}', [PricingController::class, 'destroyFaq']);

    Route::middleware('permission:pricing.view')->get('/pricing/settings', [PricingController::class, 'settings']);
    Route::middleware('permission:pricing.edit')->put('/pricing/settings', [PricingController::class, 'updateSettings']);

    Route::middleware('permission:pricing.view')->get('/pricing/category-sales', [PricingController::class, 'categorySales']);
    Route::middleware('permission:pricing.create')->post('/pricing/category-sales', [PricingController::class, 'storeCategorySale']);
    Route::middleware('permission:pricing.edit')->put('/pricing/category-sales/{id}', [PricingController::class, 'updateCategorySale']);
    Route::middleware('permission:pricing.delete')->delete('/pricing/category-sales/{id}', [PricingController::class, 'destroyCategorySale']);

    // Service Management
    Route::middleware('permission:services.view')->get('/services', [ServiceController::class, 'index']);
    Route::middleware('permission:services.view')->get('/services/{id}', [ServiceController::class, 'show']);
    Route::middleware('permission:services.create')->post('/services', [ServiceController::class, 'store']);
    Route::middleware('permission:services.edit')->put('/services/{id}', [ServiceController::class, 'update']);
    Route::middleware('permission:services.edit')->put('/services-reorder', [ServiceController::class, 'reorder']);
    Route::middleware('permission:services.delete')->delete('/services/{id}', [ServiceController::class, 'destroy']);

    // Features (standalone CRUD)
    Route::middleware('permission:features.view')->get('/features', [FeatureController::class, 'index']);
    Route::middleware('permission:features.view')->get('/features/{id}', [FeatureController::class, 'show']);
    Route::middleware('permission:features.create')->post('/features', [FeatureController::class, 'store']);
    Route::middleware('permission:features.edit')->put('/features/{id}', [FeatureController::class, 'update']);
    Route::middleware('permission:features.delete')->delete('/features/{id}', [FeatureController::class, 'destroy']);

    // Blog Management - Categories
    Route::middleware('permission:blog.view')->get('/blog/categories', [BlogController::class, 'categories']);
    Route::middleware('permission:blog.create')->post('/blog/categories', [BlogController::class, 'storeCategory']);
    Route::middleware('permission:blog.edit')->put('/blog/categories/{id}', [BlogController::class, 'updateCategory']);
    Route::middleware('permission:blog.delete')->delete('/blog/categories/{id}', [BlogController::class, 'destroyCategory']);

    // Blog Management - Tags
    Route::middleware('permission:blog.view')->get('/blog/tags', [BlogController::class, 'tags']);
    Route::middleware('permission:blog.create')->post('/blog/tags', [BlogController::class, 'storeTag']);
    Route::middleware('permission:blog.edit')->put('/blog/tags/{id}', [BlogController::class, 'updateTag']);
    Route::middleware('permission:blog.delete')->delete('/blog/tags/{id}', [BlogController::class, 'destroyTag']);

    // Blog Management - Posts
    Route::middleware('permission:blog.view')->get('/blog/posts', [BlogController::class, 'posts']);
    Route::middleware('permission:blog.view')->get('/blog/posts/{id}', [BlogController::class, 'showPost']);
    Route::middleware('permission:blog.create')->post('/blog/posts', [BlogController::class, 'storePost']);
    Route::middleware('permission:blog.edit')->put('/blog/posts/{id}', [BlogController::class, 'updatePost']);
    Route::middleware('permission:blog.delete')->delete('/blog/posts/{id}', [BlogController::class, 'destroyPost']);

    // Blog Management - Publish actions
    Route::middleware('permission:blog.publish')->put('/blog/posts/{id}/publish', [BlogController::class, 'publish']);
    Route::middleware('permission:blog.publish')->put('/blog/posts/{id}/unpublish', [BlogController::class, 'unpublish']);
    Route::middleware('permission:blog.publish')->put('/blog/posts/{id}/schedule', [BlogController::class, 'schedule']);

    // Projects Management
    Route::middleware('permission:projects.view')->get('/projects', [ProjectController::class, 'index']);
    Route::middleware('permission:projects.view')->get('/projects/{id}', [ProjectController::class, 'show']);
    Route::middleware('permission:projects.create')->post('/projects', [ProjectController::class, 'store']);
    Route::middleware('permission:projects.edit')->put('/projects/{id}', [ProjectController::class, 'update']);
    Route::middleware('permission:projects.delete')->delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::middleware('permission:projects.publish')->put('/projects/{id}/publish', [ProjectController::class, 'publish']);
    Route::middleware('permission:projects.publish')->put('/projects/{id}/unpublish', [ProjectController::class, 'unpublish']);

    // Page Settings Management
    Route::middleware('permission:pages.view')->get('/page-settings/homepage', [PageSettingsController::class, 'indexHomepage']);
    Route::middleware('permission:pages.edit')->put('/page-settings/homepage/reorder', [PageSettingsController::class, 'reorderHomepageSections']);
    Route::middleware('permission:pages.edit')->put('/page-settings/homepage/{section_key}', [PageSettingsController::class, 'updateHomepageSection']);

    Route::middleware('permission:settings.view')->get('/page-settings/site', [PageSettingsController::class, 'indexSiteSettings']);
    Route::middleware('permission:settings.edit')->put('/page-settings/site', [PageSettingsController::class, 'updateSiteSettings']);

    Route::middleware('permission:settings.view')->get('/page-settings/seo', [PageSettingsController::class, 'indexSeo']);
    Route::middleware('permission:settings.edit')->put('/page-settings/seo', [PageSettingsController::class, 'updateSeo']);

    Route::middleware('permission:pages.view')->get('/page-settings/service-panels', [PageSettingsController::class, 'indexServicePanels']);
    Route::middleware('permission:pages.edit')->post('/page-settings/service-panels', [PageSettingsController::class, 'storeServicePanel']);
    Route::middleware('permission:pages.edit')->put('/page-settings/service-panels/reorder', [PageSettingsController::class, 'reorderServicePanels']);
    Route::middleware('permission:pages.edit')->put('/page-settings/service-panels/{id}', [PageSettingsController::class, 'updateServicePanel']);
    Route::middleware('permission:pages.edit')->delete('/page-settings/service-panels/{id}', [PageSettingsController::class, 'destroyServicePanel']);

    Route::middleware('permission:pages.view')->get('/page-settings/about', [PageSettingsController::class, 'indexAbout']);
    Route::middleware('permission:pages.edit')->put('/page-settings/about/reorder', [PageSettingsController::class, 'reorderAboutSections']);
    Route::middleware('permission:pages.edit')->put('/page-settings/about/{section_key}', [PageSettingsController::class, 'updateAboutSection']);

    // Listing pages (projects, blog, services) — hero + (services-only) approach
    Route::middleware('permission:pages.view')->get('/page-settings/listing/{page}', [PageSettingsController::class, 'indexListingPage'])
        ->where('page', 'projects|blog|services');
    Route::middleware('permission:pages.edit')->put('/page-settings/listing/{page}/{section_key}', [PageSettingsController::class, 'updateListingSection'])
        ->where('page', 'projects|blog|services');

    Route::middleware('permission:settings.view')->get('/page-settings/cms', [PageSettingsController::class, 'indexCmsSettings']);
    Route::middleware('permission:settings.edit')->put('/page-settings/cms', [PageSettingsController::class, 'updateCmsSettings']);

    // SEO Engine
    Route::middleware('permission:seo.view')->get('/seo/audit', [SeoEngineController::class, 'audit']);
    Route::middleware('permission:seo.edit')->post('/seo/auto-generate', [SeoEngineController::class, 'autoGenerate']);
    Route::middleware('permission:seo.view')->post('/seo/auto-generate/preview', [SeoEngineController::class, 'autoGeneratePreview']);
    Route::middleware('permission:seo.view')->get('/seo/robots', [SeoEngineController::class, 'getRobotsTxt']);
    Route::middleware('permission:seo.edit')->put('/seo/robots', [SeoEngineController::class, 'updateRobotsTxt']);
    Route::middleware('permission:seo.view')->get('/seo/llms', [SeoEngineController::class, 'getLlmsTxt']);
    Route::middleware('permission:seo.edit')->put('/seo/llms', [SeoEngineController::class, 'updateLlmsTxt']);
    Route::middleware('permission:seo.view')->get('/seo/sitemap/config', [SeoEngineController::class, 'getSitemapConfig']);
    Route::middleware('permission:seo.edit')->put('/seo/sitemap/config', [SeoEngineController::class, 'updateSitemapConfig']);
    Route::middleware('permission:seo.view')->get('/seo/sitemap/preview', [SeoEngineController::class, 'sitemapPreview']);
    Route::middleware('permission:seo.view')->get('/seo/redirects', [SeoEngineController::class, 'redirects']);
    Route::middleware('permission:seo.edit')->post('/seo/redirects', [SeoEngineController::class, 'storeRedirect']);
    Route::middleware('permission:seo.edit')->put('/seo/redirects/{id}', [SeoEngineController::class, 'updateRedirect']);
    Route::middleware('permission:seo.delete')->delete('/seo/redirects/{id}', [SeoEngineController::class, 'destroyRedirect']);

    // LiveAvatar Management
    Route::middleware('permission:settings.view')->get('/liveavatar/context', [LiveAvatarController::class, 'getContext']);
    Route::middleware('permission:settings.view')->get('/liveavatar/context/preview', [LiveAvatarController::class, 'previewContext']);
    Route::middleware('permission:settings.edit')->put('/liveavatar/context', [LiveAvatarController::class, 'updateContext']);
    Route::middleware('permission:settings.edit')->post('/liveavatar/sync', [LiveAvatarController::class, 'syncContext']);
    Route::middleware('permission:settings.edit')->post('/liveavatar/embed/regenerate', [LiveAvatarController::class, 'regenerateEmbed']);
    Route::middleware('permission:settings.view')->get('/liveavatar/display', [LiveAvatarController::class, 'getDisplayConfig']);
    Route::middleware('permission:settings.edit')->put('/liveavatar/display', [LiveAvatarController::class, 'updateDisplayConfig']);

    // File Upload
    Route::middleware('permission:media.upload')->post('/upload', [UploadController::class, 'upload']);
    Route::middleware('permission:media.delete')->delete('/upload', [UploadController::class, 'destroy']);

    // Media Library
    Route::middleware('permission:media.view')->get('/media-stats', [MediaController::class, 'stats']);
    Route::middleware('permission:media.view')->get('/media', [MediaController::class, 'index']);
    Route::middleware('permission:media.view')->get('/media/{id}', [MediaController::class, 'show']);
    Route::middleware('permission:media.upload')->post('/media', [MediaController::class, 'store']);
    Route::middleware('permission:media.upload')->put('/media/{id}', [MediaController::class, 'update']);
    Route::middleware('permission:media.delete')->post('/media/bulk-delete', [MediaController::class, 'bulkDestroy']);
    Route::middleware('permission:media.delete')->delete('/media/{id}', [MediaController::class, 'destroy']);
});
