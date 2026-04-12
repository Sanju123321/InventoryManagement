<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Skip status checks when a superadmin is actively impersonating
        if (session('impersonator_id')) {
            return $next($request);
        }

        if ($user->status === 'blocked') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->withErrors(['email' => 'Your account has been blocked.']);
        }

        if ($user->company && $user->company->status === 'blocked') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->withErrors(['email' => 'Your company has been blocked.']);
        }

        return $next($request);
    }
}
