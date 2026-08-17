@extends('layouts.role')
@section('title', 'Detail Terima Stok - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-3">
                        <i class="bi bi-box-arrow-in-right me-2 text-primary"></i>
                        Detail {{ $source === 'ambil_bahan_gudang' ? 'Ambil Bahan Gudang' : 'Terima Stok' }}
                    </h3>
                    <div class="mb-3">
                        @if($record->status === 'pending')
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Diterima</span>
                        @endif
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label text-muted small fw-medium mb-1">Tanggal Pengiriman</label>
                            <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($record->tanggal)->format('d-m-Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">
                                {{ $source === 'ambil_bahan_gudang' ? 'Barista' : 'Manager' }}
                            </label>
                            <p class="mb-0 fw-bold">{{ $record->pelaku }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Sumber Transaksi</label>
                            <p class="mb-0">
                                @if($source === 'ambil_bahan_gudang')
                                    <span class="badge bg-info text-dark">Ambil Bahan Gudang</span>
                                @else
                                    <span class="badge bg-secondary">Gudang Kirim Stok</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Waktu Dibuat</label>
                            <p class="mb-0">{{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('H:i:s') : '-' }}</p>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-box-seam me-2"></i>Item Bahan Baku yang {{ $source === 'ambil_bahan_gudang' ? 'Diambil' : 'Dikirim' }}
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">No</th>
                                    <th>Nama Bahan</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($record->items as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item->bahan->nama }}</td>
                                    <td class="text-end">{{ $item->jumlah }}</td>
                                    <td>{{ $item->bahan->satuan }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada item</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top p-4 text-end">
                    <a href="{{ route('headbar.coffee-shop.terima-stok.index') }}" class="btn btn-secondary px-4">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
