<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse($data = null, $detail = null, $message = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'details' => $detail,
            'data' => $data,
            'metadata' => [
                'message' => $message,
            ],
        ]);
    }

    protected function errorResponse(string $name, string $message, array $errors = [], int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'error' => true,
            'details' => [
                'name' => $name,
                'message' => $message,
                'errors' => $errors,
            ],
            'metadata' => [
                'message' => null,
            ],
        ], $statusCode);
    }
}
