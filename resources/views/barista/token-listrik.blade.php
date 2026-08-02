@extends('layouts.role')

@section('title', 'Token Listrik - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Token Listrik</h2>
                        <a href="{{ route('barista.update-stok') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                    </div>
                    <hr class="border-secondary">
                    <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Input nomor token listrik setiap shift sebagai bukti pengisian.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('barista.token-listrik.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ $today }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="shift" class="form-label">Shift</label>
                            <select class="form-select" id="shift" name="shift" required>
                                <option value="">Pilih Shift</option>
                                @foreach ($shift_list as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="token_r17" class="form-label">Token Listrik R17</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="token_r17" name="token_r17" inputmode="decimal" placeholder="Contoh: 123,45" required>
                                <span class="input-group-text">kWh</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="token_r18" class="form-label">Token Listrik R18</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="token_r18" name="token_r18" inputmode="decimal" placeholder="Contoh: 123,45" required>
                                <span class="input-group-text">kWh</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="token_mesin" class="form-label">Token Listrik Mesin</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="token_mesin" name="token_mesin" inputmode="decimal" placeholder="Contoh: 123,45" required>
                                <span class="input-group-text">kWh</span>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Simpan Token Listrik</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection