@props(['active' => 'Dashboard', 'displayDate' => null])

<x-app-shell class="dashboard-shell" data-dashboard-shell>
    <div class="dashboard-shell__layout">
    <div class="dashboard-sidebar-backdrop" data-sidebar-backdrop></div>
    <aside class="dashboard-sidebar" data-dashboard-sidebar aria-label="Main navigation">
        <div class="dashboard-sidebar__brand">
            <span class="dashboard-sidebar__logo" aria-hidden="true">P</span>
            <span class="dashboard-sidebar__brand-name" data-sidebar-label>Prottyashi SFP</span>
            <button class="dashboard-icon-button dashboard-sidebar__close" type="button" data-sidebar-close aria-label="Close navigation">×</button>
        </div>

        <nav class="dashboard-sidebar__nav">
            <div class="dashboard-sidebar__label" data-sidebar-label>Workspaces</div>
            <a class="dashboard-nav-item {{ $active === 'Dashboard' ? 'is-active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard"><span aria-hidden="true">⌂</span><span data-sidebar-label>Dashboard</span></a>
            @if (auth()->user()->isAdmin())
                @php($administrationActive = in_array($active, ['Users', 'Schools', 'Demand setup', 'Official reports', 'Form 7'], true))
                <button class="dashboard-nav-item dashboard-nav-item--expandable {{ $administrationActive ? 'is-active' : '' }}" type="button" data-sidebar-section="Administration" aria-expanded="{{ $administrationActive ? 'true' : 'false' }}" title="Administration">
                    <span aria-hidden="true">⚙</span><span data-sidebar-label>Administration</span><span class="dashboard-nav-chevron" data-sidebar-label>›</span>
                </button>
                <div class="dashboard-submenu {{ $administrationActive ? 'is-open' : '' }}" data-sidebar-submenu="Administration">
                    <a href="{{ route('admin.users.index') }}" class="dashboard-submenu__item {{ $active === 'Users' ? 'is-active' : '' }}">Users</a>
                    <a href="{{ route('admin.schools.index') }}" class="dashboard-submenu__item {{ $active === 'Schools' ? 'is-active' : '' }}">Schools</a>
                    <a href="{{ route('admin.demand-setup.index') }}" class="dashboard-submenu__item {{ $active === 'Demand setup' ? 'is-active' : '' }}">Demand setup</a>
                    <a href="{{ route('admin.reports.index') }}" class="dashboard-submenu__item {{ $active === 'Official reports' ? 'is-active' : '' }}">Official reports</a>
                </div>
            @endif
            @php($operationsActive = in_array($active, ['Daily report', 'My entries'], true))
            <button class="dashboard-nav-item dashboard-nav-item--expandable {{ $operationsActive ? 'is-active' : '' }}" type="button" data-sidebar-section="Operations" aria-expanded="{{ $operationsActive ? 'true' : 'false' }}" title="Operations">
                <span aria-hidden="true">▤</span><span data-sidebar-label>Operations</span><span class="dashboard-nav-chevron" data-sidebar-label>›</span>
            </button>
            <div class="dashboard-submenu {{ $operationsActive ? 'is-open' : '' }}" data-sidebar-submenu="Operations">
                <a href="{{ route('reports.daily-delivery.index') }}" class="dashboard-submenu__item {{ $active === 'Daily report' ? 'is-active' : '' }}">Daily report</a>
                @unless (auth()->user()->isAdmin())
                    <a href="{{ route('field-staff.deliveries.index') }}" class="dashboard-submenu__item {{ $active === 'My entries' ? 'is-active' : '' }}">My entries</a>
                @endunless
            </div>
        </nav>

        <div class="dashboard-sidebar__footer">
            <div class="dashboard-user-card">
                <span class="dashboard-user-card__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="dashboard-user-card__details" data-sidebar-label>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="dashboard-logout" type="submit"><span aria-hidden="true">↪</span><span data-sidebar-label>Sign out</span></button>
            </form>
        </div>
        <button class="dashboard-sidebar__rail" type="button" data-sidebar-toggle aria-label="Collapse navigation">‹</button>
    </aside>

    <section class="dashboard-main">
        <header class="dashboard-topbar">
            <button class="dashboard-icon-button dashboard-mobile-toggle" type="button" data-sidebar-open aria-label="Open navigation">☰</button>
            <div>
                <p class="dashboard-eyebrow">School Feeding Management</p>
                <h1>{{ $active }}</h1>
            </div>
            <div class="dashboard-topbar__date">{{ $displayDate ?? now()->format('D, d M Y') }}</div>
        </header>
        <main class="dashboard-content">{{ $slot }}</main>
    </section>
</div>
</x-app-shell>
