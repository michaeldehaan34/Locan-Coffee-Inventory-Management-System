@extends('layouts.role')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm text-center">
                <div class="card-body py-5">
                    <i class="bi bi-tools text-muted mb-4" style="font-size: 4rem;"></i>
                    <h2 class="fw-bold mb-3">Dashboard Segera Hadir</h2>
                    <p class="text-muted mb-4">
                        Halo <strong>{{ session('name') }}</strong>! 
                        <br>
                        Dashboard untuk role <strong>{{ ucwords(session('role')) }}</strong> saat ini sedang dalam tahap pengembangan.
                    </p>
                    <a href="{{ route('logout') }}" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="btn btn-primary">
                        Kembali ke Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
