@extends('layouts.role')
@section('title', $title ?? ('Master Barang - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>Master Barang
                    </h2>
                    <div>
                        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-light btn-sm me-2">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                        <a href="{{ route('gudang.master-bahan.create') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('gudang.master-bahan') }}" class="d-flex gap-2">
                <div class="input-group flex-grow-1">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari Kode atau Nama Barang..."
                           value="{{ $search ?? '' }}" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-outline-light">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                @if (!empty($search))
                    <a href="{{ route('gudang.master-bahan') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bahanTable">
                            <thead>
                                <tr>
                                    <th style="width: 4%;">No</th>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Kelompok</th>
                                    <th>Satuan</th>
                                    <th>Status</th>
                                    <th style="width: 22%;">Aksi</th>
                                </tr>
                            </thead>
                                <tbody>
                                @forelse ($bahan_list as $b)
                                    <tr>
                                        <td>{{ $loop->iteration + ($bahan_list->currentPage() - 1) * $bahan_list->perPage() }}</td>
                                        <td><code>{{ $b->kode }}</code></td>
                                        <td>{{ $b->nama }}</td>
                                        <td>{{ $b->kategori }}</td>
                                        <td>{{ $b->kelompok }}</td>
                                        <td>{{ $b->satuan }}</td>
                                        <td>
                                            @if ($b->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
<td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('gudang.master-bahan.detail', $b->id) }}"
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                                <a href="{{ route('gudang.master-bahan.edit', $b->id) }}"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <form method="POST"
                                                      action="{{ route('gudang.master-bahan.toggle', $b->id) }}"
                                                      class="d-inline form-toggle"
                                                      data-nama="{{ $b->nama }}"
                                                      data-aksi="{{ $b->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                                    @csrf
                                                    @if ($b->is_active)
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="bi bi-eye-slash"></i> Nonaktifkan
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-eye"></i> Aktifkan
                                                        </button>
                                                    @endif
                                                </form>
                                                <form method="POST"
                                                      action="{{ route('gudang.master-bahan.delete', $b->id) }}"
                                                      class="d-inline form-delete"
                                                      data-nama="{{ $b->nama }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada data barang.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                        </table>
                    </div>

                    @if ($bahan_list->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <span class="text-muted small">
                                Menampilkan {{ $bahan_list->firstItem() }}–{{ $bahan_list->lastItem() }}
                                dari {{ $bahan_list->total() }} data
                            </span>
                            {{ $bahan_list->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
(function () {
    document.querySelectorAll('form.form-toggle').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var nama = form.getAttribute('data-nama') || '';
            var aksi = form.getAttribute('data-aksi') || 'mengubah status';
            swalConfirm({
                text: 'Yakin ingin ' + aksi + ' bahan "' + nama + '"?',
                confirmButtonColor: '#DC3545',
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('form.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var nama = form.getAttribute('data-nama') || '';
            swalConfirm({
                text: 'Yakin ingin menghapus bahan "' + nama + '"? Data historis kolom tersebut akan hilang.',
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });
})();
</script>
@endpush
@endsection
