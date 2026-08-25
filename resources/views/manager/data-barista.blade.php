@extends('layouts.role')
@section('title', 'Data Karyawan - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-people me-2"></i>Data Karyawan
                    </h2>
<div>
                        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-light btn-sm me-2">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                        <a href="{{ route('manager.data-barista.create') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Karyawan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control"
                       placeholder="Cari Nama atau Nomor Telepon...">
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="baristaTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Nomor Telepon</th>
                                    <th>Role</th>
                                    <th>Tanggal Dibuat</th>
                                    <th style="width: 18%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($barista_list as $i => $b)
                                    @php $no = $i + 1; @endphp
                                    <tr data-nama="{{ strtolower($b['nama_lengkap']) }}" data-telp="{{ strtolower($b['no_telp']) }}">
                                        <td>{{ $no }}</td>
                                        <td>{{ $b['nama_lengkap'] }}</td>
                                        <td>{{ $b['no_telp'] }}</td>
                                        <td>
                                            @if ($b['role'] == 'manajemen')
                                                <span class="badge bg-warning text-dark">Manajemen</span>
                                            @elseif ($b['role'] == 'barista')
                                                <span class="badge bg-secondary">Barista</span>
                                            @else
                                                <span class="badge bg-info text-dark">{{ ucwords($b['role']) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $b['created_at'] }}</td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a href="{{ route('manager.data-barista.detail', $b['id']) }}"
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                                <a href="{{ route('manager.data-barista.edit.form', $b['id']) }}"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger btn-delete"
                                                        data-id="{{ $b['id'] }}"
                                                        data-nama="{{ $b['nama_lengkap'] }}"
                                                        @if ($b['id'] == $current_user_id) disabled title="Tidak dapat menghapus akun sendiri" @endif>
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada data karyawan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <p id="noResult" class="text-muted text-center py-3 mb-0 d-none">Tidak ada data yang cocok.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('manager.data-barista.add') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="no_telp" class="form-control" required>
                        <small class="text-muted">Password otomatis = 6 digit terakhir nomor telepon.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="barista">Barista</option>
                            <option value="manajemen">Manajemen</option>
                            <option value="headbar">Headbar</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="headkitchen">Head Kitchen</option>
                            <option value="admin gudang">Admin Gudang</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-light">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form hapus tersembunyi -->
<form id="deleteForm" method="POST" action="#" style="display:none;">
    @csrf
</form>

@push('extra_js')
<script>
(function () {
    var input = document.getElementById('searchInput');
    var rows = document.querySelectorAll('#baristaTable tbody tr');
    var noResult = document.getElementById('noResult');
    if (input) {
        input.addEventListener('keyup', function () {
            var q = input.value.toLowerCase().trim();
            var visible = 0;
            rows.forEach(function (row) {
                var nama = row.getAttribute('data-nama') || '';
                var telp = row.getAttribute('data-telp') || '';
                var show = (nama.indexOf(q) !== -1) || (telp.indexOf(q) !== -1);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noResult) noResult.classList.toggle('d-none', visible !== 0);
        });
    }

    var deleteForm = document.getElementById('deleteForm');
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            var id = btn.getAttribute('data-id');
            var nama = btn.getAttribute('data-nama');
            swalConfirm({
                text: 'Yakin ingin menghapus karyawan "' + nama + '"?',
                onConfirm: function () {
                    deleteForm.action = "{{ url('/manager/data-barista/hapus') }}/" + id;
                    deleteForm.submit();
                }
            });
        });
    });
})();
</script>
@endpush
@endsection