<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $users = User::where('company_id', $companyId)
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();

        $roles = Role::whereIn('slug', ['admin', 'sales_admin', 'inventory_admin'])->get();

        return view('rbac.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::whereIn('slug', ['sales_admin', 'inventory_admin'])->get();
        return view('rbac.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => ['required', Rule::in(['sales_admin', 'inventory_admin'])],
        ]);

        User::create([
            'company_id' => $companyId,
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'status'     => 'active',
        ]);

        ActivityLogService::log('user.created', "User '{$validated['name']}' ({$validated['role']}) created.");

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorizeUser($user);
        $roles = Role::whereIn('slug', ['sales_admin', 'inventory_admin'])->get();
        return view('rbac.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUser($user);

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'   => ['required', Rule::in(['sales_admin', 'inventory_admin'])],
            'status' => ['required', Rule::in(['active', 'blocked'])],
        ]);

        $user->update([
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'role'   => $validated['role'],
            'status' => $validated['status'],
        ]);

        ActivityLogService::log('user.updated', "User '{$user->name}' updated (role: {$user->role}, status: {$user->status}).");

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeUser($user);

        // Prevent deleting the last admin
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot delete the company administrator account.');
        }

        ActivityLogService::log('user.deleted', "User '{$user->name}' ({$user->role}) removed.");
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User removed.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeUser($user);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        ActivityLogService::log('user.password_reset', "Password reset for user '{$user->name}'.");

        return back()->with('success', 'Password updated successfully.');
    }

    // ── Private helpers ────────────────────────────────────────

    private function authorizeUser(User $user): void
    {
        if ($user->company_id !== Auth::user()->company_id || $user->id === Auth::id()) {
            abort(403);
        }
    }
}
