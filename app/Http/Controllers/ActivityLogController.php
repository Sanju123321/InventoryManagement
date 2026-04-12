<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Company-scoped activity log (admin only).
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = ActivityLog::where('company_id', $companyId)->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(50)->appends($request->query());

        $actions = ActivityLog::where('company_id', $companyId)
            ->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activity-log.index', compact('logs', 'actions'));
    }

    /**
     * SuperAdmin-wide activity log.
     */
    public function superadminIndex(Request $request)
    {
        $query = ActivityLog::latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(100)->appends($request->query());

        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $companies = \App\Models\Company::orderBy('company_name')->get(['id', 'company_name']);

        return view('superadmin.activity-log', compact('logs', 'actions', 'companies'));
    }
}
