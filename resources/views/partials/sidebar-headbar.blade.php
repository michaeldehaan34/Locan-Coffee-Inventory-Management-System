<!-- Headbar Sidebar -->
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

        <a class="sidebar-link {{ request()->routeIs('headbar.dashboard') ? 'active' : '' }}"
           href="{{ route('headbar.dashboard') }}" data-page="dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Stok Coffeeshop</span>
        </a>

        <div class="sidebar-divider"></div>
        <span class="nav-label">Inventory Coffeeshop</span>

        <a class="sidebar-link {{ request()->routeIs('headbar.coffee-shop.terima-stok.*') ? 'active' : '' }}"
           href="{{ route('headbar.coffee-shop.terima-stok.index') }}" data-page="terima_stok">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>Riwayat Penerimaan Stok</span>
        </a>


        <div class="sidebar-divider"></div>
        <span class="nav-label">Monitoring Barista</span>

        <a class="sidebar-link {{ request()->routeIs('headbar.riwayat.update-stok*') || request()->routeIs('headbar.update-stok*') ? 'active' : '' }}"
           href="{{ route('headbar.riwayat.update-stok') }}" data-page="riwayat_update_stok">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Update Stok</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('headbar.riwayat.daily-clean*') ? 'active' : '' }}"
           href="{{ route('headbar.riwayat.daily-clean') }}" data-page="riwayat_daily_clean">
            <i class="bi bi-stars"></i>
            <span>Riwayat Daily Clean</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('headbar.riwayat.token-listrik*') ? 'active' : '' }}"
           href="{{ route('headbar.riwayat.token-listrik') }}" data-page="riwayat_token_listrik">
            <i class="bi bi-lightning-charge"></i>
            <span>Riwayat Token Listrik</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <div class="sidebar-user" id="managerProfileCard" role="button" tabindex="0" title="Edit Akun">
        <div class="user-avatar">{{ mb_strtoupper(mb_substr(session('name') ?: (session('username') ?: 'U'), 0, 1)) }}</div>
        <div class="user-meta">
            <span class="user-name">{{ session('name') ?: session('username') }}</span>
            <span class="user-role">Headbar</span>
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
