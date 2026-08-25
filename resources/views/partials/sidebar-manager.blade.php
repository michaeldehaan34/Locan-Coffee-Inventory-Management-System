<!-- Sidebar Manajemen -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <button class="sidebar-close" type="button" id="sidebarClose" aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="sidebar-logo d-none d-lg-flex">
            <img src="{{ asset(config('branding.logo')) }}" alt="{{ config('branding.company_name') }} Logo">
        </div>
        <div class="sidebar-logo d-flex d-lg-none">
            <img src="{{ asset('static/images/logo_locan.png') }}" alt="Locan Logo">
        </div>
        <div class="sidebar-brand-text">
            <span class="brand-name">{{ config('branding.company_name') }}<span class="brand-sub">{{ config('branding.subtitle') }}</span></span>
        </div>
        <button class="sidebar-toggle" type="button" id="sidebarCollapse" aria-label="Ciutkan menu">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <!-- MENU UTAMA -->
        <span class="nav-label">Menu Utama</span>

        <div class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}" href="{{ route('manager.dashboard') }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <a class="sidebar-link {{ request()->routeIs('manager.data-barista') || request()->routeIs('manager.data-barista.*') ? 'active' : '' }}"
           href="{{ route('manager.data-barista') }}">
            <i class="bi bi-people"></i>
            <span>Data Karyawan</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <div class="sidebar-user" id="managerProfileCard" role="button" tabindex="0" title="Edit Akun Manajemen">
        <div class="user-avatar">{{ mb_strtoupper(mb_substr(session('name') ?: (session('username') ?: 'M'), 0, 1)) }}</div>
        <div class="user-meta">
            <span class="user-name">{{ session('name') ?: session('username') }}</span>
            <span class="user-role">Manajemen</span>
            <span class="user-status"><span class="dot"></span>Online</span>
        </div>
        <i class="bi bi-pencil-square sidebar-user-edit-icon"></i>
    </div>

    <div class="sidebar-logout">
        <a class="sidebar-link text-danger"
           href="#"
           onclick="return confirmLogout(event)">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

        <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">
            @csrf
        </form>
    </div>
</aside>