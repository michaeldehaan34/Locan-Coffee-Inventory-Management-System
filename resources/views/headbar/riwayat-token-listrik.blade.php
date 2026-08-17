@extends('layouts.role')
@section('title', 'Riwayat Token Listrik - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0"><i class="bi bi-lightning-charge-fill me-2"></i>Riwayat Token Listrik</h2>
                    <div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small text-muted">Tanggal Awal</label>
                <input type="date" class="form-control" name="tgl_awal" value="{{ $filter_tgl_awal }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Tanggal Akhir</label>
                <input type="date" class="form-control" name="tgl_akhir" value="{{ $filter_tgl_akhir }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Shift</label>
                <input type="text" class="form-control" name="shift" placeholder="Nama shift..." value="{{ $filter_shift }}">
            </div>
            <div class="col-md-2">
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
                        <form id="bulkDeleteForm" method="POST" action="{{ route('headbar.riwayat.token-listrik.bulk-delete') }}">
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
                                        <th style="width: 25%;">Token Listrik (kWh)</th>
                                        <th style="width: 12%;">Aksi</th>
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
                                            <td>{{ format_kwh($rec['token_listrik_total']) }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openSingleDeleteModal({{ $rec['id'] }}, '{{ $rec['tanggal'] }}', '{{ $rec['shift'] }}', '{{ addslashes($rec['barista']) }}')">
                                                        <i class="bi bi-trash3 me-1"></i>Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">Belum ada data Token Listrik.</td>
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

@push('extra_js')
<script>
// =============================================
// Update Bulk Delete Button
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

// =============================================
// Single Delete - SweetAlert2
// =============================================
function openSingleDeleteModal(id, tanggal, shift, barista) {
    var message = 'Yakin ingin menghapus data Token Listrik tanggal "' + tanggal + '" shift ' + shift + ' (' + barista + ')?';

    swalConfirm({
        text: message,
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ route('headbar.riwayat.token-listrik.delete', 0) }}".replace('/0', '/' + id), {
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
                    showToast('success', data.message || 'Token Listrik berhasil dihapus.');
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus.');
                }
            }).catch(function () {
                // Fallback: submit form biasa
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("headbar.riwayat.token-listrik.delete", 0) }}'.replace('/0', '/' + id);
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
function openBulkDeleteModal() {
    var checked = document.querySelectorAll('.row-checkbox:checked');

    if (checked.length === 0) {
        showToast('danger', 'Pilih minimal satu data terlebih dahulu.');
        return;
    }

    swalConfirm({
        text: 'Anda akan menghapus ' + checked.length + ' data Token Listrik.',
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ route('headbar.riwayat.token-listrik.bulk-delete') }}", {
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


