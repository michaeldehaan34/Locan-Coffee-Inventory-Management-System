@extends('layouts.role')
@section('title', 'Coming Soon - ' . config('branding.app_name'))
@section('content')
<div class="page-container d-flex flex-column justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="text-center">
        <i class="bi bi-tools text-muted mb-3" style="font-size: 4rem;"></i>
        <h2 class="mb-3">Coming Soon</h2>
        <p class="text-muted mb-4">Halaman dashboard untuk role ini sedang dalam tahap pengembangan.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-primary px-4">Kembali / Logout</button>
        </form>
    </div>
</div>
@endsection
