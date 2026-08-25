@extends('layouts.role')
@section('title', $title ?? ('Detail Update Stok - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-3">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        Detail Update Stok
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
                                {{ $record->user ? $record->user->nama_lengkap : ($record->barista ?: 'User tidak tercatat') }}<br>
                                <span class="text-muted small fw-normal">{{ $record->user ? \Illuminate\Support\Str::title($record->user->role) : ($record->inventory_type === 'kitchen' ? 'Kitchen' : 'Barista') }}</span>
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
                            <label class="form-label text-muted small fw-medium mb-1">Tanggal Berlaku Stok</label>
                            <p class="mb-0">{{ $tanggal ?? ($record->tanggal ? $record->tanggal->format('d F Y') : '-') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Shift</label>
                            <p class="mb-0">{{ $record->shift }}</p>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2 mt-4">
                        <i class="bi bi-box-seam me-2"></i>DETAIL
                    </h5>

                    @if (count($items) > 0)
                        <div class="table-responsive mb-4 shadow-sm rounded border">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50" class="text-center py-3">No</th>
                                        <th class="py-3">Bahan</th>
                                        <th class="py-3">Kategori</th>
                                        <th class="text-end py-3">Jumlah</th>
                                        <th class="py-3">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $index => $item)
                                        <tr>
                                            <td class="text-center py-3">{{ $index + 1 }}</td>
                                            <td class="py-3 fw-medium text-dark">{{ $item['nama'] }}</td>
                                            <td class="py-3">
                                                <span class="badge bg-light text-secondary border">{{ $item['kategori'] }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-primary py-3">{{ $item['nilai'] }}</td>
                                            <td class="py-3 text-muted">{{ $item['satuan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">Tidak ada item update stok.</p>
                    @endif
                </div>
                
                <div class="card-footer bg-white border-top p-4 text-end">
                    <a href="{{ route('headkitchen.riwayat.update-stok') }}" class="btn btn-secondary px-4">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

