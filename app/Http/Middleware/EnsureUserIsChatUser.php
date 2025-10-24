<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsChatUser
{
    /**
     * Handle an incoming request.
     *
     * Ensures user is either admin or chat_user (NOT view_only).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->isAdmin() && !$user->isChatUser())) {
            abort(403, 'Access denied. This feature requires chat user or admin privileges.');
        }

        return $next($request);
    }
}
