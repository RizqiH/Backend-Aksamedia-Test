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
    ->withMiddleware(function (Middleware $middleware) {
        // Add CORS middleware globally untuk semua API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
            // Hapus EnsureFrontendRequestsAreStateful karena untuk token-based auth tidak perlu session
        ]);

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Production-friendly exception handling
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->wantsJson()) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthenticated',
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Forbidden',
                    ], 403);
                }

                // Generic error for production
                if (app()->environment('production')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Internal server error',
                    ], 500);
                }
            }
        });
    })
    ->create();
