<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\EnsureValidApiVersion::class,
        ], prepend: [
            \App\Http\Middleware\ApiVersionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json([
                        'error' => true,
                        'details' => [
                            'name' => 'Error::RequestError::MethodNotAllowed',
                            'message' => $e->getMessage(),
                        ],
                        'metadata' => [
                            'message' => null
                        ]
                    ], 405);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'error' => true,
                        'details' => [
                            'name' => 'Error::RequestError::NotFound',
                            'message' => $e->getMessage(),
                        ],
                        'metadata' => [
                            'message' => null
                        ]
                    ], 404);
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'error' => true,
                        'details' => [
                            'name' => 'Error::ValidationError',
                            'message' => $e->getMessage(),
                            'errors' => $e->errors(),
                        ],
                        'metadata' => [
                            'message' => null
                        ]
                    ], 422);
                }

                // Tambahan: default handler untuk error lainnya
                return response()->json([
                    'error' => true,
                    'details' => [
                        'name' => 'Error::InternalServerError',
                        'message' => $e->getMessage(),
                    ],
                    'metadata' => [
                        'message' => null
                    ]
                ], 500);
            }
        });
    })->create();
