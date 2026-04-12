<!DOCTYPE html>
<html lang="en" itemscope itemtype="https://schema.org/WebApplication">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- SEO Meta --}}
    <title>@yield('title', 'Kemtex Management System') | Kemtex ERP</title>
    <meta name="description" content="@yield('meta_description', 'Kemtex Management System is a powerful ERP solution for manufacturing, inventory, production tracking, and analytics.')" />
    <meta name="keywords"
        content="Manufacturing ERP India, Inventory Management Software, Production ERP System, Kemtex ERP, Kemtex Management System, Manufacturing ERP, Small Business ERP India, Production Tracking Software, Bill of Materials Software, Sales Order Management India" />
    <meta name="author" content="Kemtex Management System" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="canonical" href="{{ url()->current() }}" />

    {{-- Open Graph --}}
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Kemtex Management System" />
    <meta property="og:title" content="@yield('title', 'Kemtex Management System') | Kemtex ERP" />
    <meta property="og:description" content="@yield('meta_description', 'Kemtex Management System — Smart Manufacturing & Inventory ERP for Modern Businesses.')" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:image" content="{{ asset('images/kemtex-logo.svg') }}" />

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('title', 'Kemtex Management System') | Kemtex ERP" />
    <meta name="twitter:description" content="@yield('meta_description', 'Kemtex Management System — Smart Manufacturing & Inventory ERP for Modern Businesses.')" />
    <meta name="twitter:image" content="{{ asset('images/kemtex-logo.svg') }}" />

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/kemtex-logo.svg') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/kemtex-logo.svg') }}" />

    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous" defer></script>

    {{-- Google Analytics GA4 --}}
    {{-- STEP 1: Go to analytics.google.com → Create Property → Get Measurement ID --}}
    {{-- STEP 2: Replace G-XXXXXXXXXX below with your real ID and uncomment the lines --}}
    {{-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script> --}}

    {{-- Google Search Console Verification --}}
    {{-- STEP: Go to search.google.com/search-console → Add Property → HTML tag method --}}
    {{-- Replace YOUR_CODE_HERE with actual code, then uncomment: --}}
    {{-- <meta name="google-site-verification" content="YOUR_CODE_HERE" /> --}}

    {{-- JSON-LD Organization Schema --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "Kemtex",
        "alternateName": "Kemtex Management System",
        "description": "Smart Manufacturing & Inventory ERP for Modern Businesses. Your Quality. Our Obsession.",
        "url": "{{ config('app.url') }}",
        "logo": "{{ asset('images/kemtex-logo.svg') }}",
        "slogan": "Your Quality. Our Obsession.",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web"
    }
    </script>

    @yield('styles')
</head>

<body class="sb-nav-fixed">
    @include('layouts.header')

    {{-- Main Content Area --}}

    {{-- ── Impersonation Banner ─────────────────────────────────────────── --}}
    @if (session('impersonator_id'))
        <div
            style="position:fixed;top:56px;left:0;right:0;z-index:1040;background:#fd7e14;color:#fff;padding:8px 20px;display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:.9rem;box-shadow:0 2px 6px rgba(0,0,0,.2);flex-wrap:wrap;">
            <span>
                <i class="fas fa-user-secret me-2"></i>
                <strong>Impersonating:</strong> {{ auth()->user()->name }}
                &nbsp;&mdash;&nbsp;{{ auth()->user()->company->company_name ?? '' }}
            </span>
            <form action="{{ route('impersonate.stop') }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light fw-semibold text-danger">
                    <i class="fas fa-times-circle me-1"></i>Stop Impersonating
                </button>
            </form>
        </div>
        <style>
            #layoutSidenav_content {
                padding-top: 40px;
            }
        </style>
    @endif

    <div id="layoutSidenav">
        @include('layouts.sidebar')
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    @include('components.flash-message')
                    @yield('content')
                </div>
            </main>
            @include('layouts.footer')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    @yield('scripts')

    {{-- ── Firebase Cloud Messaging (web push) ─────────────────────────── --}}
    @auth
        @if (config('firebase.web.api_key'))
            <script type="module">
                import {
                    initializeApp
                } from 'https://www.gstatic.com/firebasejs/12.11.0/firebase-app.js';
                import {
                    getAnalytics
                } from 'https://www.gstatic.com/firebasejs/12.11.0/firebase-analytics.js';
                import {
                    getMessaging,
                    getToken,
                    onMessage
                } from 'https://www.gstatic.com/firebasejs/12.11.0/firebase-messaging.js';

                const firebaseConfig = {
                    apiKey: @json(config('firebase.web.api_key')),
                    authDomain: @json(config('firebase.web.auth_domain')),
                    projectId: @json(config('firebase.web.project_id')),
                    storageBucket: @json(config('firebase.web.storage_bucket')),
                    messagingSenderId: @json(config('firebase.web.messaging_sender_id')),
                    appId: @json(config('firebase.web.app_id')),
                    measurementId: @json(config('firebase.web.measurement_id')),
                };

                const vapidKey = @json(config('firebase.web.vapid_key'));
                const saveTokenUrl = @json(route('fcm.save-token'));
                const csrfToken = @json(csrf_token());

                const app = initializeApp(firebaseConfig);
                const analytics = getAnalytics(app);
                const messaging = getMessaging(app);

                async function initPush() {
                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') return;

                        // Register service worker and pass config to it
                        const sw = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                        sw.active?.postMessage({
                            type: 'FIREBASE_CONFIG',
                            config: firebaseConfig
                        });

                        const token = await getToken(messaging, {
                            vapidKey,
                            serviceWorkerRegistration: sw
                        });

                        if (token) {
                            await fetch(saveTokenUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({
                                    fcm_token: token
                                }),
                            });
                        }

                        // Handle foreground messages
                        onMessage(messaging, payload => {
                            const {
                                title,
                                body
                            } = payload.notification ?? {};
                            if (!title) return;

                            // Show a Bootstrap toast
                            const toastEl = document.createElement('div');
                            toastEl.className =
                                'toast align-items-center text-bg-primary border-0 position-fixed bottom-0 end-0 m-3';
                            toastEl.setAttribute('role', 'alert');
                            toastEl.style.zIndex = 9999;
                            toastEl.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body"><strong>${title}</strong><br>${body ?? ''}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>`;
                            document.body.appendChild(toastEl);
                            new bootstrap.Toast(toastEl, {
                                delay: 6000
                            }).show();
                        });

                    } catch (err) {
                        console.warn('FCM init failed:', err);
                    }
                }

                if ('serviceWorker' in navigator && 'Notification' in window) {
                    initPush();
                }
            </script>
        @endif
    @endauth

    {{-- Notification bell badge polling (every 30 s) --}}
    @auth
        @if (!auth()->user()->isSuperAdmin())
            <script>
                function refreshNotifBadge() {
                    fetch('{{ url('/notifications/unread-count') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            const badge = document.getElementById('notif-badge');
                            if (!badge) return;
                            if (data.count > 0) {
                                badge.textContent = data.count > 99 ? '99+' : data.count;
                                badge.style.display = '';
                            } else {
                                badge.style.display = 'none';
                            }
                        })
                        .catch(() => {});
                }
                refreshNotifBadge();
                setInterval(refreshNotifBadge, 30000);
            </script>
        @endif
    @endauth
</body>

</html>
