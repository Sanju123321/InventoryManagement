<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalCompanies = Company::count();
        $totalUsers = User::where('role', 'admin')->count();
        $activeCompanies = Company::where('status', 'active')->count();
        $blockedCompanies = Company::where('status', 'blocked')->count();
        $totalProducts = Product::count();
        $totalMaterials = RawMaterial::count();
        $recentCompanies = Company::withCount('users')->latest()->take(5)->get();

        return view('superadmin.dashboard', compact(
            'totalCompanies',
            'totalUsers',
            'activeCompanies',
            'blockedCompanies',
            'totalProducts',
            'totalMaterials',
            'recentCompanies'
        ));
    }

    public function companies()
    {
        $companies = Company::withCount('users')->latest()->paginate(15);
        return view('superadmin.companies', compact('companies'));
    }

    public function companyCreate()
    {
        return view('superadmin.company-create');
    }

    public function companyStore(Request $request)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255|unique:companies,company_name',
            'business_type'  => 'nullable|string|max:255',
            'phone'          => 'nullable|string|min:7|max:20',
            'status'         => 'required|in:active,blocked',
        ]);

        Company::create($request->only('company_name', 'business_type', 'phone', 'status'));

        ActivityLogService::log('company.created', "Company '{$request->company_name}' created.", null, null);

        return redirect()->route('superadmin.companies')->with('success', "Company '{$request->company_name}' created successfully.");
    }

    public function companyEdit(Company $company)
    {
        return view('superadmin.company-edit', compact('company'));
    }

    public function companyUpdate(Request $request, Company $company)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255|unique:companies,company_name,' . $company->id,
            'business_type'  => 'nullable|string|max:255',
            'phone'          => 'nullable|string|min:7|max:20',
            'status'         => 'required|in:active,blocked',
        ]);

        $company->update($request->only('company_name', 'business_type', 'phone', 'status'));

        ActivityLogService::log('company.updated', "Company '{$company->company_name}' updated.", null, null);

        return redirect()->route('superadmin.companies')->with('success', "Company '{$company->company_name}' updated successfully.");
    }

    public function companyDestroy(Company $company)
    {
        $name = $company->company_name;
        $company->delete(); // cascades to all related data via DB constraints

        ActivityLogService::log('company.deleted', "Company '{$name}' and all its data deleted.", null, null);

        return redirect()->route('superadmin.companies')->with('success', "Company '{$name}' and all its data have been deleted.");
    }

    public function toggleCompanyStatus(Company $company)
    {
        $company->update([
            'status' => $company->status === 'active' ? 'blocked' : 'active',
        ]);

        ActivityLogService::log('company.status_changed', "Company '{$company->company_name}' status changed to {$company->status}.", null, null);

        return back()->with('success', "Company '{$company->company_name}' has been " . $company->status . ".");
    }

    public function users()
    {
        $users = User::where('role', '!=', 'superadmin')->with('company')->latest()->paginate(15);
        return view('superadmin.users', compact('users'));
    }

    public function userCreate()
    {
        $companies = Company::where('status', 'active')->orderBy('company_name')->get();
        return view('superadmin.user-create', compact('companies'));
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email',
            'phone_number' => 'nullable|string|min:7|max:20',
            'company_id'   => 'required|exists:companies,id',
            'role'         => 'required|in:admin,inventory_admin,sales_admin',
            'password'     => 'required|string|min:8|confirmed',
            'status'       => 'required|in:active,blocked',
        ]);

        $company = Company::findOrFail($request->company_id);

        User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'company_id'   => $request->company_id,
            'role'         => $request->role,
            'password'     => $request->password,
            'status'       => $request->status,
        ]);

        ActivityLogService::log('user.created', "SuperAdmin created user '{$request->name}' ({$request->role}) for company '{$company->company_name}'.", null, null);

        return redirect()->route('superadmin.users')->with('success', "User '{$request->name}' created successfully.");
    }

    public function userEdit(User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Cannot edit superadmin.');
        }
        $companies = Company::where('status', 'active')->orderBy('company_name')->get();
        return view('superadmin.user-edit', compact('user', 'companies'));
    }

    public function userUpdate(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Cannot edit superadmin.');
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|min:7|max:20',
            'company_id'   => 'required|exists:companies,id',
            'role'         => 'required|in:admin,inventory_admin,sales_admin',
            'status'       => 'required|in:active,blocked',
        ]);

        $user->update($request->only('name', 'email', 'phone_number', 'company_id', 'role', 'status'));

        ActivityLogService::log('user.updated', "SuperAdmin updated user '{$user->name}' ({$user->role}).", null, null);

        return redirect()->route('superadmin.users')->with('success', "User '{$user->name}' updated successfully.");
    }

    public function userDestroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Cannot delete superadmin.');
        }
        $name = $user->name;
        $user->delete();

        ActivityLogService::log('user.deleted', "SuperAdmin deleted user '{$name}'.", null, null);

        return redirect()->route('superadmin.users')->with('success', "User '{$name}' has been deleted.");
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Cannot modify superadmin status.');
        }

        $user->update([
            'status' => $user->status === 'active' ? 'blocked' : 'active',
        ]);

        ActivityLogService::log('user.status_changed', "SuperAdmin changed user '{$user->name}' status to {$user->status}.", null, null);

        return back()->with('success', "User '{$user->name}' has been " . $user->status . ".");
    }

    public function companyDetail(Company $company)
    {
        $company->load('users');
        $products = Product::where('company_id', $company->id)->count();
        $materials = RawMaterial::where('company_id', $company->id)->count();
        $productions = ProductionLog::where('company_id', $company->id)->count();

        return view('superadmin.company-detail', compact('company', 'products', 'materials', 'productions'));
    }
}
