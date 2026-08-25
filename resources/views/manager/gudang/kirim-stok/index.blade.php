@extends('layouts.role')
@section('title', 'Riwayat Kirim Stok - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div>
                        <h2 class="mb-0">
                            <i class="bi bi-truck me-2 text-teal"></i>
                            Riwayat Kirim Stok
                        </h2>
                        <p class="text-muted mb-0">Daftar pengiriman barang dari Gudang ke Coffeeshop.</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="{{ route('gudang.kirim-stok.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Kirim Stok
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL RIWAYAT -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="50">No</th>
                                    <th>Waktu Input</th>
                                    <th>Sumber</th>
                                    <th>Diinput Oleh</th>
                                    <th>Jumlah Item</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $records->firstItem() + $index }}</td>
                                    <td>
                                        <div>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</div>
                                        <small class="text-muted">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('H:i') : '' }}</small>
                                    </td>
                                    <td>
                                        @if($row->source === 'ambil_bahan_gudang')
                                            <span class="badge bg-info text-dark">Ambil Bahan Gudang</span>
                                        @else
                                            <span class="badge bg-secondary">Gudang Kirim Stok</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            @if($row->source === 'ambil_bahan_gudang')
                                                <i class="bi bi-person-badge me-1 text-muted"></i>
                                            @else
                                                <i class="bi bi-person-workspace me-1 text-muted"></i>
                                            @endif
                                            {{ $row->pelaku }}
                                        </div>
                                        @if($row->pelaku_role)
                                            <small class="text-muted text-capitalize">{{ $row->pelaku_role }}</small>
                                        @endif
                                    </td>
                                    <td>{{ count($row->items) }} Item</td>
                                    <td>
                                        @if($row->status === 'pending')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                        @else
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Diterima</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('gudang.kirim-stok.detail', ['id' => $row->id, 'source' => $row->source]) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($row->source !== 'ambil_bahan_gudang')
                                        <a href="{{ route('gudang.kirim-stok.edit', $row->id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('gudang.kirim-stok.delete', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data pengiriman stok.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($records->hasPages())
                <div class="card-footer bg-white border-top-0 pt-3 pb-0">
                    <div class="d-flex justify-content-center">
                        {{ $records->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
