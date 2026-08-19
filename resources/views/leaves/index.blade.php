@extends('layouts.app')

@section('title', 'Apply Leave')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-4 mb-3">
        <div>
            <h1 class="h3 mb-1">Apply Leave</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/attendance') }}">Attendance</a></li>
                <li class="breadcrumb-item active">Leave</li>
            </ol>
        </div>
        <a href="{{ url('/attendance') }}" class="btn btn-outline-secondary">
            <i class="fas fa-user-check me-1"></i> My attendance
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

    <div class="row">
        <div class="col-12 col-lg-5 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-paper-plane me-1"></i> Apply for leave</div>
                <div class="card-body">
                    <form method="POST" action="{{ url('/leaves') }}" id="leaveApplyForm" class="leave-type-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Leave type</label>
                            <select name="leave_type" id="leave_type" class="form-select leave-type" required>
                                @php $selectedType = old('leave_type', 'casual'); @endphp
                                <option value="casual" {{ $selectedType === 'casual' ? 'selected' : '' }}>Casual Leave</option>
                                <option value="sick" {{ $selectedType === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="unpaid" {{ $selectedType === 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                                <option value="half_day" {{ $selectedType === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                <option value="short" {{ $selectedType === 'short' ? 'selected' : '' }}>Short Leave (2 hours)</option>
                                <option value="other" {{ $selectedType === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6" id="from_date_wrap">
                                <label class="form-label from-date-label" id="from_date_label">From</label>
                                <input type="date" name="from_date" id="from_date" class="form-control from-date" value="{{ old('from_date') }}" required>
                            </div>
                            <div class="col-6 to-date-wrap" id="to_date_wrap">
                                <label class="form-label">To</label>
                                <input type="date" name="to_date" id="to_date" class="form-control to-date" value="{{ old('to_date') }}">
                            </div>
                        </div>
                        <div class="mb-3 session-wrap" id="session_wrap" style="display:none;">
                            <label class="form-label">Half of the day</label>
                            <select name="session" id="session" class="form-select">
                                <option value="morning" {{ old('session', 'morning') === 'morning' ? 'selected' : '' }}>Morning</option>
                                <option value="afternoon" {{ old('session') === 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                            </select>
                        </div>
                        <div class="mb-3 start-time-wrap" id="start_time_wrap" style="display:none;">
                            <label class="form-label">Start time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}">
                            <div class="form-text">Short leave is 2 hours from the start time (same day).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit application
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-list me-1"></i> My applications</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dates</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Admin note</th>
                                    <th class="text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaves as $leave)
                                    <tr>
                                        <td>
                                            {{ $leave->from_date->format('d-m-Y') }}
                                            @if ($leave->from_date->toDateString() !== $leave->to_date->toDateString())
                                                – {{ $leave->to_date->format('d-m-Y') }}
                                            @endif
                                            @if ($leave->timeDetail() !== '')
                                                <div class="small text-muted">{{ $leave->timeDetail() }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $leave->typeLabel() }}</td>
                                        <td>{{ $leave->durationLabel() }}</td>
                                        <td><span class="badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span></td>
                                        <td><small>{{ $leave->review_note ?: '—' }}</small></td>
                                        <td>
                                            @if (! $leave->employeeCanModify())
                                                <small class="text-muted">Locked</small>
                                            @else
                                                <div class="action-group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editLeave{{ $leave->id }}">
                                                        <i class="fas fa-pen-to-square"></i>
                                                    </button>
                                                    <form action="{{ url('/leaves/' . $leave->id) }}" method="POST"
                                                        onsubmit="return confirm('Delete this leave application?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <div class="modal fade" id="editLeave{{ $leave->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ url('/leaves/' . $leave->id) }}" class="leave-type-form">
                                                                @csrf @method('PUT')
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit leave</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="small text-muted">You can edit or delete until 11:59 PM on {{ $leave->to_date->format('d-m-Y') }}.</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Leave type</label>
                                                                        <select name="leave_type" class="form-select leave-type" required>
                                                                            @foreach (['casual' => 'Casual Leave', 'sick' => 'Sick Leave', 'unpaid' => 'Unpaid Leave', 'half_day' => 'Half Day', 'short' => 'Short Leave (2 hours)', 'other' => 'Other'] as $value => $label)
                                                                                <option value="{{ $value }}" {{ $leave->leave_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label from-date-label">From</label>
                                                                            <input type="date" name="from_date" class="form-control from-date" value="{{ $leave->from_date->format('Y-m-d') }}" required>
                                                                        </div>
                                                                        <div class="col-6 to-date-wrap">
                                                                            <label class="form-label">To</label>
                                                                            <input type="date" name="to_date" class="form-control to-date" value="{{ $leave->to_date->format('Y-m-d') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3 session-wrap" style="display:none;">
                                                                        <label class="form-label">Half of the day</label>
                                                                        <select name="session" class="form-select">
                                                                            <option value="morning" {{ $leave->session === 'morning' ? 'selected' : '' }}>Morning</option>
                                                                            <option value="afternoon" {{ $leave->session === 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3 start-time-wrap" style="display:none;">
                                                                        <label class="form-label">Start time</label>
                                                                        <input type="time" name="start_time" class="form-control" value="{{ $leave->start_time ? substr((string) $leave->start_time, 0, 5) : '' }}">
                                                                        <div class="form-text">Short leave is 2 hours from the start time.</div>
                                                                    </div>
                                                                    <div class="mb-0">
                                                                        <label class="form-label">Reason</label>
                                                                        <textarea name="reason" class="form-control" rows="3" maxlength="1000" required>{{ $leave->reason }}</textarea>
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
                                        <td colspan="6" class="text-center text-muted py-4">No leave applications yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $leaves->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    function bindLeaveForm(form) {
        const typeEl = form.querySelector('.leave-type') || form.querySelector('[name="leave_type"]');
        const fromEl = form.querySelector('.from-date') || form.querySelector('[name="from_date"]');
        const toEl = form.querySelector('.to-date') || form.querySelector('[name="to_date"]');
        const toWrap = form.querySelector('.to-date-wrap') || document.getElementById('to_date_wrap');
        const sessionWrap = form.querySelector('.session-wrap') || document.getElementById('session_wrap');
        const startWrap = form.querySelector('.start-time-wrap') || document.getElementById('start_time_wrap');
        const fromLabel = form.querySelector('.from-date-label') || document.getElementById('from_date_label');
        if (!typeEl || !fromEl || !toEl) return;

        function syncPartial() {
            const type = typeEl.value;
            const partial = type === 'half_day' || type === 'short';
            if (toWrap) toWrap.style.display = partial ? 'none' : '';
            if (sessionWrap) sessionWrap.style.display = type === 'half_day' ? '' : 'none';
            if (startWrap) startWrap.style.display = type === 'short' ? '' : 'none';
            if (fromLabel) fromLabel.textContent = partial ? 'Date' : 'From';
            toEl.required = !partial;
            if (partial && fromEl.value) toEl.value = fromEl.value;
        }

        typeEl.addEventListener('change', syncPartial);
        fromEl.addEventListener('change', function () {
            if (typeEl.value === 'half_day' || typeEl.value === 'short') {
                toEl.value = fromEl.value;
            }
        });
        form.addEventListener('submit', function () {
            if (typeEl.value === 'half_day' || typeEl.value === 'short') {
                toEl.value = fromEl.value;
            }
        });
        syncPartial();
    }

    document.querySelectorAll('.leave-type-form').forEach(bindLeaveForm);
})();
</script>
@endsection
