<?php

use Illuminate\Support\Facades\Route;

/*
 * DigiDittos CMS is an API-only backend — the business site +
 * CMS admin live in separate Next.js / React apps. We expose a
 * tiny JSON health endpoint at `/` so:
 *   - Render's health checks get a 200 instead of a Blade render
 *   - You can hit the root URL to confirm the container is alive
 *   - No view cache / named-route lookups happen at boot
 */
Route::get('/', function () {
    return response()->json([
        'service' => 'digidittos-cms-backend',
        'status' => 'ok',
        'env' => app()->environment(),
        'docs' => '/api/public/site-settings',
    ]);
});

/*
 * Serve uploaded media files via Laravel instead of relying on
 * `public/storage` being a working symlink. `php -S` on Render
 * doesn't always follow symlinks that point into the persistent
 * disk mount, so a direct file response is the reliable path.
 *
 * Matches `/storage/<anything including slashes>` and streams the
 * file straight from storage/app/public/. Uses inline disposition
 * and long cache headers so browsers treat these like real assets.
 */
Route::get('/storage/{path}', function (string $path) {
    $base = storage_path('app/public');
    $real = realpath($base . DIRECTORY_SEPARATOR . $path);

    // Guard against path traversal — `realpath` canonicalises
    // `../` segments; ensure the result still lives under $base.
    if (! $real || ! str_starts_with($real, realpath($base) . DIRECTORY_SEPARATOR) || ! is_file($real)) {
        abort(404);
    }

    return response()->file($real, [
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*');
