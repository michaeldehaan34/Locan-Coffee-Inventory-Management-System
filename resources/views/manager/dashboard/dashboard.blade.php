@extends('layouts.role')
@section('title', 'Dashboard Manager - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-speedometer2 me-2 text-teal"></i>
                        Dashboard {{ config('branding.app_name') }} {{ config('branding.subtitle') }}
                    </h2>
                    <p class="text-muted mb-0">Selamat datang, {{ $managerName }}! Berikut Ringkasan Stock {{ config('branding.company_name') }}.</p>
                </div>
            </div>
        </div>
    </div>

    @if(!$has_data)
    <!-- Belum ada data -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div class="empty-title">Belum ada data</div>
                        <div class="empty-text">Mulai catat update stok untuk menampilkan ringkasan di sini.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else

    <!-- 1. Ringkasan Statistik -->
    <div class="row g-4 mb-4">
        <!-- Barang Aman -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Aman
                            </h6>
                            <h2 class="mb-0 fw-bold text-success">{{ $bahan_aman }}</h2>
                            <small class="text-muted">item stok > {{ $limit_tipis }}</small>
                        </div>
                        <div class="icon-box icon-success">
                            <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barang Tipis -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Tipis
                            </h6>
                            <h2 class="mb-0 fw-bold text-amber">{{ $bahan_tipis }}</h2>
                            <small class="text-muted">item stok > {{ $limit_habis }} dan &le; {{ $limit_tipis }}</small>
                        </div>
                        <div class="icon-box icon-amber">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barang Habis -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                Barang Habis
                            </h6>
                            <h2 class="mb-0 fw-bold text-rose">{{ $bahan_habis }}</h2>
                            <small class="text-muted">item stok kosong / &le; {{ $limit_habis }}</small>
                        </div>
                        <div class="icon-box icon-rose">
                            <i class="bi bi-x-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 & 3. Top Barang Paling Sering Habis & Hampir Habis -->
    <div class="row g-4 mb-4">
        <!-- Top Barang Sering Habis -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="bi bi-exclamation-octagon-fill me-2 text-rose"></i>
                        Barang Yang Paling Sering Habis
                    </h5>
                    @if(count($top_barang_habis) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Rank</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 22%;">Jumlah Habis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_barang_habis as $item)
                                <tr>
                                    <td>{{ $item['rank'] }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td><span class="badge bg-danger">{{ $item['jumlah'] }}x</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada data barang habis.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Barang Hampir Habis -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-amber"></i>
                        Barang Yang Hampir Habis
                    </h5>
                    @if(count($top_barang_tipis) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Rank</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 22%;">Jumlah Tipis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_barang_tipis as $item)
                                <tr>
                                    <td>{{ $item['rank'] }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ $item['jumlah'] }}x</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada data barang tipis.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Aktivitas Barista -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="bi bi-people-fill me-2 text-teal"></i>
                        Aktivitas Barista
                    </h5>
                    @if(count($top_aktivitas_barista) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No</th>
                                    <th>Nama Barista</th>
                                    <th style="width: 24%;">Jumlah Update Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_aktivitas_barista as $item)
                                <tr>
                                    <td>{{ $item['no'] }}</td>
                                    <td>{{ $item['nama_barista'] }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $item['jumlah'] }} update</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada aktivitas barista tercatat.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection