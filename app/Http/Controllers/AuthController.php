<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone_number' => 'required|string|min:7|max:20',
            'company_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request) {
            $company = Company::create([
                'company_name' => $request->company_name,
                'business_type' => $request->business_type,
                'phone' => $request->phone_number,
            ]);

            User::create([
                'company_id' => $company->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => $request->password,
                'role' => 'admin',
            ]);
        });

        return redirect('/')->with('success', 'Account created successfully. Please login.');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->status === 'blocked') {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been blocked. Contact support.'])->onlyInput('email');
            }

            if ($user->company && $user->company->status === 'blocked') {
                Auth::logout();
                return back()->withErrors(['email' => 'Your company has been disabled. Contact support.'])->onlyInput('email');
            }

            if ($user->isSuperAdmin()) {
                ActivityLogService::log('auth.login', "SuperAdmin '{$user->name}' logged in.", null, null);
                return redirect('/superadmin/dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
            }

            ActivityLogService::log('auth.login', "User '{$user->name}' logged in.", $user->company_id, $user->company?->company_name);
            return redirect()->intended('/dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            ActivityLogService::log('auth.logout', "User '{$user->name}' logged out.", $user->company_id, $user->company?->company_name);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
