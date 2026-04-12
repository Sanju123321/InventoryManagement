<nav class="sb-topnav navbar navbar-expand navbar-dark" style="background:#1a1a2e; border-bottom:3px solid #F5C800;">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3 d-flex align-items-center"
        href="{{ auth()->check() && auth()->user()->isSuperAdmin() ? url('/superadmin/dashboard') : url('/dashboard') }}">
        <img src="{{ asset('images/kemtex-logo.svg') }}" alt="Kemtex Management System" height="38"
            style="width:auto;max-width:120px;filter:drop-shadow(0 2px 6px rgba(245,200,0,.3));" loading="eager" /></a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Navbar Search (hidden on xs, visible md+) -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..."
                aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
        </div>
    </form>
    <!-- Navbar Right -->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">

        {{-- Notification Bell --}}
        @auth
            @if (!auth()->user()->isSuperAdmin())
                <li class="nav-item me-1">
                    <a class="nav-link position-relative" href="{{ url('/notifications') }}" title="Notifications">
                        <i class="fas fa-bell fa-fw"></i>
                        <span id="notif-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size:0.6rem; display:none;">0</span>
                    </a>
                </li>
            @endif
        @endauth

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li>
                    @auth
                        <div class="dropdown-item-text small text-muted px-3 py-1 fw-semibold">
                            {{ auth()->user()->name }}
                        </div>
                        <hr class="dropdown-divider my-1">
                    @endauth
                </li>
                <li><a class="dropdown-item" href="{{ route('profile.settings') }}">
                        <i class="fas fa-gear me-2 text-muted"></i>Settings
                    </a></li>
                @if (auth()->user()?->role === 'superadmin')
                    <li><a class="dropdown-item" href="{{ route('superadmin.activity-log') }}">
                            <i class="fas fa-history me-2 text-muted"></i>Activity Log
                        </a></li>
                @else
                    <li><a class="dropdown-item" href="{{ route('activity-log.index') }}">
                            <i class="fas fa-history me-2 text-muted"></i>Activity Log
                        </a></li>
                @endif
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <form action="{{ url('/logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-right-from-bracket me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
