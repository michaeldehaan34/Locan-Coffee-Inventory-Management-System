<!-- Barista Sidebar -->
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
        <span class="nav-label">Menu Utama</span>

        <a class="sidebar-link {{ request()->routeIs('barista.dashboard') ? 'active' : '' }}"
           href="{{ route('barista.dashboard') }}" data-page="dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Barista</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('barista.update-stok') ? 'active' : '' }}"
           href="{{ route('barista.update-stok') }}" data-page="update_stok">
            <i class="bi bi-arrow-repeat"></i>
            <span>Update Stok</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('barista.ambil-bahan-gudang') ? 'active' : '' }}"
           href="{{ route('barista.ambil-bahan-gudang') }}" data-page="ambil_bahan_gudang">
            <i class="bi bi-box-arrow-right"></i>
            <span>Ambil Bahan Gudang</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('barista.daily-clean') ? 'active' : '' }}"
           href="{{ route('barista.daily-clean') }}" data-page="daily_clean">
            <i class="bi bi-camera"></i>
            <span>Daily Clean</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('barista.token-listrik') ? 'active' : '' }}"
           href="{{ route('barista.token-listrik') }}" data-page="token_listrik">
            <i class="bi bi-lightning-charge"></i>
            <span>Token Listrik</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <div class="sidebar-user">
        <div class="user-avatar">{{ mb_strtoupper(mb_substr(session('name') ?: (session('username') ?: 'U'), 0, 1)) }}</div>
        <div class="user-meta">
            <span class="user-name">{{ session('name') ?: session('username') }}</span>
            <span class="user-status"><span class="dot"></span>Online</span>
        </div>
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