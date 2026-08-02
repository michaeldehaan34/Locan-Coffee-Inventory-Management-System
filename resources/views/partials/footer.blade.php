<footer class="app-footer">
    <img class="app-footer-logo"
         src="{{ asset(config('branding.logo')) }}"
         alt="{{ config('branding.company_name') }}"
         width="22" height="22">

    <div class="app-footer-name">{{ config('branding.company_name') }} {{ config('branding.subtitle') }}</div>

    <div class="app-footer-meta">
        <span class="app-footer-version">Version 1.0.0</span>
        <span class="app-footer-dot">&bull;</span>
        <span class="app-footer-dev">Developed by
            <a href="https://instagram.com/michaeladh34"
               target="_blank"
               rel="noopener noreferrer"
               class="app-footer-handle">@michaeladh34</a>
        </span>
    </div>

    <div class="app-footer-copy">&copy; 2026 All Rights Reserved</div>
</footer>