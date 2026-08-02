<!-- Manager Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <button class="sidebar-close" type="button" id="sidebarClose" aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="sidebar-logo">
            <img src="{{ asset(config('branding.logo')) }}" alt="{{ config('branding.company_name') }} Logo">
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

        <a class="sidebar-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}"
           href="{{ route('manager.dashboard') }}" data-page="dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.stok-masuk.index') ? 'active' : '' }}"
           href="{{ route('manager.stok-masuk.index') }}" data-page="generic">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>Riwayat Stok Masuk</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.riwayat.update-stok') ? 'active' : '' }}"
           href="{{ route('manager.riwayat.update-stok') }}" data-page="history">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Update</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.riwayat.daily-clean') ? 'active' : '' }}"
           href="{{ route('manager.riwayat.daily-clean') }}" data-page="daily_clean">
            <i class="bi bi-camera-reels"></i>
            <span>Riwayat Daily Clean</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.riwayat.token-listrik') ? 'active' : '' }}"
           href="{{ route('manager.riwayat.token-listrik') }}" data-page="token_listrik">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Riwayat Token Listrik</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.data-barista') ? 'active' : '' }}"
           href="{{ route('manager.data-barista') }}" data-page="barista">
            <i class="bi bi-people"></i>
            <span>Data Barista</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.master-bahan') ? 'active' : '' }}"
           href="{{ route('manager.master-bahan') }}" data-page="master-bahan">
            <i class="bi bi-box-seam"></i>
            <span>Master Bahan</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.pengaturan-limit') ? 'active' : '' }}"
           href="{{ route('manager.pengaturan-limit') }}" data-page="pengaturan-limit">
            <i class="bi bi-sliders"></i>
            <span>Pengaturan Limit</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.laporan') ? 'active' : '' }}"
           href="{{ route('manager.laporan') }}" data-page="report">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Laporan</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('manager.forecast') ? 'active' : '' }}"
           href="{{ route('manager.forecast') }}" data-page="forecast">
            <i class="bi bi-graph-up"></i>
            <span>Forecast</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <div class="sidebar-user" id="managerProfileCard" role="button" tabindex="0" title="Edit Akun Manager">
        <div class="user-avatar">{{ mb_strtoupper(mb_substr(session('name') ?: (session('username') ?: 'M'), 0, 1)) }}</div>
        <div class="user-meta">
            <span class="user-name">{{ session('name') ?: session('username') }}</span>
            <span class="user-role">Manager</span>
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