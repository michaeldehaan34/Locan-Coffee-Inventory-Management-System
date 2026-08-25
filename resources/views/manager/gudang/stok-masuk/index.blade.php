@extends('layouts.role')
@section('title', 'Riwayat Stok Masuk - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Riwayat Stok Masuk
                    </h2>
                    <a href="{{ route('gudang.stok-masuk.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Stok Masuk
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Card -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Item</h6>
                    <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Hari Ini</h6>
                    <h3 class="mb-0 fw-bold text-teal">{{ $stats['today'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">7 Hari</h6>
                    <h3 class="mb-0 fw-bold text-amber">{{ $stats['week'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">30 Hari</h6>
                    <h3 class="mb-0 fw-bold text-blue">{{ $stats['month'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Stok Masuk</h5>
                    </div>
                    <hr class="border-secondary mb-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="riwayatTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Tanggal</th>
                                    <th>Shift</th>
                                    <th>Barista</th>
                                    <th>Jumlah Item</th>
                                    <th style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                    <tr>
                                        <td>{{ ($paginator->currentPage() - 1) * $paginator->perPage() + $i + 1 }}</td>
                                        <td>{{ $row['tanggal_display'] }}</td>
                                        <td>{{ $row['shift'] }}</td>
                                        <td>{{ $row['barista'] }}</td>
                                        <td>{{ $row['jumlah_item'] }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('gudang.stok-masuk.edit', $row['id']) }}" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="{{ route('gudang.stok-masuk.detail', $row['id']) }}" class="btn btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>
                                                <form method="POST" action="{{ route('gudang.stok-masuk.delete', $row['id']) }}" class="d-inline form-delete" data-tanggal="{{ $row['tanggal_display'] }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center text-muted">
                                                    <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                                                    <p class="mt-2 mb-0">Tidak ada data yang sesuai dengan pencarian.</p>
                                                    @if ($filter_tgl_awal || $filter_tgl_akhir || $filter_shift || $filter_barista || $filter_barang)
                                                    <small>Coba ubah atau reset filter pencarian Anda.</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($paginator->hasPages())
                    <nav class="mt-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="small text-muted">
                                Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }} ({{ $paginator->total() }} data)
                            </span>
                            {{ $paginator->onEachSide(1)->links() }}
                        </div>
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Detail Stok Masuk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Diinput oleh:</small>
                        <strong id="dBarista"></strong> <span id="dBaristaRole"></span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Waktu:</small>
                        <strong id="dWaktu"></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Shift:</small>
                        <strong id="dShift"></strong>
                    </div>
                </div>
                <hr class="border-secondary mb-3">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Daftar Barang</th><th>Kelompok</th><th>Kategori</th><th style="width: 15%;">Jumlah</th><th style="width: 15%;">Satuan</th></tr>
                        </thead>
                        <tbody id="dItems"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
var ALL_RECORDS = @json($records);
document.querySelectorAll('form.form-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var tanggal = form.getAttribute('data-tanggal') || '';
        swalConfirm({
            text: 'Yakin ingin menghapus data stok masuk tanggal "' + tanggal + '"?',
            onConfirm: function () {
                form.submit();
            }
        });
    });
});
function openDetail(id) {
    var rec = ALL_RECORDS.find(function (r) { return r.id === id; });
    if (!rec) return;
    document.getElementById('dBarista').textContent = rec.barista;
    document.getElementById('dBaristaRole').innerHTML = rec.barista_role ? '&mdash; <span class="text-capitalize">' + rec.barista_role + '</span>' : '';
    document.getElementById('dWaktu').textContent = rec.waktu_wib;
    document.getElementById('dShift').textContent = rec.shift;
    var tb = document.getElementById('dItems');
    tb.innerHTML = '';
    if (!rec.items || !rec.items.length) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="3" class="text-muted">Tidak ada item.</td>';
        tb.appendChild(tr);
        return;
    }
    rec.items.forEach(function (it) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + it.label + '</td><td>' + (it.kelompok || '-') + '</td><td>' + (it.kategori || '-') + '</td><td>' + it.jumlah + '</td><td>' + it.satuan + '</td>';
        tb.appendChild(tr);
    });
}
</script>
@endpush
@endsection
