<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies === ['*'] ? '*' : $trustedProxies);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->report(function (\Throwable $e) {
            if (
                $e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Auth\AuthenticationException ||
                ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() < 500)
            ) {
                return;
            }

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('activity_logs')) {
                    $req = request();
                    $user = $req?->user();
                    \App\Models\ActivityLog::record(
                        $user?->business_id,
                        $user?->id,
                        'system_exception',
                        'Exception',
                        null,
                        [
                            'exception' => class_basename($e),
                            'message' => mb_substr($e->getMessage(), 0, 260),
                            'file' => basename($e->getFile()) . ':' . $e->getLine(),
                            'method' => $req?->method(),
                            'url' => $req?->path(),
                        ],
                        $req?->ip()
                    );
                }
            } catch (\Throwable $ignored) {
            }
        });
    })->create();
