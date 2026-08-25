@extends('layouts.role')
@section('title', 'Detail Kirim Stok - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-3">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                        Detail {{ $source === 'ambil_bahan_gudang' ? 'Ambil Bahan Gudang' : 'Kirim Stok' }}
                    </h3>
                </div>
                
                <div class="card-body p-4">
                    <h5 class="mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-info-circle me-2"></i>INFORMASI
                    </h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label text-muted small fw-medium mb-1">Diinput oleh</label>
                            <p class="mb-0 fw-bold">
                                {{ $record->pelaku }}<br>
                                <span class="text-muted small fw-normal">{{ $record->pelaku_role ?? ($source === 'ambil_bahan_gudang' ? 'Barista' : 'Admin Gudang') }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Waktu Input</label>
                            <p class="mb-0 fw-bold">
                                {{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}
                            </p>
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
                            <label class="form-label text-muted small fw-medium mb-1">Tujuan</label>
                            <p class="mb-0 fw-bold">
                                @if($record->tujuan === 'coffee_shop' || $record->inventory_type === 'coffee_shop' || ($source === 'ambil_bahan_gudang' && empty($record->inventory_type)))
                                    Coffeeshop
                                @elseif($record->tujuan === 'kitchen' || $record->inventory_type === 'kitchen')
                                    Kitchen
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2 mt-4">
                        <i class="bi bi-box-seam me-2"></i>DETAIL
                    </h5>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">No</th>
                                    <th>Bahan</th>
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
                    
                    <h5 class="mb-3 text-primary border-bottom pb-2 mt-4">
                        <i class="bi bi-check-circle me-2"></i>STATUS
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Status</label>
                            <p class="mb-0">
                                @if($record->status === 'pending')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Diterima</span>
                                @endif
                            </p>
                        </div>
                        @if($record->status === 'diterima' && $source === 'gudang_kirim')
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Waktu Diterima</label>
                            <p class="mb-0 fw-bold">
                                @if($record->received_at)
                                    {{ \Carbon\Carbon::parse($record->received_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' }}
                                @else
                                    {{ \Carbon\Carbon::parse($record->updated_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-medium mb-1">Diterima oleh</label>
                            <p class="mb-0 fw-bold">
                                @if($record->receiver)
                                    {{ $record->receiver->nama_lengkap }} — {{ $record->receiver->role }}
                                @else
                                    Sistem (Legacy)
                                @endif
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top p-4 text-end">
                    <a href="{{ route('gudang.kirim-stok.index') }}" class="btn btn-secondary px-4">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
