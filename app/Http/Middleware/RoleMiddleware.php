<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage:
     *   ->middleware('role:admin')
     *   ->middleware('role:customer,super_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not authenticated -> 401
        if (!$user) {
            return errorResponse(
                name: 'Error::Auth::Unauthenticated',
                message: 'You must be authenticated to access this resource.',
                statusCode: Response::HTTP_UNAUTHORIZED
            );
        }

        // If no roles provided, deny by default (safer)
        if (empty($roles)) {
            return errorResponse(
                name: 'Error::Forbidden',
                message: 'No role is allowed for this route.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        // Case-insensitive compare just in case
        $allowed = array_map(fn($r) => strtolower(trim($r)), $roles);
        $userRole = strtolower((string) $user->role);

        if (!in_array($userRole, $allowed, true)) {
            return errorResponse(
                name: 'Error::Forbidden',
                message: 'You are not authorized to access this resource.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
