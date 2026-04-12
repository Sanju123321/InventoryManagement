@extends('layouts.app')

@section('title', 'System Health')

@section('content')
    <h1 class="mt-4">System Health</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">System Health</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- â”€â”€ Optimize All Button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="d-flex justify-content-end mb-3">
        <form action="{{ route('superadmin.health.optimize') }}" method="POST"
            onsubmit="return confirm('Run full optimization? This will cache config, routes, views, and events.')">
            @csrf
            <button type="submit" class="btn btn-success px-4">
                <i class="fas fa-rocket me-2"></i> Optimize Application
            </button>
        </form>
    </div>

    {{-- â”€â”€ Status Overview Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div
                        class="rounded-circle p-3 fs-4 {{ $dbOk ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Database</div>
                        <div class="fw-bold {{ $dbOk ? 'text-success' : 'text-danger' }}">
                            {{ $dbOk ? 'Connected' : 'Error' }}
                        </div>
                        @if ($dbMs !== null)
                            <div class="small text-muted">{{ $dbMs }} ms</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div
                        class="rounded-circle p-3 fs-4 {{ $cacheOk ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }}">
                        <i class="fas fa-memory"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Cache</div>
                        <div class="fw-bold {{ $cacheOk ? 'text-success' : 'text-warning' }}">
                            {{ $cacheOk ? 'OK' : 'Degraded' }}
                        </div>
                        <div class="small text-muted">{{ $sysInfo['cache_driver'] }}</div>
                        <form action="{{ route('superadmin.health.clear-cache') }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 mt-1"
                                onclick="return confirm('Clear all application caches?')">Clear All Cache</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div
                        class="rounded-circle p-3 fs-4 {{ $pendingJobs === 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }}">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Pending Jobs</div>
                        <div class="fw-bold fs-5">{{ number_format($pendingJobs) }}</div>
                        <div class="small text-muted">driver: {{ $sysInfo['queue_driver'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div
                        class="rounded-circle p-3 fs-4 {{ $failedJobs === 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Failed Jobs</div>
                        <div class="fw-bold fs-5 {{ $failedJobs > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($failedJobs) }}
                        </div>
                        @if ($failedJobs > 0)
                            <form action="{{ route('superadmin.health.clear-failed') }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger py-0 px-1 mt-1"
                                    onclick="return confirm('Clear all failed jobs?')">Clear</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- â”€â”€ Row: Disk Usage + System Info â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-hdd me-1 text-secondary"></i> Disk Usage
                </div>
                <div class="card-body">
                    @if ($disk['diskTotal'])
                        @php
                            $pct = $disk['diskPct'];
                            $color = $pct > 90 ? 'danger' : ($pct > 70 ? 'warning' : 'success');
                        @endphp
                        <div class="mb-2 d-flex justify-content-between small">
                            <span>Used: <strong>{{ number_format($disk['diskUsed'] / 1024 ** 3, 1) }} GB</strong></span>
                            <span>Free: <strong>{{ number_format($disk['diskFree'] / 1024 ** 3, 1) }} GB</strong></span>
                            <span>Total: <strong>{{ number_format($disk['diskTotal'] / 1024 ** 3, 1) }} GB</strong></span>
                        </div>
                        <div class="progress" style="height:20px">
                            <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%">
                                {{ $pct }}%</div>
                        </div>
                    @else
                        <p class="text-muted small">Disk info unavailable.</p>
                    @endif
                    <hr class="my-2">
                    <div class="small text-muted">
                        Log file size:
                        <strong>{{ $logSize > 0 ? number_format($logSize / 1024, 1) . ' KB' : 'â€”' }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-info-circle me-1 text-info"></i> System Info
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            @foreach ($sysInfo as $key => $val)
                                <tr>
                                    <th class="small text-muted ps-3" style="width:45%">
                                        {{ str_replace('_', ' ', ucwords($key, '_')) }}</th>
                                    <td class="small fw-semibold ps-3">
                                        @if ($key === 'debug' && $val === 'ON')
                                            <span class="text-danger">ON</span>
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-tasks me-1 text-warning"></i> Recent Failed Jobs
                </div>
                <div class="card-body p-0" style="max-height:220px;overflow-y:auto;">
                    @if ($recentFailed->isEmpty())
                        <p class="text-success text-center py-3 small m-0">
                            <i class="fas fa-check-circle me-1"></i> No failed jobs.
                        </p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($recentFailed as $job)
                                    <tr>
                                        <td class="small">
                                            <div class="fw-semibold text-truncate" style="max-width:200px"
                                                title="{{ $job['job'] }}">{{ $job['job'] }}</div>
                                            <div class="text-danger small text-truncate" style="max-width:200px"
                                                title="{{ $job['exception'] }}">{{ $job['exception'] }}</div>
                                            <div class="text-muted" style="font-size:0.7rem">{{ $job['failed_at'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- â”€â”€ Error Log â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-alt me-1 text-danger"></i> Recent Error / Critical Log Entries</span>
            <span class="badge bg-secondary">Last 20 entries</span>
        </div>
        <div class="card-body p-0" style="max-height:400px;overflow-y:auto;">
            @if (empty($logLines))
                <p class="text-success text-center py-3 m-0">
                    <i class="fas fa-check-circle me-1"></i> No ERROR/CRITICAL entries found in the log.
                </p>
            @else
                <div class="p-2">
                    @foreach ($logLines as $line)
                        <div class="font-monospace small p-1 mb-1 rounded bg-dark text-danger-emphasis text-wrap"
                            style="font-size:0.75rem;word-break:break-all;background:#1e1e2e!important;color:#ff6b6b!important">
                            {{ $line }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
