@extends('layouts.app')

@section('title', 'Production Logs')

@section('content')
    <h1 class="mt-4">Production Logs</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Production Logs</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-industry me-1"></i> Production Logs</div>
            <a href="{{ url('/production/create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i><span class="btn-label">Log Production</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Product</th>
                            <th>Qty Produced</th>
                            <th>Date</th>
                            <th style="min-width:130px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                                <td class="fw-semibold">{{ $log->product->name }}</td>
                                <td><span class="fw-bold">{{ $log->quantity_produced }}</span></td>
                                <td><small>{{ $log->production_date->format('Y-m-d') }}</small></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/production/' . $log->id) }}" class="btn btn-sm btn-outline-info"
                                            title="View details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ url('/production/' . $log->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit log">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/production/' . $log->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure? This will restore materials back to stock.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete log">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-industry fa-2x mb-2 d-block opacity-25"></i>No production logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $logs->links() }}</div>
        </div>
    </div>
@endsection
