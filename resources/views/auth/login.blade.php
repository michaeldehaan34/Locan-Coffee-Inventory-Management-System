<x-guest-layout>
    <div class="login-page">
        <div class="login-bg" aria-hidden="true"></div>

        <div class="login-shell">
            <section class="login-card" id="loginCard">
                <div class="login-brand">
                    <img src="{{ asset(config('branding.logo')) }}"
                         alt="{{ config('branding.company_name') }}"
                         class="login-logo"
                         width="65"
                         height="65">
                    <h1 class="login-appname">{{ config('branding.company_name') }} {{ config('branding.subtitle') }}</h1>
                    <p class="login-subtitle">{{ config('branding.subtitle') }}</p>
                </div>

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="login-field-group">
                        <label for="barista_id" class="login-label">Username</label>
                        <div class="login-select-wrap">
                            <select class="login-field login-select @error('username') is-invalid @enderror"
                                    id="barista_id"
                                    name="username"
                                    required>
                                <option value="">Choose your name</option>

                                @if (! empty($baristas))
                                    <optgroup label="Barista">
                                        @foreach ($baristas as $username)
                                            <option value="barista:{{ $username }}" @selected(old('username') === 'barista:'.$username)>
                                                {{ $username }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if (! empty($managers))
                                    <optgroup label="Manager">
                                        @foreach ($managers as $username)
                                            <option value="manager:{{ $username }}" @selected(old('username') === 'manager:'.$username)>
                                                {{ $username }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <i class="bi bi-chevron-down login-select-caret" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="login-field-group">
                        <label for="password" class="login-label">Password</label>
                        <div class="login-input-icon">
                            <input type="password"
                                   class="login-field login-password-field @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="Masukkan password"
                                   autocomplete="current-password"
                                   required>
                            <button type="button"
                                    class="login-toggle-pw"
                                    id="togglePw"
                                    aria-label="Tampilkan password"
                                    title="Tampilkan / Sembunyikan password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <label class="login-remember">
                        <input type="checkbox" id="rememberBarista" class="login-remember-check">
                        <span class="login-remember-box" aria-hidden="true">
                            <i class="bi bi-check-lg"></i>
                        </span>
                        <span class="login-remember-text">Remember me?</span>
                    </label>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="login-btn-label">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Login
                        </span>
                        <span class="login-btn-spinner" aria-hidden="true"></span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-guest-layout>