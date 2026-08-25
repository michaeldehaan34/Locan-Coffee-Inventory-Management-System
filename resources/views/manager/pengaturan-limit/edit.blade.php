@extends('layouts.role')
@section('title', $title ?? ('Edit Limit Stok - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-sliders me-2"></i>{{ $title }}
                    </h2>
                    <a href="{{ route('manager.pengaturan-limit', ['type' => $inventory_type ?? 'coffee_shop']) }}" class="btn btn-outline-light btn-sm">
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
                    <form method="POST" action="{{ route('manager.pengaturan-limit.update', $bahan->id) }}">
                        @csrf
                        <input type="hidden" name="inventory_type" value="{{ $inventory_type ?? 'coffee_shop' }}">

                        <div class="mb-3">
                            <label class="form-label">Nama Bahan</label>
                            <input type="text" class="form-control" value="{{ $bahan->nama }}" readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Bahan</label>
                            <input type="text" class="form-control" value="{{ $bahan->kode }}" readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" value="{{ $bahan->satuan }}" readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Limit Habis <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0" name="limit_habis" class="form-control @error('limit_habis') is-invalid @enderror"
                                   value="{{ old('limit_habis', $limit_habis) }}" required
                                   placeholder="Stok &le; nilai ini dianggap Habis">
                            <small class="text-muted">Stok &le; nilai ini dianggap <strong>Habis</strong>.</small>
                            @error('limit_habis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Limit Tipis <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0" name="limit_tipis" class="form-control @error('limit_tipis') is-invalid @enderror"
                                   value="{{ old('limit_tipis', $limit_tipis) }}" required
                                   placeholder="Stok &le; nilai ini (dan > Limit Habis) dianggap Tipis">
                            <small class="text-muted">Stok &le; nilai ini (dan > Limit Habis) dianggap <strong>Tipis</strong>.</small>
                            @error('limit_tipis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('manager.pengaturan-limit', ['type' => $inventory_type ?? 'coffee_shop']) }}" class="btn btn-outline-light">Batal</a>
                            <button type="submit" class="btn btn-light">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

