@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
    <h1 class="mt-4">Activity Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filters</div>
        <div class="card-body">
            <form method="GET" action="{{ route('activity-log.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1 small">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        @foreach ($actions as $act)
                            <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1 small">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1 small">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i> Activity ({{ $logs->total() }} records)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:130px">Time</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th class="col-hide-mobile">User</th>
                            <th class="col-hide-mobile">Impersonated By</th>
                            <th class="col-hide-mobile">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap small text-muted">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                <td>
                                    <span class="badge {{ \App\Models\ActivityLog::actionBadgeClass($log->action) }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="small">{{ $log->description }}</td>
                                <td class="small text-nowrap">{{ $log->user_name ?? '—' }}</td>
                                <td class="small text-nowrap">
                                    @if ($log->impersonated_by_name)
                                        <span class="text-danger">{{ $log->impersonated_by_name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
