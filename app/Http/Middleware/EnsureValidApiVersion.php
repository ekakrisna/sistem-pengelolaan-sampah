<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->route('version');

        $allowedVersions = explode(',', env('API_ALLOWED_VERSIONS', 'v1'));

        if (!in_array($version, $allowedVersions)) {
            return response()->json([
                'error' => true,
                'details' => [
                    'name' => 'Error::RequestError::InvalidApiVersion',
                    'message' => "Unsupported API version: $version",
                ],
                'metadata' => ['message' => null],
            ], 400);
        }

        return $next($request);
    }
}
