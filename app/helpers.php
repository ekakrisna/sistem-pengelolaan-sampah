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
