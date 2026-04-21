<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Fallback includes local dev origins + production domains so
    // the app still boots if CORS_ALLOWED_ORIGINS env var is unset.
    //   - http://localhost:5004        → CMS admin (Vite)
    //   - http://localhost:3000        → business site (Next.js dev)
    //   - http://localhost:3003        → secondary dev port
    //   - https://www.digidittos.com   → prod business site
    //   - https://digidittos.com       → prod apex
    //   - https://cms.digidittos.com   → prod CMS admin
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:5004,http://localhost:3000,http://localhost:3003,https://www.digidittos.com,https://digidittos.com,https://cms.digidittos.com'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
