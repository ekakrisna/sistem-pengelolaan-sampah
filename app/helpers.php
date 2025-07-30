<?php

use Illuminate\Http\JsonResponse;

if (!function_exists('errorResponse')) {
    function errorResponse(string $name, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => true,
            'details' => [
                'name' => $name,
                'message' => $message,
            ],
            'metadata' => [
                'message' => null,
            ],
        ], $status);
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
            ],
        ]);
    }
}

if (!function_exists('getApiVersion')) {
    function getApiVersion(): string
    {
        return app('api.version') ?? 'v1';
    }
}
