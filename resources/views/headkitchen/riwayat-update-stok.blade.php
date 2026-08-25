@extends('layouts.role')
@section('title', 'Riwayat Update - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Update Stok</h2>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Card -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center"><h6 class="text-muted mb-2">Total Riwayat</h6><h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center"><h6 class="text-muted mb-2">Hari Ini</h6><h3 class="mb-0 fw-bold text-teal">{{ $stats['today'] }}</h3></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center"><h6 class="text-muted mb-2">7 Hari</h6><h3 class="mb-0 fw-bold text-amber">{{ $stats['week'] }}</h3></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center"><h6 class="text-muted mb-2">30 Hari</h6><h3 class="mb-0 fw-bold text-blue">{{ $stats['month'] }}</h3></div>
            </div>
        </div>
    </div>

    <!-- Kontrol Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('headkitchen.riwayat.update-stok') }}" class="filter-form">
                <div class="row g-4 align-items-end">
                    <div class="col-12 col-md-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari Tanggal / Shift / Kitchen...">
                    </div>
                    <div class="col-12 col-md-4">
                        <select id="filterSelect" class="form-select">
                            <option value="all">Semua</option>
                            <option value="today">Hari Ini</option>
                            <option value="7d">7 Hari</option>
                            <option value="30d">30 Hari</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-muted mb-1">Nama Barang</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="barang" placeholder="Cari Nama Barang..." value="{{ $filter_barang }}" autocomplete="off" @if (!empty($barang_suggestions)) list="barang_list" @endif>
                        </div>
                        @if (!empty($barang_suggestions))
                        <datalist id="barang_list">@foreach ($barang_suggestions as $s)<option value="{{ $s['nama'] }}">@endforeach</datalist>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-outline-light"><i class="bi bi-search me-1"></i>Cari</button>
                    <a href="{{ route('headkitchen.riwayat.update-stok') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>

                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Detail -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Riwayat Detail Update</h5>
                    </div>
                    <hr class="border-secondary mb-3">
                    <div class="table-responsive">
                        <form id="bulkDeleteForm" method="POST" action="{{ route('headkitchen.riwayat.update-stok.bulk-delete') }}">
                            @csrf
                        <table class="table table-hover align-middle mb-0" id="historyTable">
                            <thead>
                                <tr>
                                                                            <th style="width: 4%;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                                <label class="form-check-label" for="selectAll"></label>
                                            </div>
                                        </th>
                                        <th style="width: 5%;">No</th>
                                    <th>Waktu Input</th>
                                    <th>Shift</th>
                                    <th>Diinput Oleh</th>
                                    <th>Jumlah Item</th>
                                    <th style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="historyBody"></tbody>
                        </table>
                        </form>
                        <p id="noResult" class="text-muted text-center py-3 mb-0 d-none">Tidak ada data yang sesuai dengan pencarian.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Perbandingan Stok -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Perbandingan Stok</h5>
                        <div class="small text-muted">
                            @if ($comparison['requested'] && $comparison['tanggal_valid'])
                            <span class="me-3"><i class="bi bi-calendar-check me-1"></i>Tanggal Awal: <strong>{{ $comparison['tanggal_awal'] }}</strong></span>
                            <span><i class="bi bi-calendar-event me-1"></i>Tanggal Pembanding: <strong>{{ $comparison['tanggal_pembanding'] }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <hr class="border-secondary mb-3">
                    <form method="GET" action="{{ route('headkitchen.riwayat.update-stok') }}" class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-4">
                            <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" id="tgl_awal" name="tgl_awal" value="{{ $tgl_awal ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="tgl_pembanding" class="form-label">Tanggal Pembanding</label>
                            <input type="date" class="form-control" id="tgl_pembanding" name="tgl_pembanding" value="{{ $tgl_pembanding ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-left-right me-1"></i>Bandingkan</button>
                        </div>
                    </form>

                    @if ($comparison['requested'] && !$comparison['tanggal_valid'])
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>Tanggal Awal tidak boleh lebih besar dari Tanggal Pembanding.
                    </div>
                    @elseif ($comparison['requested'] && !$comparison['has_data'])
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div class="empty-title">Tidak ada data</div>
                        <div class="empty-text">Tidak ada data update stok pada tanggal yang dipilih.</div>
                    </div>
                    @elseif ($comparison['requested'] && $comparison['has_data'])
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="compareTable">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Nama Barang</th>
                                    <th style="width: 18%;">Stok pada Tanggal Awal</th>
                                    <th style="width: 18%;">Stok pada Tanggal Pembanding</th>
                                    <th style="width: 16%;">Selisih</th>
                                    <th style="width: 18%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($comparison['items'] as $item)
                                <tr>
                                    <td>{{ $item['label'] }}</td>
                                    <td>{{ $item['stok_awal'] }} {{ $item['unit'] }}</td>
                                    <td>{{ $item['stok_pembanding'] }} {{ $item['unit'] }}</td>
                                    <td>
                                        @if ($item['status'] == 'bertambah')
                                            <span class="text-success fw-semibold">+{{ $item['selisih'] }}</span>
                                        @elseif ($item['status'] == 'berkurang')
                                            <span class="text-danger fw-semibold">{{ $item['selisih'] }}</span>
                                        @else
                                            <span class="text-secondary fw-semibold">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item['status'] == 'bertambah')
                                            <span class="badge bg-success-subtle text-success border border-success">↑ Bertambah</span>
                                        @elseif ($item['status'] == 'berkurang')
                                            <span class="badge bg-danger-subtle text-danger border border-danger">↓ Berkurang</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary">Tidak berubah</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state">
                        <i class="bi bi-calendar-range"></i>
                        <div class="empty-title">Pilih tanggal perbandingan</div>
                        <div class="empty-text">Pilih tanggal yang ingin dibandingkan</div>
                    </div>
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
                <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i>Detail Update Stok</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Diinput oleh:</small>
                        <strong id="dKitchen"></strong> <span id="dKitchenRole"></span>
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
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Kategori</th><th>Kelompok</th><th>Nama Bahan</th><th>Stok</th><th>Status</th></tr></thead>
                        <tbody id="dItems"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
var ALL_RECORDS = @json(array_values($records));

function statusBadge(s) {
    if (s === 'habis') return '<span class="badge bg-danger">Habis</span>';
    if (s === 'tipis') return '<span class="badge bg-warning text-dark">Hampir Habis</span>';
    return '<span class="badge bg-success">Aman</span>';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
    });
}

