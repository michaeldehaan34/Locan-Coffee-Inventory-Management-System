<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('branding.app_name', 'LOTRA') }} - Login</title>

    <link rel="preload" as="image" href="{{ asset(config('branding.logo')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('static/css/login.css') }}">
</head>
<body>
    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

    <div class="page-loader" id="pageLoader" aria-hidden="true">
        <img src="{{ asset(config('branding.logo_white')) }}" alt="{{ config('branding.company_name') }}" class="loader-logo">
    </div>

    <div class="content-area">
        <main>
            {{ $slot }}
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('static/js/script.js') }}"></script>
</body>
</html>