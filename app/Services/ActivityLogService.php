<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Record an activity.
     *
     * @param  string       $action       Dot-notation action key, e.g. 'product.created'
     * @param  string       $description  Human-readable sentence shown in the UI
     * @param  int|null     $companyId    Override (defaults to authenticated user's company)
     * @param  string|null  $companyName  Override (defaults to authenticated user's company name)
     */
    public static function log(
        string  $action,
        string  $description,
        ?int    $companyId   = null,
        ?string $companyName = null
    ): void {
        try {
            $user           = Auth::user();
            $impersonatorId = session('impersonator_id');

            // Resolve company from context
            $resolvedCompanyId   = $companyId   ?? $user?->company_id;
            $resolvedCompanyName = $companyName ?? $user?->company?->company_name;

            // Impersonator details
            $impersonatedByName = null;
            if ($impersonatorId) {
                $impersonator = \App\Models\User::find($impersonatorId);
                $impersonatedByName = $impersonator?->name;
            }

            ActivityLog::create([
                'user_id'              => $user?->id,
                'user_name'            => $user?->name ?? 'System',
                'company_id'           => $resolvedCompanyId,
                'company_name'         => $resolvedCompanyName,
                'impersonated_by_id'   => $impersonatorId ?: null,
                'impersonated_by_name' => $impersonatedByName,
                'action'               => $action,
                'description'          => $description,
                'ip_address'           => Request::ip(),
                'created_at'           => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let logging break the main request
            \Illuminate\Support\Facades\Log::error('ActivityLog failed: ' . $e->getMessage());
        }
    }
}
