@extends('layouts.role')
@section('title', $title ?? ('Edit Barista - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil me-2"></i>Edit Barista
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
                    <form method="POST" action="{{ route('manager.data-barista.edit', $barista->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap"
                                   class="form-control @error('nama_lengkap') is-invalid @enderror"
                                   value="{{ old('nama_lengkap', $barista->nama_lengkap) }}" required
                                   placeholder="Masukkan nama lengkap">
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control"
                                   value="{{ $barista->username }}" disabled readonly>
                            <small class="text-muted">Username tidak dapat diubah.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="no_telp"
                                   class="form-control @error('no_telp') is-invalid @enderror"
                                   value="{{ old('no_telp', $barista->no_telp) }}" required
                                   placeholder="Masukkan nomor telepon, minimal 10 digit angka">
                            <small class="text-muted">Jika nomor berubah, password mengikuti 6 digit terakhir baru.</small>
                            @error('no_telp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="barista" {{ old('role', $barista->role) == 'barista' ? 'selected' : '' }}>Barista</option>
                                <option value="manager" {{ old('role', $barista->role) == 'manager' ? 'selected' : '' }}>Manager</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div>
                                @if ($barista->status ?? false)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </div>
                            <small class="text-muted">Status akun barista.</small>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manager.data-barista') }}" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-light">
                                <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

