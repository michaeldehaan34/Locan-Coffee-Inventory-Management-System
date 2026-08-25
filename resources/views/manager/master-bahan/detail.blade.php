@extends('layouts.role')
@section('title', $title ?? ('Detail Barang - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>Detail Barang
                    </h2>
                    <a href="{{ route('gudang.master-bahan') }}" class="btn btn-outline-light btn-sm">
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
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 200px;">Kode</td>
                            <td><code>{{ $bahan->kode }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Barang</td>
                            <td><strong>{{ $bahan->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kategori</td>
                            <td>{{ $bahan->kategori }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelompok</td>
                            <td>{{ $bahan->kelompok }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Satuan</td>
                            <td>{{ $bahan->satuan }}</td>
                        </tr>

                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if ($bahan->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Dibuat</td>
                            <td>{{ $bahan->created_at ? $bahan->created_at->format('d-m-Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Terakhir Diperbarui</td>
                            <td>{{ $bahan->updated_at ? $bahan->updated_at->format('d-m-Y H:i') : '-' }}</td>
                        </tr>
                    </table>

                    <hr class="border-secondary">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('gudang.master-bahan.edit', $bahan->id) }}" class="btn btn-outline-light">
                            <i class="bi bi-pencil me-1"></i>Edit Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

