<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ─── Serve uploaded media directly from storage/app/public ────
// PHP's built-in server (`php -S`) on Render doesn't reliably
// follow the `public/storage` symlink into the persistent disk
// mount, so `/storage/foo.png` requests fall through to Laravel
// and get routed to `/` (or 404). Handling them here, before
// Laravel boots, avoids the whole mess — faster too, since we
// skip the framework bootstrap for every static asset request.
if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/storage/')) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $rel = urldecode(substr($path, strlen('/storage/')));

    // Block path traversal — no `..` segments, no leading slash.
    $traversalSafe = $rel !== ''
        && !str_contains($rel, '..')
        && !str_starts_with($rel, '/')
        && !str_starts_with($rel, '\\');

    if ($traversalSafe) {
        // Resolve `storage/app/public` to an absolute path ONCE at the
        // app root (one level up from /public) instead of relying on
        // `../` traversal through the request's filesystem position.
        // When the OS mounts a persistent disk at the storage path,
        // relative-path traversal can cross a mount boundary that
        // PHP's `is_file()` handles inconsistently. Absolute paths
        // resolve the target directly and avoid the boundary dance.
        $appRoot = dirname(__DIR__);
        $base = $appRoot . '/storage/app/public/';
        $file = $base . $rel;
        clearstatcache(true, $file);

        if (is_file($file) && is_readable($file)) {
            $mimes = [
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'avif' => 'image/avif',
                'mp4'  => 'video/mp4',
                'webm' => 'video/webm',
                'ogg'  => 'video/ogg',
                'mov'  => 'video/quicktime',
                'pdf'  => 'application/pdf',
            ];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime = $mimes[$ext]
                ?? (function_exists('mime_content_type') ? (mime_content_type($file) ?: 'application/octet-stream') : 'application/octet-stream');

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: public, max-age=31536000, immutable');
            header('X-Served-By: index.php-static');
            readfile($file);
            exit;
        }

        // Debug header — visible in response headers when the file lookup
        // fails, so we can tell whether our interceptor ran and why it
        // bailed. Remove once storage serving is verified stable.
        header('X-Storage-Miss: ' . (is_file($file) ? 'not-readable' : 'not-found'));
        header('X-Storage-Path: ' . $file);
    }
    // File not found / unreadable → fall through so Laravel returns 404.
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
