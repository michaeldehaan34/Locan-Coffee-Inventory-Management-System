@extends('layouts.role')
@section('title', 'Edit Terima Stok - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pb-0 pt-4 px-4">
                    <h3 class="mb-3">
                        <i class="bi bi-truck me-2 text-primary"></i>
                        Edit Terima Stok
                    </h3>
                </div>
                
                <form action="{{ route('headbar.coffee-shop.terima-stok.update', $id) }}" method="POST" id="formUpdateStok">
                    @csrf
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="tanggal" class="form-label fw-medium">Tanggal Pengiriman <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ old('tanggal', $default_data['tanggal'] ?? now()->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Manager <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control bg-light" value="{{ session('name') ?: session('username') }}" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- INPUT BAHAN BAKU -->
                        <div class="bg-light p-3 rounded mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <h5 class="mb-0 text-primary">
                                    <i class="bi bi-box-seam me-2"></i>Item Bahan Baku
                                </h5>
                                
                            </div>
                            <small class="text-muted">Kosongkan jika bahan tidak dikirim.</small>
                        </div>

                        <div class="accordion" id="accordionBahan">
                            @foreach($bahan_tree as $node)
                                <div class="accordion-item mb-2 border rounded">
                                    <h2 class="accordion-header" id="heading-{{ Str::slug($node['kategori']) }}">
                                        <button class="accordion-button collapsed py-2 px-3 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ Str::slug($node['kategori']) }}" aria-expanded="false" aria-controls="collapse-{{ Str::slug($node['kategori']) }}">
                                            {{ $node['kategori'] }}
                                            
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ Str::slug($node['kategori']) }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ Str::slug($node['kategori']) }}" data-bs-parent="#accordionBahan">
                                        <div class="accordion-body bg-white pt-2 pb-0">
                                            @foreach($node['kelompok_list'] as $grp)
                                                <div class="mb-3 p-3 bg-light rounded border">
                                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Kelompok: {{ $grp['kelompok'] }}</h6>
                                                    <div class="row row-cols-1 row-cols-md-2 g-3">
                                                        @foreach($grp['items'] as $item)
                                                            <div class="col">
                                                                <label class="form-label mb-1 text-secondary small d-block">
                                                                    {{ $item['nama'] }}
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           class="form-control item-input" 
                                                                           id="{{ $item['kode'] }}" 
                                                                           name="{{ $item['kode'] }}"
                                                                           data-kelompok="{{ Str::slug($node['kategori']) }}"
                                                                           value="{{ old($item['kode'], $default_data[$item['kode']] ?? '') }}">
                                                                    <span class="input-group-text text-muted" style="width: 60px; justify-content: center;">{{ $item['satuan'] }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-top d-flex justify-content-between p-4">
                        <a href="{{ route('headbar.coffee-shop.terima-stok.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSubmit">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
