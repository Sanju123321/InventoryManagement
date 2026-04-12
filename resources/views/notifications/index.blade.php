@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="d-flex align-items-center justify-content-between mt-4 mb-2 flex-wrap gap-2">
        <div>
            <h1 class="mb-0"><i class="fas fa-bell me-2 text-warning"></i>Notifications</h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Notifications</li>
            </ol>
        </div>
        <form method="POST" action="{{ url('/notifications/mark-all-read') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-check-double me-1"></i> Mark All Read
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-1"></i> All Notifications</span>
            <span class="text-muted small">{{ $notifications->total() }} total</span>
        </div>
        <div class="card-body p-0">
            @forelse ($notifications as $n)
                <div
                    class="notification-item d-flex align-items-start gap-3 p-3 border-bottom {{ $n->is_read ? '' : 'bg-light' }}">
                    {{-- Icon --}}
                    <div class="notification-icon flex-shrink-0 mt-1">
                        <i class="{{ $n->iconClass() }} fa-lg"></i>
                    </div>

                    {{-- Body --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                            <div>
                                <span class="fw-semibold">{{ $n->title }}</span>
                                @if (!$n->is_read)
                                    <span class="badge bg-primary ms-1" style="font-size:0.65rem;">NEW</span>
                                @endif
                            </div>
                            <span class="text-muted small text-nowrap">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mb-1 text-muted small mt-1">{{ $n->message }}</p>

                        {{-- Action link from data payload --}}
                        @if (!empty($n->data['url']))
                            <a href="{{ $n->data['url'] }}" class="btn btn-sm btn-outline-primary py-0 px-2 me-1">
                                <i class="fas fa-arrow-right me-1"></i>View
                            </a>
                        @endif

                        {{-- Delete --}}
                        <form method="POST" action="{{ url('/notifications/' . $n->id) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"
                                onclick="return confirm('Delete this notification?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No notifications yet.</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection

@section('styles')
    <style>
        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-icon {
            width: 36px;
            text-align: center;
        }
    </style>
@endsection
