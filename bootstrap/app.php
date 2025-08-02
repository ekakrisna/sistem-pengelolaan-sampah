<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\EnsureValidApiVersion::class,
        ], prepend: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return errorResponse(
                        name: 'Error::Auth::Unauthenticated',
                        message: 'You are not authenticated or the token is invalid.',
                        statusCode: Response::HTTP_UNAUTHORIZED
                    );
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return errorResponse(
                        name: 'Error::RequestError::MethodNotAllowed',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_METHOD_NOT_ALLOWED
                    );
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return errorResponse(
                        name: 'Error::RequestError::NotFound',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_NOT_FOUND
                    );
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return errorResponse(
                        name: 'Error::ValidationError',
                        message: $e->getMessage(),
                        errors: $e->errors(),
                        statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                return errorResponse(
                    name: 'Error::InternalServerError',
                    message: $e->getMessage(),
                    statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        });
    })->create();
