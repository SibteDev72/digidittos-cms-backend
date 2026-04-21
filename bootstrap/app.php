<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Scheduled tasks are registered in routes/console.php
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (and most PaaS) sit behind a reverse proxy that terminates
        // TLS. Without this, Laravel sees HTTP, builds mismatched URLs, and
        // any code that checks `$request->secure()` returns false — which
        // breaks session cookies marked secure and any `url()->secure()` calls.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON errors for API requests (and anything that asked for
        // JSON) — avoids rendering the generic Symfony "500 SERVER ERROR"
        // HTML page and gives the caller an actionable payload. The
        // `message` field is only exposed when APP_DEBUG=true; in production
        // we return a generic string so we don't leak internals.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $payload = ['message' => 'Server Error'];

                if (config('app.debug')) {
                    $payload['message'] = $e->getMessage();
                    $payload['exception'] = get_class($e);
                    $payload['file'] = $e->getFile() . ':' . $e->getLine();
                }

                return response()->json($payload, $status >= 400 && $status < 600 ? $status : 500);
            }

            return null; // fall through to default renderer for web routes
        });
    })->create();
