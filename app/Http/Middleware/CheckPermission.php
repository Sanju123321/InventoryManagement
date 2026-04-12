<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage: Route::middleware('check.permission:manage_sales')
     * Passes if the authenticated user holds a role that has the given permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/');
        }

        if ($user->hasPermission($permission) || $user->hasPermission('full_access')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return redirect('/dashboard')->with('error', 'You do not have permission to access that section.');
    }
}
