<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ─── Serve uploaded media directly from storage/app/public ────
// PHP's built-in server (`php -S`) on Render doesn't reliably
// follow the `public/storage` symlink into the persistent disk
// mount, so `/storage/foo.png` requests fall through to Laravel
// and get routed to `/`. Handling them here, before Laravel
// boots, avoids the whole mess — faster too, since we skip the
// framework bootstrap for every static asset request.
if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/storage/')) {
    $rel = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen('/storage/'));
    $rel = urldecode($rel);

    // Block path traversal — no leading slash, no `..` segments.
    if ($rel !== '' && !str_contains($rel, '..') && !str_starts_with($rel, '/')) {
        $base = realpath(__DIR__ . '/../storage/app/public');
        $file = $base ? realpath($base . '/' . $rel) : false;

        if ($file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) {
            $mimes = [
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'mp4'  => 'video/mp4',
                'webm' => 'video/webm',
                'ogg'  => 'video/ogg',
                'mov'  => 'video/quicktime',
                'pdf'  => 'application/pdf',
            ];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime = $mimes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream');

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: public, max-age=31536000, immutable');
            header('X-Served-By: index.php-static');
            readfile($file);
            exit;
        }
    }
    // File not found — fall through so Laravel can return a 404.
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
