@extends('layouts.role')
@section('title', $title ?? ('Edit Barang - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil me-2"></i>Edit Barang
                    </h2>
                    <a href="{{ route('manager.master-bahan') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('manager.master-bahan.update', $bahan->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Kode <span class="text-danger">*</span></label>
                            <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror"
                                   value="{{ old('kode', $bahan->kode) }}" required
                                   placeholder="huruf kecil, angka, underscore (mis. brown_sugar)">
                            <small class="text-muted">Kode digunakan sebagai kolom di database & form. Mengubah kode akan mengubah nama kolom di database.</small>
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama', $bahan->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" id="kategori" class="form-select @error('kategori') is-invalid @enderror">
                                @foreach ($kategori_list as $kat)
                                    <option value="{{ $kat }}" @selected(old('kategori', $bahan->kategori) == $kat)>{{ $kat }}</option>
                                @endforeach
                            </select>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelompok <span class="text-danger">*</span></label>
                            <select name="kelompok" id="kelompok" class="form-select @error('kelompok') is-invalid @enderror" required>
                                <option value="">-- Pilih Kelompok --</option>
                            </select>
                            <small class="text-muted">Kelompok di bawah kategori (diambil dari database).</small>
                            @error('kelompok')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Satuan</label>
                            <select name="satuan" class="form-select @error('satuan') is-invalid @enderror">
                                @foreach ($satuan_list as $sat)
                                    <option value="{{ $sat }}" @selected(old('satuan', $bahan->satuan) == $sat)>{{ $sat }}</option>
                                @endforeach
                            </select>
                            @error('satuan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil</label>
                            <input type="number" name="urutan" class="form-control" value="{{ old('urutan', $bahan->urutan) }}">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manager.master-bahan') }}" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-light">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('extra_js')
<script>
(function () {
    var kategori = document.getElementById('kategori');
    var kelompok = document.getElementById('kelompok');
    var currentKelompok = @json(old('kelompok', $bahan->kelompok));

    function fillKelompok(kat, selected) {
        kelompok.innerHTML = '<option value="">-- Memuat... --</option>';
        fetch('{{ route('manager.master-bahan.kelompok') }}?kategori=' + encodeURIComponent(kat), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                kelompok.innerHTML = '<option value="">-- Pilih Kelompok --</option>';
                var list = (data && data.kelompok) ? data.kelompok : [];
                list.forEach(function (k) {
                    var opt = document.createElement('option');
                    opt.value = k;
                    opt.textContent = k;
                    if (selected !== undefined && selected !== null && k === selected) {
                        opt.selected = true;
                    }
                    kelompok.appendChild(opt);
                });
                if (selected && list.indexOf(selected) === -1) {
                    var extra = document.createElement('option');
                    extra.value = selected;
                    extra.textContent = selected + ' (data saat ini)';
                    extra.selected = true;
                    kelompok.appendChild(extra);
                }
            })
            .catch(function () {
                kelompok.innerHTML = '<option value="">-- Pilih Kelompok --</option>';
            });
    }

    if (kategori && kelompok) {
        fillKelompok(kategori.value, currentKelompok);
        kategori.addEventListener('change', function () {
            fillKelompok(kategori.value);
        });
    }
})();
</script>
@endpush