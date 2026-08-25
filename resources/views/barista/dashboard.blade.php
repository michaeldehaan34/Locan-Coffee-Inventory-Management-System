@extends('layouts.role')
@section('title', 'Dashboard Barista - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-speedometer2 me-2 text-teal"></i>
                        Dashboard
                    </h2>
                    <p class="text-muted mb-0">Halo, {{ $baristaName }}!!!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Aksi Cepat -->
    <div class="row g-4">


        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('barista.ambil-bahan-gudang') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card">
                    <div class="card-body text-center">
                        <div class="icon-box icon-purple mx-auto mb-3">
                            <i class="bi bi-box-arrow-down" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-0">Ambil Bahan Gudang</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('barista.update-stok') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card">
                    <div class="card-body text-center">
                        <div class="icon-box icon-amber mx-auto mb-3">
                            <i class="bi bi-arrow-repeat" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-0">Update Stok</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('barista.daily-clean') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card">
                    <div class="card-body text-center">
                        <div class="icon-box icon-info mx-auto mb-3">
                            <i class="bi bi-camera" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-0">Daily Clean</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('barista.token-listrik') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card">
                    <div class="card-body text-center">
                        <div class="icon-box icon-rose mx-auto mb-3">
                            <i class="bi bi-lightning-charge" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-0">Token Listrik</h6>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection