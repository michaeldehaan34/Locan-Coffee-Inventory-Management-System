@extends('layouts.role')

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>
                        Laporan
                    </h2>
                    <p class="text-muted mb-0">Pilih rentang tanggal untuk membuat laporan stok.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Filter Laporan -->
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('manager.laporan') }}">
                        <div class="mb-3">
                            <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" id="tanggal_awal"
                                   name="tanggal_awal" value="{{ $tanggal_awal ?? '' }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tanggal_akhir"
                                   name="tanggal_akhir" value="{{ $tanggal_akhir ?? '' }}" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-gear-wide-connected me-2"></i>
                                Generate Laporan
                            </button>
                            @if($summary)

                            <button type="button" class="btn btn-outline-light" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>
                                Print
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Laporan -->
    @if($summary)
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-clipboard-data me-2"></i>
                Ringkasan Laporan &mdash; {{ $summary['periode_label'] }}
            </h4>
        </div>
    </div>

    <!-- Ringkasan Kartu (selalu tampil, data dari Update Stok) -->
    <div class="row g-4 mb-4">
        <!-- Total Update Stok -->
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Total Update</h6>
                    <h2 class="mb-0 fw-bold text-blue">{{ $summary['total_update_stok'] }}</h2>
                    <small class="text-muted">kali update</small>
                </div>
            </div>
        </div>

        <!-- Barang Aman -->
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Barang Aman</h6>
                    <h2 class="mb-0 fw-bold text-success">{{ $summary['barang_aman'] }}</h2>
                    <small class="text-muted">item stok > 2</small>
                </div>
            </div>
        </div>

        <!-- Barang Tipis -->
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Barang Tipis</h6>
                    <h2 class="mb-0 fw-bold text-amber">{{ $summary['barang_tipis'] }}</h2>
                    <small class="text-muted">item stok > 0 dan &le; 2</small>
                </div>
            </div>
        </div>

        <!-- Barang Habis -->
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Barang Habis</h6>
                    <h2 class="mb-0 fw-bold text-rose">{{ $summary['barang_habis'] }}</h2>
                    <small class="text-muted">item stok kosong / &le; 0</small>
                </div>
            </div>
        </div>
    </div>

    @if($summary['has_data'])
    <!-- 1. Top 10 Barang Paling Sering Habis -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-exclamation-octagon-fill me-2 text-rose"></i>
                10 Barang Teratas Yang Paling Sering Habis
            </h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if(count($summary['top_barang_habis']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Rank</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 18%;">Jumlah Habis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['top_barang_habis'] as $item)
                                <tr>
                                    <td>{{ $item['rank'] }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td><span class="badge bg-danger">{{ $item['jumlah'] }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Tidak ada barang yang tercatat habis pada periode ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Top 10 Barang Hampir Habis -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2 text-amber"></i>
                10 Barang Teratas Yang Hampir Habis
            </h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if(count($summary['top_barang_tipis']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Rank</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 18%;">Jumlah Tipis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['top_barang_tipis'] as $item)
                                <tr>
                                    <td>{{ $item['rank'] }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ $item['jumlah'] }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Tidak ada barang yang tercatat hampir habis pada periode ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Aktivitas Barista -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-people-fill me-2 text-teal"></i>
                Aktivitas Barista
            </h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if(count($summary['aktivitas_barista']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">No</th>
                                    <th>Nama Barista</th>
                                    <th style="width: 22%;">Jumlah Update Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['aktivitas_barista'] as $item)
                                <tr>
                                    <td>{{ $item['no'] }}</td>
                                    <td>{{ $item['nama_barista'] }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $item['jumlah'] }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Tidak ada aktivitas barista tercatat pada periode ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Forecast & Estimasi Pembelian -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-graph-up me-2 text-teal"></i>
                Forecast & Estimasi Pembelian
            </h5>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-4 mb-3">
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 stat-card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Total Kebutuhan</h6>
                                    <h3 class="mb-0 fw-bold text-amber">{{ $summary['total_kebutuhan'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 stat-card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Total Estimasi Pembelian</h6>
                                    <h3 class="mb-0 fw-bold text-teal">{{ $summary['total_estimasi_pembelian'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(count($summary['forecast_items']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Nama Barang</th>
                                    <th>Stok Sekarang</th>
                                    <th>Forecast Kebutuhan</th>
                                    <th>Estimasi Pembelian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['forecast_items'] as $item)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td>{{ $item['stok_sekarang'] }}</td>
                                    <td>{{ $item['kebutuhan'] }}</td>
                                    <td>{{ $item['estimasi_pembelian'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Tidak ada data forecast untuk periode ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection

