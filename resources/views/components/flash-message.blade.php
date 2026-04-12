@php
    $messages = [
        'success' => ['bg' => 'success', 'icon' => 'fas fa-check-circle'],
        'error' => ['bg' => 'danger', 'icon' => 'fas fa-times-circle'],
        'warning' => ['bg' => 'warning', 'icon' => 'fas fa-exclamation-triangle'],
        'info' => ['bg' => 'info', 'icon' => 'fas fa-info-circle'],
    ];
@endphp

@foreach ($messages as $type => $config)
    @if (session($type))
        <div class="alert alert-{{ $config['bg'] }} alert-dismissible fade show flash-alert d-flex align-items-start gap-2 shadow-sm"
            role="alert" data-auto-dismiss>
            <i class="{{ $config['icon'] }} mt-1 flex-shrink-0"></i>
            <span>{{ session($type) }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

<style>
    .flash-alert {
        border-left: 4px solid transparent;
        border-radius: 8px;
        font-size: .92rem;
        animation: flash-slide-in .3s ease;
    }

    .alert-success.flash-alert {
        border-left-color: #198754;
    }

    .alert-danger.flash-alert {
        border-left-color: #dc3545;
    }

    .alert-warning.flash-alert {
        border-left-color: #ffc107;
    }

    .alert-info.flash-alert {
        border-left-color: #0dcaf0;
    }

    @keyframes flash-slide-in {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    window.addEventListener('load', function() {
        document.querySelectorAll('[data-auto-dismiss]').forEach(function(el) {
            setTimeout(function() {
                if (el.parentNode && typeof bootstrap !== 'undefined') {
                    bootstrap.Alert.getOrCreateInstance(el).close();
                }
            }, 4500);
        });
    });
</script>
