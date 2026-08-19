@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-4 mb-3">
        <div>
            <h1 class="h3 mb-1">My Attendance</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Attendance</li>
            </ol>
        </div>
        <a href="{{ url('/leaves') }}" class="btn btn-outline-primary">
            <i class="fas fa-calendar-plus me-1"></i> Apply leave
        </a>
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

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fas fa-user-check me-1"></i> Mark regular attendance</div>
                <div class="card-body">
                    @if ($onApprovedLeaveToday)
                        <div class="alert alert-info mb-0">
                            You are on approved leave today. Attendance is already marked as On Leave.
                        </div>
                    @else
                        <form method="POST" action="{{ url('/attendance') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="work_date" class="form-control"
                                    value="{{ old('work_date', now()->format('Y-m-d')) }}"
                                    min="{{ now()->format('Y-m-d') }}"
                                    max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="present" {{ old('status', 'present') === 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="half_day" {{ old('status') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                    <option value="absent" {{ old('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Check in</label>
                                    <input type="time" name="check_in" class="form-control"
                                        value="{{ old('check_in', $today?->check_in ? substr($today->check_in, 0, 5) : now()->format('H:i')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Check out</label>
                                    <input type="time" name="check_out" class="form-control"
                                        value="{{ old('check_out', $today?->check_out ? substr((string) $today->check_out, 0, 5) : '') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remark</label>
                                <input type="text" name="notes" class="form-control" maxlength="500"
                                    value="{{ old('notes', $today?->notes) }}" placeholder="Optional">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Save attendance
                            </button>
                            @if ($today)
                                <div class="text-center mt-2">
                                    <span class="badge {{ $today->statusBadgeClass() }}">Today: {{ $today->statusLabel() }}</span>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="fas fa-calendar-days me-1"></i> This month</span>
                    <form method="GET" class="d-flex gap-2">
                        <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Go</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>In</th>
                                    <th>Out</th>
                                    <th>Remark</th>
                                    <th class="text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $row)
                                    @php
                                        $leaveLocked = $row->status === 'leave' || in_array($row->work_date->toDateString(), $leaveLockedDates, true);
                                        $dayLocked = ! $row->work_date->isToday();
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $row->work_date->format('d-m-Y') }}</td>
                                        <td><span class="badge {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                                        <td>{{ $row->check_in ? substr($row->check_in, 0, 5) : '—' }}</td>
                                        <td>{{ $row->check_out ? substr($row->check_out, 0, 5) : '—' }}</td>
                                        <td>{{ $row->notes ?: '—' }}</td>
                                        <td>
                                            @if ($leaveLocked)
                                                <small class="text-muted">Leave</small>
                                            @elseif ($dayLocked)
                                                <small class="text-muted">Locked</small>
                                            @else
                                                <div class="action-group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editAttendance{{ $row->id }}">
                                                        <i class="fas fa-pen-to-square"></i>
                                                    </button>
                                                    <form action="{{ url('/attendance/' . $row->id) }}" method="POST"
                                                        onsubmit="return confirm('Delete attendance for {{ $row->work_date->format('d-m-Y') }}?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <div class="modal fade" id="editAttendance{{ $row->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ url('/attendance/' . $row->id) }}">
                                                                @csrf @method('PUT')
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit attendance</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="work_date" value="{{ $row->work_date->format('Y-m-d') }}">
                                                                    <p class="small text-muted mb-3">{{ $row->work_date->format('d-m-Y') }} · editable until 11:59 PM today</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status</label>
                                                                        <select name="status" class="form-select" required>
                                                                            @foreach (['present' => 'Present', 'half_day' => 'Half Day', 'absent' => 'Absent'] as $value => $label)
                                                                                <option value="{{ $value }}" {{ $row->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label">Check in</label>
                                                                            <input type="time" name="check_in" class="form-control"
                                                                                value="{{ $row->check_in ? substr($row->check_in, 0, 5) : '' }}">
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label">Check out</label>
                                                                            <input type="time" name="check_out" class="form-control"
                                                                                value="{{ $row->check_out ? substr((string) $row->check_out, 0, 5) : '' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-0">
                                                                        <label class="form-label">Remark</label>
                                                                        <input type="text" name="notes" class="form-control" maxlength="500"
                                                                            value="{{ $row->notes }}">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-calendar-days fa-2x d-block mb-2 opacity-25"></i>
                                            No attendance marked this month.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
