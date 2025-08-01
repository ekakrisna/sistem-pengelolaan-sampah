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
                'version' => getApiVersion(),
            ],
        ], $statusCode);
    }
}

if (!function_exists('successResponse')) {
    function successResponse(mixed $data = null, $detail = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'details' => $detail,
            'data' => $data,
            'metadata' => [
                'message' => $message,
                'version' => getApiVersion(),
            ],
        ]);
    }
}

if (!function_exists('getApiVersion')) {
    function getApiVersion(): string
    {
        return app('api.version');
    }
}
