@extends('layouts.app')

@section('title', 'Leave Applications')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-4 mb-3">
        <div>
            <h1 class="h3 mb-1">Leave Applications</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/admin/attendance') }}">Attendance</a></li>
                <li class="breadcrumb-item active">Leave</li>
            </ol>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/admin/leaves/export') . '?' . http_build_query(array_filter(['q' => request('q'), 'month' => $month, 'status' => request('status')])) }}"
                class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export
            </a>
            <a href="{{ url('/admin/attendance') }}" class="btn btn-outline-secondary">
                <i class="fas fa-user-check me-1"></i> Attendance register
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filters</div>
        <div class="card-body">
            <form method="GET" action="{{ url('/admin/leaves') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-lg-4">
                    <label class="form-label small text-muted mb-1">Search employee</label>
                    <input type="search" name="q" class="form-control form-control-sm"
                        value="{{ request('q') }}" placeholder="Employee name">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach (['pending', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                    <a href="{{ url('/admin/leaves') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                <i class="fas fa-calendar-check me-1"></i> Applications
                @if ($pendingCount > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
                @endif
            </span>
            <span class="small text-muted">{{ $leaves->total() }} record{{ $leaves->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaves as $leave)
                            <tr>
                                <td>
                                    <strong>{{ $leave->user->name ?? '—' }}</strong>
                                    <div class="small text-muted">{{ $leave->user?->roleLabel() }}</div>
                                </td>
                                <td>
                                    {{ $leave->typeLabel() }}
                                    @if ($leave->timeDetail() !== '')
                                        <div class="small text-muted">{{ $leave->timeDetail() }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $leave->from_date->format('d-m-Y') }}</td>
                                <td class="text-nowrap">{{ $leave->to_date->format('d-m-Y') }}</td>
                                <td class="text-nowrap">{{ $leave->durationLabel() }}</td>
                                <td style="max-width:16rem;">{{ $leave->reason }}</td>
                                <td>
                                    <span class="badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span>
                                    @if ($leave->reviewer)
                                        <div class="small text-muted">by {{ $leave->reviewer->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($leave->status === 'pending')
                                        <div class="action-group">
                                            <form action="{{ url('/admin/leaves/' . $leave->id . '/approve') }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Approve leave for {{ $leave->user->name }}?')">
                                                    Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#rejectLeave{{ $leave->id }}">
                                                Disapprove
                                            </button>
                                        </div>
                                        <div class="modal fade" id="rejectLeave{{ $leave->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ url('/admin/leaves/' . $leave->id . '/reject') }}">
                                                        @csrf @method('PATCH')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Disapprove leave</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-2">{{ $leave->user->name }} · {{ $leave->typeLabel() }}
                                                                · {{ $leave->from_date->format('d-m-Y') }} to {{ $leave->to_date->format('d-m-Y') }}</p>
                                                            <label class="form-label">Note (optional)</label>
                                                            <textarea name="review_note" class="form-control" rows="3" maxlength="500"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Disapprove</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <small class="text-muted">{{ $leave->review_note ?: '—' }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-check fa-2x d-block mb-2 opacity-25"></i>
                                    No leave applications for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($leaves->total() > 0)
                <div class="p-3">{{ $leaves->links() }}</div>
            @endif
        </div>
    </div>
@endsection
