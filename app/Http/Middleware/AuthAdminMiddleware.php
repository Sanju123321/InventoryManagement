<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        // Block superadmins from hitting company routes (they have their own prefix)
        // Exception: allow when impersonating a company user
        if (Auth::user()->isSuperAdmin()
            && ! str_starts_with($request->path(), 'superadmin')
            && ! str_starts_with($request->path(), 'impersonate')
            && ! session('impersonator_id')
        ) {
            return redirect('/superadmin/dashboard');
        }

        return $next($request);
    }
}
