@extends('layouts.app')

@section('title', 'Team Attendance')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-4 mb-3">
        <div>
            <h1 class="h3 mb-1">Team Attendance</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Attendance</li>
            </ol>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/admin/leaves') }}" class="btn btn-outline-warning">
                <i class="fas fa-calendar-check me-1"></i> Leave approvals
                @if ($pendingLeaves > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingLeaves }}</span>
                @endif
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                <i class="fas fa-plus me-1"></i> Mark attendance
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Team</div>
                    <div class="fs-4 fw-semibold">{{ $stats['team'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Present today</div>
                    <div class="fs-4 fw-semibold text-success">{{ $stats['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">On leave today</div>
                    <div class="fs-4 fw-semibold text-info">{{ $stats['leave'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Pending leaves</div>
                    <div class="fs-4 fw-semibold text-warning">{{ $stats['pending_leaves'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filters</div>
        <div class="card-body">
            <form method="GET" action="{{ url('/admin/attendance') }}" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted mb-1">Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label small text-muted mb-1">Employee</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('user_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }} ({{ $employee->roleLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
                    <a href="{{ url('/admin/attendance') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-1"></i> Attendance register</span>
            <span class="small text-muted">{{ $records->total() }} record{{ $records->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Remark</th>
                            <th>Marked by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row->work_date->format('d-m-Y') }}</td>
                                <td class="fw-semibold">{{ $row->user->name ?? '—' }}</td>
                                <td><small class="text-muted">{{ $row->user?->roleLabel() }}</small></td>
                                <td><span class="badge {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                                <td>{{ $row->check_in ? substr($row->check_in, 0, 5) : '—' }}</td>
                                <td>{{ $row->check_out ? substr($row->check_out, 0, 5) : '—' }}</td>
                                <td>{{ $row->notes ?: '—' }}</td>
                                <td><small>{{ $row->marker->name ?? '—' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-check fa-2x d-block mb-2 opacity-25"></i>
                                    No attendance records for this filter.
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                                            Mark first attendance
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($records->total() > 0)
                <div class="p-3">{{ $records->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-labelledby="markAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ url('/attendance') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="markAttendanceModalLabel">Mark attendance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->roleLabel() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="work_date" class="form-control" value="{{ now()->format('Y-m-d') }}"
                                max="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="present">Present</option>
                                <option value="half_day">Half Day</option>
                                <option value="short_leave">Short Leave (2 hrs)</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Check in</label>
                                <input type="time" name="check_in" class="form-control" value="{{ now()->format('H:i') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Check out</label>
                                <input type="time" name="check_out" class="form-control">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Remark</label>
                            <input type="text" name="notes" class="form-control" maxlength="500" placeholder="Optional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
