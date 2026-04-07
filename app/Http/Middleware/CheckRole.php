<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if user is logged in
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 2. Check if the user's role is in the allowed list
        // Note: Assumes your User model has a 'role' column
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. Your role (' . $request->user()->role . ') does not have access to this resource.'
            ], 403);
        }

        return $next($request);
    }
}
