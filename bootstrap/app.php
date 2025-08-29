<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
                if ($e instanceof AuthenticationException) {
                    return errorResponse(
                        name: 'Error::Auth::Unauthenticated',
                        message: 'You are not authenticated.',
                        statusCode: Response::HTTP_UNAUTHORIZED
                    );
                }

                if ($e instanceof MethodNotAllowedHttpException) {
                    return errorResponse(
                        name: 'Error::RequestError::MethodNotAllowed',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_METHOD_NOT_ALLOWED
                    );
                }

                if ($e instanceof NotFoundHttpException) {
                    $prev = $e->getPrevious();

                    if ($prev instanceof ModelNotFoundException) {
                        $model = class_basename($prev->getModel());

                        return errorResponse(
                            name: 'Error::ModelNotFound',
                            message: "{$model} tidak ditemukan.",
                            statusCode: Response::HTTP_NOT_FOUND
                        );
                    }
                    return errorResponse(
                        name: 'Error::RequestError::NotFound',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_NOT_FOUND
                    );
                }

                if ($e instanceof ModelNotFoundException) {
                    return errorResponse(
                        name: 'Error::ModelNotFound',
                        message: class_basename($e->getModel()) . ' tidak ditemukan.',
                        statusCode: Response::HTTP_NOT_FOUND

                    );
                }

                if ($e instanceof ValidationException) {
                    return errorResponse(
                        name: 'Error::ValidationError',
                        message: $e->getMessage(),
                        errors: $e->errors(),
                        statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                if ($e instanceof \TypeError) {
                    if (Str::contains($e->getMessage(), 'must be of type int')) {
                        return errorResponse(
                            name: 'Error::InvalidParameterType',
                            message: 'The provided ID must be a valid integer.',
                            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }

                    return errorResponse(
                        name: 'Error::TypeError',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                if ($e instanceof \InvalidArgumentException) {
                    $prev = $e->getPrevious();

                    if ($prev instanceof ModelNotFoundException) {
                        $model = class_basename($prev->getModel());

                        return errorResponse(
                            name: 'Error::ModelNotFound',
                            message: "{$model} item could not be found.",
                            statusCode: Response::HTTP_NOT_FOUND
                        );
                    }

                    return errorResponse(
                        name: 'Error::ModelNotFound',
                        message: $e->getMessage(),
                        statusCode: Response::HTTP_NOT_FOUND

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
