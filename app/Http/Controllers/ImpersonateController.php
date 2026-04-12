<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a company's admin.
     * Only accessible to superadmins (enforced by route middleware).
     */
    public function start(Company $company)
    {
        // Find the admin user for this company (prefer 'admin' role, fall back to any company user)
        $target = $company->users()->where('role', 'admin')->first()
            ?? $company->users()->first();

        if (!$target) {
            return back()->with('error', "No users found for company '{$company->company_name}'.");
        }

        // Store the real superadmin's ID so we can restore later
        session(['impersonator_id' => Auth::id()]);

        ActivityLogService::log('impersonate.start', "SuperAdmin started impersonating '{$target->name}' of '{$company->company_name}'.", null, null);

        Auth::loginUsingId($target->id);

        return redirect('/dashboard')->with('info', "You are now impersonating {$target->name} ({$company->company_name}). Click 'Stop Impersonating' to return.");
    }

    /**
     * Stop impersonation and restore the original superadmin session.
     */
    public function stop(Request $request)
    {
        $originalId = session('impersonator_id');

        if (!$originalId) {
            return redirect('/dashboard');
        }

        Auth::loginUsingId($originalId);
        $request->session()->forget('impersonator_id');

        ActivityLogService::log('impersonate.stop', 'SuperAdmin stopped impersonation and restored session.', null, null);

        return redirect('/superadmin/dashboard')->with('success', 'Impersonation ended. You are back as Super Admin.');
    }
}
