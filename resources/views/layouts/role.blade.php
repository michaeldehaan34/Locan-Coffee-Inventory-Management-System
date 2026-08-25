<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('branding.app_name'))</title>

    <link rel="preload" as="image" href="{{ asset(config('branding.logo')) }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('static/css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/loading.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('extra_css')
</head>
<body>
    @if (session('role') === 'manajemen')
        @include('partials.sidebar-manager')
    @elseif (session('role') === 'admin gudang')
        @include('partials.sidebar-gudang')
    @elseif (session('role') === 'headbar')
        @include('partials.sidebar-headbar')
    @elseif (session('role') === 'headkitchen')
        @include('partials.sidebar-headkitchen')
    @elseif (session('role') === 'kitchen')
        @include('partials.sidebar-kitchen')
    @elseif (session('role') === 'barista')
        @include('partials.sidebar-barista')
    @endif

    <div class="mobile-topbar">
        <button class="hamburger" type="button" id="sidebarHamburger" aria-label="Buka menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="mobile-brand">
            <img src="{{ asset('static/images/logo_locan.png') }}" alt="Locan Logo">
            <span class="mobile-title">Locan</span>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

    @if (!isset($hideLoader) || !$hideLoader)
    <div class="page-loader" id="pageLoader" aria-hidden="true">
        <img src="{{ asset(config('branding.logo_white')) }}" alt="{{ config('branding.company_name') }}" class="loader-logo">
    </div>
    @endif

    <div class="content-area">
        <main>
            @if (session('__flash'))
                <div class="flash-container" hidden>
                    @foreach (session('__flash') as $f)
                        <div class="alert alert-{{ $f['type'] }}" data-toast="{{ $f['type'] }}" data-toast-msg="{{ $f['msg'] }}">
                            {{ $f['msg'] }}
                        </div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    <!-- ============================================ -->
    <!-- MODAL: Edit Akun (Coffee Theme) -->
    <!-- ============================================ -->
    @php
        $role = session('role');
        $canEditProfile = in_array($role, ['manajemen', 'headbar', 'headkitchen', 'admin gudang']);
        $profileRoute = '';
        if ($role === 'manajemen') $profileRoute = route('manager.profile.update');
        elseif ($role === 'headbar') $profileRoute = route('headbar.profile.update');
        elseif ($role === 'headkitchen') $profileRoute = route('headkitchen.profile.update');
        elseif ($role === 'admin gudang') $profileRoute = route('gudang.profile.update');
    @endphp

    @if ($canEditProfile)
    <div class="modal fade" id="editAkunModal" tabindex="-1" aria-labelledby="editAkunModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border border-secondary border-opacity-10 rounded-4 shadow">
                <form method="POST" action="{{ $profileRoute }}" id="editAkunForm">
                    @csrf

                    <!-- Header with Coffee Gradient -->
                    <div class="modal-header border-0 p-4 pb-3 rounded-top-4" style="background: linear-gradient(135deg, #2C1810, #4A3728);">
                        <div class="d-flex flex-column w-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="editAkunModalLabel">
                                    <span style="font-size: 1.4rem;">👤</span>
                                    <span class="text-white">Edit Akun</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <span class="text-white-50 small mt-1" style="font-size: 0.82rem; letter-spacing: 0.2px;">
                                Kelola informasi akun dan keamanan login Anda
                            </span>
                        </div>
                    </div>

                    <div class="modal-body p-4" id="editAkunModalBody">

                        <!-- SECTION 1: Informasi Akun -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2" style="color: var(--text-secondary);">
                                <i class="bi bi-info-circle"></i>
                                Informasi Akun
                            </h6>

                            <!-- Nama Lengkap -->
                            <div class="mb-3">
                                <label for="editNama" class="form-label small fw-medium">Nama Lengkap</label>
                                <input type="text" class="form-control" id="editNama" name="nama"
                                       value="{{ session('name') }}" required
                                       placeholder="Masukkan nama lengkap Anda"
                                       autocomplete="off">
                                <div class="invalid-feedback" id="editNamaError"></div>
                            </div>

                            <!-- Username -->
                            <div class="mb-1">
                                <label for="editUsername" class="form-label small fw-medium">Username</label>
                                <input type="text" class="form-control" id="editUsername" name="username"
                                       value="{{ session('username') }}"
                                       placeholder="Kosongkan jika tidak diubah"
                                       autocomplete="off">
                                <div class="invalid-feedback" id="editUsernameError"></div>
                            </div>
                        </div>

                        <hr class="my-4 border-secondary opacity-10">

                        <!-- SECTION 2: Keamanan Akun -->
                        <div class="mb-2">
                            <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2" style="color: var(--text-secondary);">
                                <i class="bi bi-shield-lock"></i>
                                Keamanan Akun
                            </h6>

                            <p class="text-muted small mb-3" style="font-size: 0.82rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Kosongkan jika tidak ingin mengganti password.
                            </p>

                            <!-- Password Lama -->
                            <div class="mb-3">
                                <label for="editPasswordLama" class="form-label small fw-medium">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="editPasswordLama" name="password_lama"
                                           placeholder="Masukkan password lama" autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-pw-btn border-start-0" type="button"
                                            data-target="editPasswordLama" aria-label="Tampilkan password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="editPasswordLamaError"></div>
                            </div>

                            <!-- Password Baru -->
                            <div class="mb-3">
                                <label for="editPasswordBaru" class="form-label small fw-medium">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="editPasswordBaru" name="password_baru"
                                           placeholder="Minimal 8 karakter" autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-pw-btn border-start-0" type="button"
                                            data-target="editPasswordBaru" aria-label="Tampilkan password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="editPasswordBaruError"></div>
                            </div>

                            <!-- Konfirmasi Password Baru -->
                            <div class="mb-2">
                                <label for="editPasswordKonfirmasi" class="form-label small fw-medium">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="editPasswordKonfirmasi" name="password_baru_confirmation"
                                           placeholder="Ulangi password baru" autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-pw-btn border-start-0" type="button"
                                            data-target="editPasswordKonfirmasi" aria-label="Tampilkan password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="editPasswordKonfirmasiError"></div>
                            </div>

                            <!-- Realtime Password Match Indicator -->
                            <div id="passwordMatchIndicator" class="d-none mt-2">
                                <small class="d-flex align-items-center gap-1 fw-medium">
                                    <i class="bi" id="matchIcon"></i>
                                    <span id="matchText"></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 border-top border-secondary border-opacity-10 px-4 py-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Batal
                        </button>
                        <button type="submit" class="btn px-4 text-white fw-semibold" id="simpanProfileBtn"
                                style="background: linear-gradient(135deg, #5C3A28, #8B5E3C); border: none;">
                            <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('static/js/script.js') }}"></script>

    @stack('extra_js')
</body>
</html>
