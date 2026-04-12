<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                @php
                    $user = auth()->user();
                    $currentUrl = url()->current();
                @endphp

                @if ($user->isSuperAdmin())
                    {{-- ── Super Admin navigation ── --}}
                    <div class="sb-sidenav-menu-heading">SuperAdmin</div>
                    <a class="nav-link {{ request()->is('superadmin/dashboard') ? 'active' : '' }}"
                        href="{{ url('/superadmin/dashboard') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>
                    <a class="nav-link {{ request()->is('superadmin/companies*') ? 'active' : '' }}"
                        href="{{ url('/superadmin/companies') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                        Companies
                    </a>
                    <a class="nav-link {{ request()->is('superadmin/users*') ? 'active' : '' }}"
                        href="{{ url('/superadmin/users') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                        Users
                    </a>
                    <a class="nav-link {{ request()->routeIs('superadmin.activity-log') ? 'active' : '' }}"
                        href="{{ route('superadmin.activity-log') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                        Activity Log
                    </a>
                    <a class="nav-link {{ request()->routeIs('superadmin.analytics') ? 'active' : '' }}"
                        href="{{ route('superadmin.analytics') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                        Analytics
                    </a>
                    <a class="nav-link {{ request()->routeIs('superadmin.announcements*') ? 'active' : '' }}"
                        href="{{ route('superadmin.announcements') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                        Announcements
                    </a>
                    <a class="nav-link {{ request()->routeIs('superadmin.health*') ? 'active' : '' }}"
                        href="{{ route('superadmin.health') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-heartbeat"></i></div>
                        System Health
                    </a>
                @else
                    {{-- ── Company user navigation ── --}}
                    <div class="sb-sidenav-menu-heading">Core</div>
                    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"
                        href="{{ url('/dashboard') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>

                    {{-- Inventory section — admin & inventory_admin --}}
                    @if ($user->hasRole(['admin', 'inventory_admin']))
                        <div class="sb-sidenav-menu-heading">Inventory</div>
                        <a class="nav-link {{ request()->is('inventory*') ? 'active' : '' }}"
                            href="{{ url('/inventory') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes-stacked"></i></div>
                            Inventory
                        </a>
                        <a class="nav-link {{ request()->is('products*') ? 'active' : '' }}"
                            href="{{ url('/products') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                            Products
                        </a>
                        <a class="nav-link {{ request()->is('materials*') ? 'active' : '' }}"
                            href="{{ url('/materials') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-cubes"></i></div>
                            Raw Materials
                        </a>
                        <a class="nav-link {{ request()->is('bom*') ? 'active' : '' }}" href="{{ url('/bom') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-list-check"></i></div>
                            Bill of Materials
                        </a>

                        <div class="sb-sidenav-menu-heading">Production</div>
                        <a class="nav-link {{ request()->is('production*') ? 'active' : '' }}"
                            href="{{ url('/production') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-industry"></i></div>
                            Production Logs
                        </a>
                        <a class="nav-link {{ request()->is('reports*') ? 'active' : '' }}"
                            href="{{ url('/reports') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                            Reports & Analytics
                        </a>
                    @endif

                    {{-- Sales section — admin & sales_admin --}}
                    @if ($user->hasRole(['admin', 'sales_admin']))
                        <div class="sb-sidenav-menu-heading">Sales</div>
                        <a class="nav-link {{ request()->is('sales/dashboard') ? 'active' : '' }}"
                            href="{{ url('/sales/dashboard') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                            Sales Dashboard
                        </a>
                        <a class="nav-link {{ request()->is('customers*') ? 'active' : '' }}"
                            href="{{ url('/customers') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Customers
                        </a>
                        <a class="nav-link {{ request()->is('sales/orders*') ? 'active' : '' }}"
                            href="{{ url('/sales/orders') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>
                            Sales Orders
                        </a>
                        @if ($user->isAdmin())
                        <a class="nav-link {{ request()->is('sales/product-costs*') ? 'active' : '' }}"
                            href="{{ url('/sales/product-costs') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                            Product Pricing
                        </a>
                        <a class="nav-link {{ request()->is('sales/reports*') ? 'active' : '' }}"
                            href="{{ url('/sales/reports/products') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                            Product Report
                        </a>
                        @endif
                    @endif

                    {{-- Admin-only section --}}
                    @if ($user->isAdmin())
                        <div class="sb-sidenav-menu-heading">Administration</div>
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                            href="{{ route('users.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-user-shield"></i></div>
                            User Management
                        </a>
                        <a class="nav-link {{ request()->routeIs('analytics.index') ? 'active' : '' }}"
                            href="{{ route('analytics.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                            Analytics
                        </a>
                        <a class="nav-link {{ request()->routeIs('activity-log.index') ? 'active' : '' }}"
                            href="{{ route('activity-log.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                            Activity Log
                        </a>
                    @endif

                @endif
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            {{ auth()->user()->name }}
            <span class="badge {{ auth()->user()->roleBadgeClass() }} ms-1" style="font-size:.65rem;">
                {{ auth()->user()->roleLabel() }}
            </span>
            @if (auth()->user()->company)
                <div class="small text-muted">{{ auth()->user()->company->company_name }}</div>
            @endif
        </div>
    </nav>
</div>
