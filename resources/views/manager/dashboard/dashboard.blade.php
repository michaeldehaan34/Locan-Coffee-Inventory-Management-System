@extends('layouts.role')
@section('title', 'Dashboard Manajemen - ' . config('branding.app_name'))
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


    <!-- GLOBAL STOCK -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3 text-center">
                        <i class="bi bi-box-seam me-2 text-primary"></i>
                        GLOBAL STOCK
                    </h5>
                    <p class="text-muted small mb-3 text-center">Nilai stok terkini setiap bahan baku.</p>
                    @if(count($global_stock) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Bahan</th>
                                    <th>Stok Terkini</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($global_stock as $item)
                                <tr>
                                    <td>{{ $item['nama'] }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $item['stok'] }}</span> {{ $item['satuan'] }}
                                    </td>
                                    <td>
                                        @if($item['status'] === 'aman')
                                            <span class="badge bg-success">Aman</span>
                                        @elseif($item['status'] === 'tipis')
                                            <span class="badge bg-warning text-dark">Tipis</span>
                                        @else
                                            <span class="badge bg-danger">Habis</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada data stok global.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>


    @endif
</div>
@endsection