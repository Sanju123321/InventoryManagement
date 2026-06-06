@extends('layouts.app')

@section('title', 'Firms')

@section('content')
    <h1 class="mt-4">Firms</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Firms</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-building me-1"></i> Firms List</div>
            <a href="{{ route('firms.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i><span class="btn-label">Add Firm</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th class="col-hide-mobile">GST</th>
                            <th class="col-hide-mobile">Mobile</th>
                            <th>Status</th>
                            <th style="min-width:120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($firms as $firm)
                            <tr>
                                <td>{{ $loop->iteration + ($firms->currentPage() - 1) * $firms->perPage() }}</td>
                                <td class="fw-semibold">{{ $firm->name }}</td>
                                <td class="col-hide-mobile">{{ $firm->gst ?? '-' }}</td>
                                <td class="col-hide-mobile">{{ $firm->mobile_number ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $firm->isActive() ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $firm->isActive() ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('firms.edit', $firm) }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit firm">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('firms.destroy', $firm) }}" method="POST"
                                            onsubmit="return confirm('Delete this firm?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete firm">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>No firms found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $firms->links() }}</div>
        </div>
    </div>
@endsection
