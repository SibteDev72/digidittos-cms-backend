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
