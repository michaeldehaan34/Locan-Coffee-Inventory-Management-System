@extends('layouts.role')
@section('title', $title ?? ('Detail Stok Masuk - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Detail Stok Masuk
                    </h2>
                    <a href="{{ route('gudang.stok-masuk.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Tanggal</small>
                            <strong>{{ $record->tanggal ? $record->tanggal->format('Y-m-d') : '-' }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Shift</small>
                            <strong>{{ $record->shift }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Barista</small>
                            <strong>{{ $record->barista }}</strong>
                        </div>
                    </div>

                    <hr class="border-secondary mb-3">

                    <h6 class="mb-3"><i class="bi bi-list-ul me-2"></i>Daftar Barang</h6>

                    @if (count($items) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Kelompok</th>
                                        <th>Kategori</th>
                                        <th style="width: 15%;">Jumlah</th>
                                        <th style="width: 15%;">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item['nama'] }}</td>
                                            <td>{{ $item['kelompok'] }}</td>
                                            <td>{{ $item['kategori'] }}</td>
                                            <td>{{ $item['nilai'] }}</td>
                                            <td>{{ $item['satuan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">Tidak ada item stok masuk.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

