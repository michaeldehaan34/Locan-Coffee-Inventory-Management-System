@extends('layouts.role')
@section('title', ($title ?? 'Edit Stok Masuk') . ' - ' . config('branding.app_name'))



@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>{{ $title ?? 'Edit Stok Masuk' }}
                    </h2>
                    <a href="{{ route('gudang.stok-masuk.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Form Edit Stok Masuk
                        <span class="badge bg-secondary ms-1">#{{ $id ?? '-' }}</span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if ($errors->has('__items__'))
                        <div class="alert alert-danger py-2">{{ $errors->first('__items__') }}</div>
                    @endif

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle me-2"></i>
                                Informasi
                            </h6>
                            <p class="card-text text-muted mb-0">
                                Input jumlah stok yang masuk. Kalo gaada stok masuk, kosongin aja. Minimal satu item harus diisi.
                                Barang dikelompokkan berdasarkan Master Barang: Kategori → Kelompok → Daftar Barang.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('gudang.stok-masuk.update', $id) }}" id="stokMasukForm" autocomplete="off">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal" class="form-label"><i class="bi bi-calendar me-1"></i>Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal"
                                    value="{{ old('tanggal', ($default_data['tanggal'] ?? date('Y-m-d'))) }}" required>
                                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="barista" class="form-label"><i class="bi bi-person me-1"></i>Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('barista') is-invalid @enderror" id="barista" name="barista"
                                    value="{{ old('barista', ($default_data['barista'] ?? '')) }}" required>
                                @error('barista')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                                                            <input type="text" class="form-control form-control-sm item-input @error($item['kode']) is-invalid @enderror" id="{{ $item['kode'] }}" name="{{ $item['kode'] }}"
                                                                                value="{{ old($item['kode'], ($default_data[$item['kode']] ?? '')) }}" inputmode="decimal" placeholder="0">
                                                                            <span class="unit-label">{{ $item['satuan'] }}</span>
                                                                            @error($item['kode'])<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Perbarui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
document.getElementById('stokMasukForm').addEventListener('submit', function(e) {
    const itemInputs = document.querySelectorAll('.item-input');
    let hasValue = false;
    itemInputs.forEach(function(input) { if (input.value.trim() !== '') { hasValue = true; } });
if (!hasValue) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Periksa Kembali', text: 'Minimal satu item harus diisi.', confirmButtonColor: '#DC3545', background: '#1F2026', color: '#e4e4e7', borderRadius: '16px', customClass: { popup: 'swal-dark-popup' } }); return false; }
    itemInputs.forEach(function(input) {
        const value = input.value.trim();
        if (value && isNaN(value)) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Periksa Kembali', text: input.id.replace('_', ' ').toUpperCase() + ' harus berupa angka.', confirmButtonColor: '#DC3545', background: '#1F2026', color: '#e4e4e7', borderRadius: '16px', customClass: { popup: 'swal-dark-popup' } }); input.focus(); return false; }
    });
});
document.querySelectorAll('.item-input').forEach(function(input) {
    input.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (value && !/^\d+$/.test(value)) { e.target.classList.add('is-invalid'); }
        else { e.target.classList.remove('is-invalid'); }
    });
});
</script>
@endpush
@endsection
