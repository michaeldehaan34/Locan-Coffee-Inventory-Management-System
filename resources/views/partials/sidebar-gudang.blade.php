<!-- Sidebar Admin Gudang -->
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
        <span class="nav-label">Dashboard</span>

        <a class="sidebar-link {{ request()->routeIs('gudang.dashboard') ? 'active' : '' }}" href="{{ route('gudang.dashboard') }}">
            <i class="bi bi-box-seam"></i> <span>Dashboard Stok Gudang</span>
        </a>

        <!-- INVENTORY GUDANG -->
        <span class="nav-label mt-3">Inventory Gudang</span>

        <a class="sidebar-link {{ request()->routeIs('gudang.stok-masuk.*') ? 'active' : '' }}"
           href="{{ route('gudang.stok-masuk.index') }}">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>Stok Masuk</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('gudang.kirim-stok.*') && !request()->routeIs('gudang.kirim-stok.create') ? 'active' : '' }}"
           href="{{ route('gudang.kirim-stok.index') }}">
            <i class="bi bi-truck"></i>
            <span>Riwayat Ambil Stok</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('gudang.kirim-stok.create') ? 'active' : '' }}"
           href="{{ route('gudang.kirim-stok.create') }}">
            <i class="bi bi-plus-square"></i>
            <span>Tambah Stok Keluar</span>
        </a>

        <!-- MASTER BAHAN BAKU -->
        <span class="nav-label mt-3">Master Data</span>
        
        <a class="sidebar-link {{ request()->routeIs('gudang.master-bahan*') ? 'active' : '' }}"
           href="{{ route('gudang.master-bahan') }}">
            <i class="bi bi-tags"></i>
            <span>Bahan Baku</span>
        </a>

        <a class="sidebar-link {{ request()->routeIs('gudang.pengaturan-limit*') ? 'active' : '' }}"
           href="{{ route('gudang.pengaturan-limit') }}">
            <i class="bi bi-sliders"></i>
            <span>Pengaturan Limit</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <div class="sidebar-user" id="managerProfileCard" role="button" tabindex="0" title="Edit Akun">
        <div class="user-avatar">{{ mb_strtoupper(mb_substr(session('name') ?: (session('username') ?: 'A'), 0, 1)) }}</div>
        <div class="user-meta">
            <span class="user-name">{{ session('name') ?: session('username') }}</span>
            <span class="user-role">Admin Gudang</span>
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
