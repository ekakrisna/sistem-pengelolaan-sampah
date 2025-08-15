<?php

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

if (!function_exists('normalizeXenditException')) {
    function normalizeXenditException(\Throwable $th): array
    {
        $code = (is_int($th->getCode()) && $th->getCode() >= 400 && $th->getCode() <= 599)
            ? $th->getCode()
            : 500;


        $name = 'Error::InternalServerError';
        $message = $th->getMessage();

        $json = json_decode($th->getMessage(), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $name = data_get($json, 'details.error_code') ?? data_get($json, 'error') ?? $name;
            $message = data_get($json, 'details.message') ?? data_get($json, 'message') ?? $message;
        }


        return [$name, $message, $code];
    }
}
