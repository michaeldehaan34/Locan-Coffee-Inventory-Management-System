@extends('layouts.role')
@section('title', $title ?? ('Tambah Karyawan - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Karyawan
                    </h2>
                    <a href="{{ route('manager.data-barista') }}" class="btn btn-outline-light btn-sm">
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
                    <form method="POST" action="{{ route('manager.data-barista.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap"
                                   class="form-control @error('nama_lengkap') is-invalid @enderror"
                                   value="{{ old('nama_lengkap') }}" required
                                   placeholder="Masukkan nama lengkap">
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="no_telp"
                                   class="form-control @error('no_telp') is-invalid @enderror"
                                   value="{{ old('no_telp') }}" required
                                   placeholder="Masukkan nomor telepon, minimal 10 digit angka">
                            <small class="text-muted">Password otomatis = 6 digit terakhir nomor telepon.</small>
                            @error('no_telp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="barista" {{ old('role') == 'barista' ? 'selected' : '' }}>Barista</option>
                                <option value="manajemen" {{ old('role') == 'manajemen' ? 'selected' : '' }}>Manajemen</option>
                                <option value="headbar" {{ old('role') == 'headbar' ? 'selected' : '' }}>Headbar</option>
                                <option value="kitchen" {{ old('role') == 'kitchen' ? 'selected' : '' }}>Kitchen</option>
                                <option value="headkitchen" {{ old('role') == 'headkitchen' ? 'selected' : '' }}>Head Kitchen</option>
                                <option value="admin gudang" {{ old('role') == 'admin gudang' ? 'selected' : '' }}>Admin Gudang</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manager.data-barista') }}" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-light">
                                <i class="bi bi-check-lg me-1"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

