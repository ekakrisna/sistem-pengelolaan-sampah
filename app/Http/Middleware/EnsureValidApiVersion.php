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
            return errorResponse(
                'Error::RequestError::InvalidApiVersion',
                "Unsupported API version: $version",
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        return $next($request);
    }
}
