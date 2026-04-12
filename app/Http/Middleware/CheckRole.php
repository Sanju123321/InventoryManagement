<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage: Route::middleware('check.role:admin,sales_admin')
     * Passes if the authenticated user's role is in the given list.
     * Company admins (role = 'admin') always pass.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/');
        }

        // Superadmins never hit company routes — kept for safety
        if ($user->isSuperAdmin()) {
            abort(403, 'Access denied.');
        }

        if ($user->hasRole($roles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return redirect('/dashboard')->with('error', 'You do not have permission to access that page.');
    }
}
