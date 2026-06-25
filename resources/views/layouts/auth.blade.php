<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Sign In') | Kemtex Management System</title>
    <meta name="description"
        content="Kemtex Management System — Smart Manufacturing & Inventory ERP for Modern Businesses." />
    <meta name="robots" content="noindex, nofollow" />

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/kemtex-logo.svg') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous" defer></script>
    @yield('styles')
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f0f2f5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* â”€â”€ Split layout â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .auth-wrapper {
            display: flex;
            flex: 1;
            min-height: 100vh;
        }

        /* Left panel */
        .auth-brand {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 4rem;
            background: #1a1a2e;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .auth-brand::before {
            content: '';
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: rgba(245, 200, 0, .05);
            top: -120px;
            right: -140px;
        }

        .auth-brand::after {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(245, 200, 0, .07);
            bottom: -80px;
            left: -80px;
        }

        .auth-brand-logo {
            width: 260px;
            height: auto;
            background: none;
            border-radius: 0;
            display: block;
            margin-bottom: 2.2rem;
            position: relative;
            z-index: 1;
        }

        .auth-brand-logo img {
            width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 6px 20px rgba(245, 200, 0, .35));
        }

        .auth-brand h1 {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: .6rem;
            letter-spacing: -.3px;
            color: #F5C800;
        }

        .auth-brand p {
            font-size: .97rem;
            opacity: .72;
            max-width: 340px;
            line-height: 1.7;
            color: #e2e8f0;
        }

        .auth-features {
            list-style: none;
            margin-top: 2.5rem;
        }

        .auth-features li {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .92rem;
            color: #cbd5e1;
            margin-bottom: .9rem;
        }

        .auth-features li i {
            width: 30px;
            height: 30px;
            background: rgba(245, 200, 0, .18);
            color: #F5C800;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .82rem;
            flex-shrink: 0;
        }

        /* Right panel */
        .auth-form-panel {
            width: 480px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 2.5rem;
            background: #fff;
        }

        .auth-form-inner {
            width: 100%;
            max-width: 380px;
        }

        /* Form elements */
        .auth-form-inner h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: .4rem;
        }

        .auth-form-inner .auth-subtitle {
            color: #6b7280;
            font-size: .92rem;
            margin-bottom: 2rem;
        }

        .field-group {
            margin-bottom: 1.1rem;
        }

        .field-group label {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #374151;
            margin-bottom: .45rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-ico {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: .85rem;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: .78rem 1rem .78rem 2.6rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: .95rem;
            color: #111827;
            background: #f9fafb;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }

        .input-wrap input:focus {
            border-color: #1C1C1B;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(28, 28, 27, .10);
        }

        .input-wrap input::placeholder {
            color: #d1d5db;
        }

        /* Toggle password */
        .input-wrap .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            font-size: .85rem;
            line-height: 1;
        }

        .input-wrap .toggle-pw:hover {
            color: #1C1C1B;
        }

        .row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: .85rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: .45rem;
            color: #374151;
            cursor: pointer;
        }

        .remember-label input[type=checkbox] {
            accent-color: #F5C800;
            width: 15px;
            height: 15px;
        }

        .btn-signin {
            width: 100%;
            padding: .85rem;
            border: none;
            border-radius: 10px;
            background: #F5C800;
            color: #1C1C1B;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .3px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(245, 200, 0, .45);
        }

        .btn-signin:hover {
            opacity: .92;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(245, 200, 0, .55);
        }

        .btn-signin:active {
            transform: scale(.98);
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: #d1d5db;
            font-size: .82rem;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .auth-footer-text {
            text-align: center;
            font-size: .87rem;
            color: #6b7280;
            margin-top: 1.6rem;
        }

        .auth-footer-text a {
            color: #1C1C1B;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-footer-text a:hover {
            text-decoration: underline;
        }

        .alert-auth {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #dc2626;
            padding: .75rem 1rem;
            font-size: .88rem;
            margin-bottom: 1.2rem;
        }

        .alert-auth p {
            margin: 0;
        }

        /* Footer */
        .auth-page-footer {
            text-align: center;
            font-size: .78rem;
            color: #9ca3af;
            padding: 1rem;
            background: #fff;
        }

        /* Responsive: hide brand panel on small screens */
        @media (max-width: 768px) {
            .auth-brand {
                display: none;
            }

            .auth-form-panel {
                width: 100%;
                padding: 2rem 1.5rem;
                min-height: 100vh;
            }
        }

        @media (max-width: 480px) {
            .auth-form-panel {
                padding: 1.5rem 1rem;
            }

            .auth-form-inner h2 {
                font-size: 1.4rem;
            }

            .input-wrap input,
            .input-wrap select {
                padding: .65rem .8rem .65rem 2.4rem;
                font-size: .88rem;
            }

            .btn-signin {
                padding: .75rem;
                font-size: .92rem;
            }
        }

        /* ── Register card styles ────────────────────────────── */
        .auth-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, .14);
        }

        .auth-card .card-header {
            background: transparent;
            border-bottom: 1px solid #eee;
            padding: 2rem 2rem 1rem;
        }

        .auth-card .card-body {
            padding: 2rem;
        }

        .auth-card .card-footer {
            background: transparent;
            border-top: 1px solid #eee;
        }

        .auth-card .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            height: auto;
            font-size: 1rem;
        }

        .auth-card .form-floating>.form-control {
            padding: 1.625rem 1rem 0.625rem 2.75rem;
        }

        .auth-card .form-floating>label {
            padding-left: 2.75rem;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
            pointer-events: none;
        }

        .btn-auth {
            padding: 0.75rem 1.5rem;
            font-size: 1.05rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }

        .auth-logo i {
            font-size: 3rem;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">

        {{-- Left brand panel --}}
        <div class="auth-brand">
            <div class="auth-brand-logo">
                <img src="{{ asset('images/kemtex-logo.svg') }}" alt="Kemtex Management System" />
            </div>
            <h1>Smart Manufacturing &amp; Inventory ERP</h1>
            <p>Streamline production, inventory, and sales for your business.</p>
            <ul class="auth-features">
                <li><i class="fas fa-box"></i> Product &amp; Raw Material Tracking</li>
                <li><i class="fas fa-industry"></i> Production Logs &amp; BOM</li>
                <li><i class="fas fa-chart-line"></i> Sales Reports &amp; Analytics</li>
                <li><i class="fas fa-bell"></i> Real-time Push Notifications</li>
            </ul>
        </div>

        {{-- Right form panel --}}
        <div class="auth-form-panel">
            <div class="auth-form-inner">
                @include('components.flash-message')
                @yield('content')
            </div>
        </div>

    </div>

    <footer class="auth-page-footer">
        &copy; {{ date('Y') }} Kemtex Management System. All rights reserved. &nbsp;&middot;&nbsp;
        <a href="#" style="color:#9ca3af;">Privacy Policy</a> &nbsp;&middot;&nbsp;
        <a href="#" style="color:#9ca3af;">Terms &amp; Conditions</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    @yield('scripts')
</body>
<div id="pageLoaderOverlay"
    style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:20000;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);">
    <div
        style="display:flex;flex-direction:column;align-items:center;gap:.9rem;padding:1.25rem 1.5rem;border-radius:16px;background:#fff;box-shadow:0 20px 60px rgba(0,0,0,.22);min-width:220px;">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <div style="font-weight:700;color:#111827;">Please wait...</div>
    </div>
</div>

<script>
    (function() {
        const overlay = document.getElementById('pageLoaderOverlay');
        if (!overlay) return;

        const showLoader = () => {
            overlay.style.display = 'flex';
        };

        const hideLoader = () => {
            overlay.style.display = 'none';
        };

        window.addEventListener('pageshow', hideLoader);

        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.loaderDisabled === 'true') return;
            if (form.dataset.loaderSubmitted === 'true') return;

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                if (typeof form.reportValidity === 'function') {
                    form.reportValidity();
                }
                return;
            }

            event.preventDefault();
            form.dataset.loaderSubmitted = 'true';
            showLoader();
            window.setTimeout(() => form.submit(), 2000);
        }, true);
    })();
</script>

</html>
