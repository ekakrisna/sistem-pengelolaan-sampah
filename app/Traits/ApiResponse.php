<?php

namespace App\Traits;

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

trait ApiResponse
{
    protected function successResponse($data = null, $message = null): JsonResponse
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

    protected function errorResponse(
        string $name,
        string $message,
        array $errors = [],
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
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

    protected function normalizeException(\Throwable $th): array
    {
        $code = ($th instanceof HttpExceptionInterface)
            ? $th->getStatusCode()
            : (is_int($th->getCode()) && $th->getCode() >= 400 && $th->getCode() <= 599 ? $th->getCode() : 500);

        $name    = 'Error::InternalServerError';
        $message = $th->getMessage();
        $errors  = [];

        if ($th instanceof ValidationException) {
            $code    = Response::HTTP_UNPROCESSABLE_ENTITY;
            $name    = 'Error::ValidationError';
            $message = $th->getMessage() ?: 'Validation failed.';
            $errors  = $th->errors();
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof AuthenticationException) {
            $code    = Response::HTTP_UNAUTHORIZED;
            $name    = 'Error::Auth::Unauthenticated';
            $message = 'You are not authenticated or the token is invalid.';
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof AuthorizationException) {
            $code    = Response::HTTP_FORBIDDEN;
            $name    = 'Error::Forbidden';
            $message = $th->getMessage() ?: 'You are not authorized to perform this action.';
            return [$name, $message, $code, $errors];
        }

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

        if ($th instanceof MethodNotAllowedHttpException) {
            $code    = Response::HTTP_METHOD_NOT_ALLOWED;
            $name    = 'Error::RequestError::MethodNotAllowed';
            $message = $th->getMessage() ?: 'Method Not Allowed.';
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof \TypeError) {
            if (str_contains($th->getMessage(), 'must be of type int')) {
                $code    = Response::HTTP_UNPROCESSABLE_ENTITY;
                $name    = 'Error::InvalidParameterType';
                $message = 'The provided ID must be a valid integer.';
                return [$name, $message, $code, $errors];
            }
            $code    = Response::HTTP_INTERNAL_SERVER_ERROR;
            $name    = 'Error::TypeError';
            $message = $th->getMessage();
            return [$name, $message, $code, $errors];
        }

        if ($th instanceof QueryException) {
            $sqlState = $th->errorInfo[0] ?? null;
            $driverCode = (int)($th->errorInfo[1] ?? 0);
            $dbMsg = $th->errorInfo[2] ?? $th->getMessage();

            if ($sqlState === '23000' && $driverCode === 1062) {
                $code    = Response::HTTP_CONFLICT;
                $name    = 'Error::Duplicate';
                $message = 'Duplicate entry detected.';
                $errors  = ['db_message' => $dbMsg];
                return [$name, $message, $code, $errors];
            }

            if ($sqlState === '23000' && in_array($driverCode, [1451, 1452], true)) {
                $code    = Response::HTTP_UNPROCESSABLE_ENTITY;
                $name    = 'Error::ForeignKeyConstraint';
                $message = 'Foreign key constraint violation.';
                $errors  = ['db_message' => $dbMsg];
                return [$name, $message, $code, $errors];
            }

            $code    = $code >= 400 && $code <= 599 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
            $name    = 'Error::Database';
            $message = $dbMsg;
            return [$name, $message, $code, $errors];
        }

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

            $jsonCode = (int) (data_get($json, 'status') ?? 0);
            if ($jsonCode >= 400 && $jsonCode <= 599) {
                $code = $jsonCode;
            }

            return [$name, $message, $code, $errors];
        }

        $code    = $code >= 400 && $code <= 599 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
        $name    = 'Error::InternalServerError';
        $message = $message ?: 'Internal server error.';
        return [$name, $message, $code, $errors];
    }
}