function applyFilter() {
    var q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    var range = document.getElementById('filterSelect').value;
    var today = new Date(); today.setHours(0,0,0,0);
    var wAgo = new Date(today); wAgo.setDate(wAgo.getDate() - 7);
    var mAgo = new Date(today); mAgo.setDate(mAgo.getDate() - 30);
    return ALL_RECORDS.filter(function (rec) {
        var d = new Date(rec.tanggal + 'T00:00:00');
        if (range === 'today' && d.getTime() !== today.getTime()) return false;
        if (range === '7d' && d < wAgo) return false;
        if (range === '30d' && d < mAgo) return false;
        if (q) {
            var hay = (rec.tanggal_display + ' ' + rec.shift + ' ' + rec.Kitchen).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    });
}

function renderTable() {
    var filtered = applyFilter();
    var tbody = document.getElementById('historyBody');
    tbody.innerHTML = '';
    if (filtered.length === 0) {
        document.getElementById('noResult').classList.remove('d-none');
        return;
    }
    document.getElementById('noResult').classList.add('d-none');
    filtered.forEach(function (rec, i) {
        var tr = document.createElement('tr');
                tr.innerHTML =
            '<td>' + (i + 1) + '</td>' +
            '<td><div>' + escapeHtml(rec.tanggal_display) + '</div><small class="text-muted d-block">' + escapeHtml(rec.waktu_wib) + '</small></td>' +
            '<td>' + escapeHtml(rec.shift) + '</td>' +
            '<td><div>' + escapeHtml(rec.Kitchen) + '</div>' + (rec.barista_role ? '<small class="text-muted text-capitalize">' + escapeHtml(rec.barista_role) + '</small>' : '') + '</td>' +
            '<td>' + escapeHtml(rec.jumlah_item) + '</td>' +
            '<td>' +
            '<div class="btn-group btn-group-sm" role="group">' +
            '<a href="{{ route("headkitchen.update-stok.edit", 0) }}'.replace('/0', '/' + rec.id) + '" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>' +
            '<a href="{{ route("headkitchen.update-stok.detail", 0) }}'.replace('/0', '/' + rec.id) + '" class="btn btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>' +
            '<button class="btn btn-outline-danger" onclick="confirmDelete(' + rec.id + ')" title="Hapus"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function openDetail(id) {
    var rec = ALL_RECORDS.find(function (r) { return r.id === id; });
    if (!rec) return;
    document.getElementById('dTanggal').textContent = rec.tanggal_display;
    document.getElementById('dShift').textContent = rec.shift;
    document.getElementById('dKitchen').textContent = rec.barista;
    document.getElementById('dKitchenRole').innerHTML = rec.barista_role ? '&mdash; <span class="text-capitalize">' + rec.barista_role + '</span>' : '';
    document.getElementById('dWaktu').textContent = rec.waktu_wib;
    var tb = document.getElementById('dItems');
    tb.innerHTML = '';
    rec.items.forEach(function (it) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + escapeHtml(it.kategori || '-') + '</td><td>' + escapeHtml(it.kelompok || '-') + '</td><td>' + escapeHtml(it.label) + '</td><td>' + escapeHtml(it.value) + '</td><td>' + statusBadge(it.status) + '</td>';
        tb.appendChild(tr);
    });
}

function confirmDelete(id) {
    swalConfirm({
        text: 'Apakah Anda yakin ingin menghapus data update stok ini? Tindakan ini tidak dapat dibatalkan.',
        onConfirm: function () {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("headkitchen.update-stok.delete", 0) }}'.replace('/0', '/' + id);
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            var method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

document.getElementById('searchInput').addEventListener('keyup', renderTable);
document.getElementById('filterSelect').addEventListener('change', renderTable);
renderTable();

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
function openSingleDeleteModal(id, tanggal, shift) {
    var message = 'Yakin ingin menghapus data Update Stok tanggal "' + tanggal + '" shift ' + shift + '?';

    swalConfirm({
        text: message,
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ route('headkitchen.riwayat.update-stok.delete', 0) }}".replace('/0', '/' + id), {
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
                    showToast('success', data.message || 'Data berhasil dihapus.');
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showToast('danger', data.message || 'Gagal menghapus.');
                }
            }).catch(function () {
                // Fallback: submit form biasa
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("headkitchen.riwayat.update-stok.delete", 0) }}'.replace('/0', '/' + id);
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
        text: 'Anda akan menghapus ' + checked.length + ' data Update Stok.',
        confirmButtonColor: '#DC3545',
        showLoader: true,
        preConfirm: function () {
            return fetch("{{ route('headkitchen.riwayat.update-stok.bulk-delete') }}", {
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
