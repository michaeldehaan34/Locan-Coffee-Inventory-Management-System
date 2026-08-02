@extends('layouts.role')
@section('title', 'Riwayat Daily Clean - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0"><i class="bi bi-camera-reels me-2"></i>Riwayat Daily Clean</h2>
                    <a href="{{ route('manager.export.daily-clean') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <label class="form-label small text-muted">Tanggal</label>
                <input type="date" class="form-control" name="tanggal" value="{{ $filter_tanggal }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Shift</label>
                <select class="form-select" name="shift">
                    <option value="">Semua</option>
                    @foreach ($shift_list as $s)
                        <option value="{{ $s }}" @if ($filter_shift == $s) selected @endif>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Barista</label>
                <input type="text" class="form-control" name="barista" placeholder="Nama barista..." value="{{ $filter_barista }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-light w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>

    <!-- Bulk Delete Button -->
    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" disabled onclick="openBulkDeleteModal()">
                <i class="bi bi-trash3 me-1"></i>Hapus Terpilih
            </button>
            <span class="small text-muted ms-2" id="selectedCount">0 dipilih</span>
        </div>
    </div>

    <!-- Tabel -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <form id="bulkDeleteForm" method="POST" action="{{ route('manager.riwayat.daily-clean.bulk-delete') }}">
                            @csrf
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 4%;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                                <label class="form-check-label" for="selectAll"></label>
                                            </div>
                                        </th>
                                        <th style="width: 5%;">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Barista</th>
                                        <th>Jumlah Foto</th>
                                        <th style="width: 18%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($records as $i => $rec)
                                        @php $no = $i + 1; @endphp
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox" name="ids[]" value="{{ $rec['id'] }}" onchange="updateBulkDeleteButton()">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                            <td>{{ $no }}</td>
                                            <td>{{ $rec['tanggal'] }}</td>
                                            <td>{{ $rec['shift'] }}</td>
                                            <td>{{ $rec['barista'] }}</td>
                                            <td>{{ $rec['jumlah_foto'] }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('manager.daily-clean.detail', $rec['id']) }}" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye me-1"></i>Detail
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openSingleDeleteModal({{ $rec['id'] }}, '{{ $rec['tanggal'] }}', '{{ $rec['shift'] }}', '{{ addslashes($rec['barista']) }}', {{ $rec['jumlah_foto'] }})">
                                                        <i class="bi bi-trash3 me-1"></i>Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Belum ada data Daily Clean.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Foto -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-images me-2"></i>Foto Daily Clean</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Tanggal:</strong> <span id="mTanggal">-</span></div>
                    <div class="col-md-4"><strong>Shift:</strong> <span id="mShift">-</span></div>
                    <div class="col-md-4"><strong>Barista:</strong> <span id="mBarista">-</span></div>
                </div>
                <div class="row g-3" id="mPhotos">
                    <div class="col-12 text-center text-muted">Memuat foto...</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
function openPhotos(id) {
    document.getElementById('mTanggal').textContent = '-';
    document.getElementById('mShift').textContent = '-';
    document.getElementById('mBarista').textContent = '-';
    document.getElementById('mPhotos').innerHTML = '<div class="col-12 text-center text-muted">Memuat foto...</div>';
    fetch("{{ url('/manager/riwayat/daily-clean/detail') }}/" + id)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                document.getElementById('mPhotos').innerHTML = '<div class="col-12 text-center text-danger">Data tidak ditemukan.</div>';
                return;
            }
            document.getElementById('mTanggal').textContent = data.tanggal;
            document.getElementById('mShift').textContent = data.shift;
            document.getElementById('mBarista').textContent = data.barista;
            var box = document.getElementById('mPhotos');
            box.innerHTML = '';
            if (!data.photos.length) {
                box.innerHTML = '<div class="col-12 text-center text-muted">Tidak ada foto.</div>';
                return;
            }
            data.photos.forEach(function (p) {
                var col = document.createElement('div');
                col.className = 'col-6 col-md-4';
                col.innerHTML =
                    '<a href="' + p.url + '" target="_blank"><img src="' + p.url + '" class="img-fluid rounded border" style="object-fit:cover;height:160px;width:100%;" alt="' + p.name + '"></a>' +
                    '<div class="small text-muted text-truncate mt-1">' + p.name + '</div>';
                box.appendChild(col);
            });
        })
        .catch(function () {
            document.getElementById('mPhotos').innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat foto.</div>';
        });
}

// =============================================
// Single Delete - SweetAlert2
// =============================================

function openSingleDeleteModal(id, tanggal, shift, barista, jumlahFoto) {
    var message = 'Anda yakin ingin menghapus Daily Clean berikut?\n\nTanggal: ' + tanggal + '\nShift: ' + shift + '\nBarista: ' + barista + '\nJumlah Foto: ' + jumlahFoto + '\n\nMenghapus data ini juga akan menghapus seluruh file foto yang tersimpan.';

    swalConfirm({
        text: message,
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ url('/manager/riwayat/daily-clean/hapus') }}/" + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: (function () {
                    var fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('_method', 'DELETE');
                    return fd;
                })()
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            }).then(function (data) {
                if (data.success) {
                    showToast('success', data.message || 'Daily Clean berhasil dihapus.');
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus.');
                }
            }).catch(function () {
                // Fallback: submit form biasa
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ url('/manager/riwayat/daily-clean/hapus') }}/" + id;
                var csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                var method = document.createElement('input');
                method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            });
        }
    });
}

// =============================================
// Bulk Delete - SweetAlert2
// =============================================

function updateBulkDeleteButton() {
    var checked = document.querySelectorAll('.row-checkbox:checked');
    var btn = document.getElementById('bulkDeleteBtn');
    var countSpan = document.getElementById('selectedCount');

    if (checked.length > 0) {
        btn.disabled = false;
        countSpan.textContent = checked.length + ' dipilih';
    } else {
        btn.disabled = true;
        countSpan.textContent = '0 dipilih';
    }
}

function toggleSelectAll(source) {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(function (cb) {
        cb.checked = source.checked;
    });
    updateBulkDeleteButton();
}

function openBulkDeleteModal() {
    var checked = document.querySelectorAll('.row-checkbox:checked');

    if (checked.length === 0) {
        showToast('danger', 'Pilih minimal satu data terlebih dahulu.');
        return;
    }

    swalConfirm({
        text: 'Anda akan menghapus ' + checked.length + ' data Daily Clean. Semua foto yang berkaitan juga akan ikut dihapus.',
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ route('manager.riwayat.daily-clean.bulk-delete') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: (function () {
                    var fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    checked.forEach(function (cb) {
                        fd.append('ids[]', cb.value);
                    });
                    return fd;
                })()
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            }).then(function (data) {
                if (data.success) {
                    showToast('success', data.message || 'Data berhasil dihapus.');
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus.');
                }
            }).catch(function () {
                // Fallback: submit form biasa
                document.getElementById('bulkDeleteForm').submit();
            });
        }
    });
}
</script>
@endpush
@endsection

