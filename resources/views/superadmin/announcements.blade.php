@extends('layouts.app')

@section('title', 'Broadcast Announcements')

@section('content')
    <h1 class="mt-4">Broadcast Announcements</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Announcements</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">

        {{-- ── Compose Form ──────────────────────────────────────────────────── --}}
        <div class="col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="fas fa-bullhorn me-1 text-primary"></i> Send New Announcement
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.announcements.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" placeholder="e.g. Scheduled Maintenance Tonight" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                            <textarea name="body" rows="5" class="form-control @error('body') is-invalid @enderror"
                                placeholder="Write your announcement here..." required>{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target</label>
                            <select name="target" id="targetSelect" class="form-select"
                                onchange="toggleCompanySelect(this.value)">
                                <option value="all" @selected(old('target', 'all') === 'all')>All Companies</option>
                                <option value="plan:free" @selected(old('target') === 'plan:free')>Plan: Free</option>
                                <option value="plan:basic" @selected(old('target') === 'plan:basic')>Plan: Basic</option>
                                <option value="plan:pro" @selected(old('target') === 'plan:pro')>Plan: Pro</option>
                                <option value="specific" @selected(str_starts_with(old('target', ''), 'company:'))>Specific Company</option>
                            </select>
                        </div>

                        <div class="mb-3" id="companySelectWrapper" style="display:none">
                            <label class="form-label fw-semibold">Select Company</label>
                            <select name="target" class="form-select" id="companyTargetSelect">
                                @foreach ($companies as $c)
                                    <option value="company:{{ $c->id }}" @selected(old('target') === 'company:' . $c->id)>
                                        {{ $c->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Channels <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="in_app"
                                    id="ch_inapp" @checked(!old('channels') || in_array('in_app', old('channels', ['in_app'])))>
                                <label class="form-check-label" for="ch_inapp">
                                    <i class="fas fa-bell me-1 text-primary"></i> In-App Notification
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="email"
                                    id="ch_email" @checked(in_array('email', old('channels', [])))>
                                <label class="form-check-label" for="ch_email">
                                    <i class="fas fa-envelope me-1 text-success"></i> Email (admin users)
                                </label>
                            </div>
                            @error('channels')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-1"></i> Send Announcement
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── History ───────────────────────────────────────────────────────── --}}
        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="fas fa-history me-1"></i> Sent Announcements
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:130px">Sent At</th>
                                    <th>Title</th>
                                    <th>Target</th>
                                    <th>Channels</th>
                                    <th>By</th>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($announcements as $ann)
                                    <tr>
                                        <td class="small text-muted text-nowrap">{{ $ann->sent_at->format('d M Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="fw-semibold small">{{ $ann->title }}</div>
                                            <div class="text-muted small text-truncate" style="max-width:260px"
                                                title="{{ $ann->body }}">{{ $ann->body }}</div>
                                        </td>
                                        <td class="small">
                                            @php
                                                $target = $ann->target;
                                                if ($target === 'all') {
                                                    echo '<span class="badge bg-primary">All</span>';
                                                } elseif (str_starts_with($target, 'plan:')) {
                                                    echo '<span class="badge bg-secondary">' .
                                                        ucfirst(substr($target, 5)) .
                                                        ' Plan</span>';
                                                } elseif (str_starts_with($target, 'company:')) {
                                                    $cId = (int) substr($target, 8);
                                                    $cName =
                                                        $companies->firstWhere('id', $cId)?->company_name ??
                                                        "Company #$cId";
                                                    echo '<span class="badge bg-info text-dark">' .
                                                        e($cName) .
                                                        '</span>';
                                                }
                                            @endphp
                                        </td>
                                        <td class="small">
                                            @foreach (explode(',', $ann->channels) as $ch)
                                                <span class="badge bg-light text-dark border">{{ $ch }}</span>
                                            @endforeach
                                        </td>
                                        <td class="small text-nowrap">{{ $ann->creator?->name ?? '—' }}</td>
                                        <td>
                                            <form action="{{ route('superadmin.announcements.destroy', $ann) }}"
                                                method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this announcement record?')"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No announcements sent yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($announcements->hasPages())
                    <div class="card-footer">{{ $announcements->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleCompanySelect(val) {
            const wrapper = document.getElementById('companySelectWrapper');
            const mainSelect = document.getElementById('targetSelect');
            const companySelect = document.getElementById('companyTargetSelect');
            if (val === 'specific') {
                wrapper.style.display = 'block';
                // disable the main select "name" so company select's name wins
                mainSelect.removeAttribute('name');
                companySelect.setAttribute('name', 'target');
            } else {
                wrapper.style.display = 'none';
                mainSelect.setAttribute('name', 'target');
                companySelect.removeAttribute('name');
            }
        }
        // Run on load in case of old() repopulation
        document.addEventListener('DOMContentLoaded', function() {
            toggleCompanySelect(document.getElementById('targetSelect').value);
        });
    </script>
@endsection
