<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (!function_exists('errorResponse')) {
    function errorResponse(string $name, string $message, array $errors = [], int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'error' => true,
            'details' => [
                'name' => $name,
                'message' => $message,
                'errors' => $errors,
            ],
            'metadata' => [
                'version' => config('app.api.version'),
            ],
        ], $statusCode);
    }
}

if (!function_exists('successResponse')) {
    function successResponse(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'data' => $data,
            'metadata' => [
                'message' => $message,
                'version' => config('app.api.version'),
            ],
        ]);
    }
}

if (!function_exists('normalizeException')) {
    function normalizeException(\Throwable $th): array
    {
        // 1) Status code awal: pakai HttpExceptionInterface kalau ada
        $code = ($th instanceof HttpExceptionInterface)
            ? $th->getStatusCode()
            : (is_int($th->getCode()) && $th->getCode() >= 400 && $th->getCode() <= 599 ? $th->getCode() : 500);

        $name    = 'Error::InternalServerError';
        $message = $th->getMessage();
        $errors  = [];

        // 2) Spesifik: Validation
        if ($th instanceof ValidationException) {
            $code    = Response::HTTP_UNPROCESSABLE_ENTITY; // 422
            $name    = 'Error::ValidationError';
            $message = $th->getMessage() ?: 'Validation failed.';
            $errors  = $th->errors();
            return [$name, $message, $code, $errors];
        }

        // 3) Auth / Policy
        if ($th instanceof AuthenticationException) {
            $code    = Response::HTTP_UNAUTHORIZED; // 401
            $name    = 'Error::Auth::Unauthenticated';
            $message = 'You are not authenticated or the token is invalid.';
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof AuthorizationException) {
            $code    = Response::HTTP_FORBIDDEN; // 403
            $name    = 'Error::Forbidden';
            $message = $th->getMessage() ?: 'You are not authorized to perform this action.';
            return [$name, $message, $code, $errors];
        }

        // 4) Not Found & ModelNotFound (termasuk previous dari route model binding)
        if ($th instanceof NotFoundHttpException) {
            $prev = $th->getPrevious();
            if ($prev instanceof ModelNotFoundException) {
                $model   = class_basename($prev->getModel());
                $code    = Response::HTTP_NOT_FOUND;
                $name    = 'Error::ModelNotFound';
                $message = "Data not found.";
                return [$name, $message, $code, $errors];
            }

            $code    = Response::HTTP_NOT_FOUND;
            $name    = 'Error::RequestError::NotFound';
            $message = $th->getMessage() ?: 'Route not found.';
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof ModelNotFoundException) {
            $model   = class_basename($th->getModel());
            $code    = Response::HTTP_NOT_FOUND;
            $name    = 'Error::ModelNotFound';
            $message = "Data not found.";
            return [$name, $message, $code, $errors];
        }

        // 5) Method not allowed
        if ($th instanceof MethodNotAllowedHttpException) {
            $code    = Response::HTTP_METHOD_NOT_ALLOWED; // 405
            $name    = 'Error::RequestError::MethodNotAllowed';
            $message = $th->getMessage() ?: 'Method Not Allowed.';
            return [$name, $message, $code, $errors];
        }

        // 6) TypeError umum (parameter ID bukan integer, dsb.)
        if ($th instanceof \TypeError) {
            if (str_contains($th->getMessage(), 'must be of type int')) {
                $code    = Response::HTTP_UNPROCESSABLE_ENTITY; // 422
                $name    = 'Error::InvalidParameterType';
                $message = 'The provided ID must be a valid integer.';
                return [$name, $message, $code, $errors];
            }
            $code    = Response::HTTP_INTERNAL_SERVER_ERROR;
            $name    = 'Error::TypeError';
            $message = $th->getMessage();
            return [$name, $message, $code, $errors];
        }

        // 7) Query/DB: duplicate & foreign key (kasih pesan lebih ramah)
        if ($th instanceof QueryException) {
            $sqlState = $th->errorInfo[0] ?? null;
            $driverCode = (int)($th->errorInfo[1] ?? 0);
            $dbMsg = $th->errorInfo[2] ?? $th->getMessage();

            // MySQL duplicate entry: 23000 / 1062
            if ($sqlState === '23000' && $driverCode === 1062) {
                $code    = Response::HTTP_CONFLICT; // 409
                $name    = 'Error::Duplicate';
                $message = 'Duplicate entry detected.';
                $errors  = ['db_message' => $dbMsg];
                return [$name, $message, $code, $errors];
            }

            // MySQL foreign key constraint: 23000 / 1452 or 1451
            if ($sqlState === '23000' && in_array($driverCode, [1451, 1452], true)) {
                $code    = Response::HTTP_UNPROCESSABLE_ENTITY; // 422
                $name    = 'Error::ForeignKeyConstraint';
                $message = 'Foreign key constraint violation.';
                $errors  = ['db_message' => $dbMsg];
                return [$name, $message, $code, $errors];
            }

            // Fallback QueryException
            $code    = $code >= 400 && $code <= 599 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
            $name    = 'Error::Database';
            $message = $dbMsg;
            return [$name, $message, $code, $errors];
        }

        // 8) Jika message mengandung JSON (mis. dari API Xendit), ekstrak dulu
        $json = json_decode($th->getMessage(), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $name    = data_get($json, 'details.error_code')
                ?? data_get($json, 'error')
                ?? data_get($json, 'name')
                ?? $name;

            $message = data_get($json, 'details.message')
                ?? data_get($json, 'message')
                ?? $message;

            $errors  = data_get($json, 'details.errors')
                ?? data_get($json, 'errors')
                ?? $errors;

            // Status dari JSON kalau ada
            $jsonCode = (int) (data_get($json, 'status') ?? 0);
            if ($jsonCode >= 400 && $jsonCode <= 599) {
                $code = $jsonCode;
            }

            return [$name, $message, $code, $errors];
        }

        // 9) Fallback umum
        $code    = $code >= 400 && $code <= 599 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
        $name    = 'Error::InternalServerError';
        $message = $message ?: 'Internal server error.';
        return [$name, $message, $code, $errors];
    }
}
