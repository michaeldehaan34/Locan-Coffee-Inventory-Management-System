@extends('layouts.role')

@section('title', 'Update Stok - ' . config('branding.app_name'))



@section('content')
<div class="page-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Update Stok</h2>
                    <p class="text-muted mb-0">Catat stok bahan baku per shift</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Form Update Stok</h5>
                </div>
                <div class="card-body p-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                            <p class="card-text text-muted mb-0">
                                Input sisa stok yang ada. Harus input semua list yang tertera. Jangan sampai kosong.
                            </p>
                        </div>
                    </div>

                                        <form method="POST" action="{{ session('role') === 'manajemen' ? route('manager.update-stok.store') : route('barista.update-stok.store') }}" id="updateStokForm">


                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-{{ session('role') === 'manajemen' ? '6' : '4' }} mb-3">
                                <label for="tanggal" class="form-label"><i class="bi bi-calendar me-1"></i>Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                            </div>
                            @if (session('role') !== 'manajemen')
                            <div class="col-md-4 mb-3">
                                <label for="shift" class="form-label"><i class="bi bi-clock me-1"></i>Shift <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="shift" name="shift" value="{{ old('shift', '') }}" required placeholder="Ketik shift">
                            </div>
                            @endif
                            <div class="col-md-{{ session('role') === 'manajemen' ? '6' : '4' }} mb-3">
                                <label for="barista" class="form-label"><i class="bi bi-person me-1"></i>Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="barista" name="barista" value="{{ $barista_name }}" readonly>
                            </div>
                        </div>

                        @php
                            $kat_icons = [
                                'Bahan Baku Bar' => 'bi-cup-hot',
                                'Bahan Baku Kitchen' => 'bi-tools',
                                'Equipment' => 'bi-hdd-network',
                            ];
                        @endphp
                        <div class="accordion bahan-accordion" id="kategoriAccordion">
                            @foreach ($bahan_tree as $ki => $node)
                                @php $kat_id = 'kat_'.($ki+1); @endphp
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="head_{{ $kat_id }}">
                                        <button class="accordion-button{{ $ki > 0 ? ' collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#body_{{ $kat_id }}" aria-expanded="{{ $ki === 0 ? 'true' : 'false' }}" aria-controls="body_{{ $kat_id }}">
                                            <i class="bi {{ $kat_icons[$node['kategori']] ?? 'bi-box' }} me-2"></i><span>{{ $node['kategori'] }}</span>
                                        </button>
                                    </h2>
                                    <div id="body_{{ $kat_id }}" class="accordion-collapse collapse{{ $ki === 0 ? ' show' : '' }}" aria-labelledby="head_{{ $kat_id }}" data-bs-parent="#kategoriAccordion">
                                        <div class="accordion-body">
                                            <div class="accordion kelompok-accordion" id="kel_{{ $kat_id }}">
                                                @foreach ($node['kelompok_list'] as $gi => $grp)
                                                    @php $kel_id = $kat_id.'_grp_'.($gi+1); @endphp
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="head_{{ $kel_id }}">
                                                            <button class="accordion-button{{ $gi > 0 ? ' collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#body_{{ $kel_id }}" aria-expanded="{{ $gi === 0 ? 'true' : 'false' }}" aria-controls="body_{{ $kel_id }}">
                                                                <i class="bi bi-collection me-2"></i><span>{{ $grp['kelompok'] }}</span>
                                                            </button>
                                                        </h2>
                                                        <div id="body_{{ $kel_id }}" class="accordion-collapse collapse{{ $gi === 0 ? ' show' : '' }}" aria-labelledby="head_{{ $kel_id }}" data-bs-parent="#kel_{{ $kat_id }}">
                                                            <div class="accordion-body">
                                                                <div class="barang-grid">
                                                                    @foreach ($grp['items'] as $item)
                                                                        <div class="barang-card input-with-unit">
                                                                            <label for="{{ $item['kode'] }}" class="form-label">{{ $item['nama'] }}</label>
                                                                            <input type="text" class="form-control form-control-sm item-input" id="{{ $item['kode'] }}" name="{{ $item['kode'] }}" value="{{ old($item['kode'], '') }}" inputmode="decimal" placeholder="0" required>
                                                                            <span class="unit-label">{{ $item['satuan'] }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="reset" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
document.getElementById('updateStokForm').addEventListener('submit', function(e) {
    const itemInputs = document.querySelectorAll('.item-input');
    let emptyFields = [];
    itemInputs.forEach(function(input) { if (input.value.trim() === '') { emptyFields.push(input); } });
    if (emptyFields.length > 0) {
        e.preventDefault();
        emptyFields.forEach(function(input) { input.classList.add('is-invalid'); });
        showStokError('Semua data stok wajib diisi sebelum disimpan.');
        emptyFields[0].focus();
        return false;
    }
    let invalidNum = null;
    itemInputs.forEach(function(input) {
        const value = input.value.trim();
        if (!/^\d+(\.\d+)?$/.test(value)) { input.classList.add('is-invalid'); invalidNum = input; }
    });
    if (invalidNum) { e.preventDefault(); hideStokError(); Swal.fire({ icon: 'warning', title: 'Periksa Kembali', text: invalidNum.id.replace('_', ' ').toUpperCase() + ' harus berupa angka.', confirmButtonColor: '#DC3545', background: '#1F2026', color: '#e4e4e7', borderRadius: '16px', customClass: { popup: 'swal-dark-popup' } }); invalidNum.focus(); return false; }
});
function showStokError(message) {
    let box = document.getElementById('stokErrorBox');
    if (!box) {
        box = document.createElement('div'); box.id = 'stokErrorBox'; box.className = 'alert alert-danger mt-3'; box.setAttribute('role', 'alert');
        const form = document.getElementById('updateStokForm'); form.parentNode.insertBefore(box, form);
    }
    box.textContent = message;
}
function hideStokError() { const box = document.getElementById('stokErrorBox'); if (box) box.remove(); }
document.querySelectorAll('.item-input').forEach(function(input) {
    input.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value !== '' && !/^\d+(\.\d+)?$/.test(value)) { e.target.classList.add('is-invalid'); }
        else { e.target.classList.remove('is-invalid'); if (document.querySelectorAll('.item-input.is-invalid').length === 0) hideStokError(); }
    });
});
</script>
@endpush
@endsection