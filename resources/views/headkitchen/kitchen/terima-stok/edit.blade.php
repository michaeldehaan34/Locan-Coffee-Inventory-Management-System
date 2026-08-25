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
                
                <form action="{{ route('headkitchen.kitchen.terima-stok.update', $id) }}" method="POST" id="formUpdateStok">
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

                        @php
                            $kat_icons = [
                                'Bahan Baku Bar' => 'bi-cup-hot',
                                'Bahan Baku Kitchen' => 'bi-tools',
                                'Equipment' => 'bi-hdd-network',
                            ];
                        @endphp
                        <div class="accordion bahan-accordion" id="kategoriAccordion">
                            @foreach($bahan_tree as $ki => $node)
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
                                                @foreach($node['kelompok_list'] as $gi => $grp)
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
                                                                    @foreach($grp['items'] as $item)
                                                                        <div class="barang-card input-with-unit">
                                                                            <label for="{{ $item['kode'] }}" class="form-label">{{ $item['nama'] }}</label>
                                                                            <input type="number" 
                                                                                   step="0.01" 
                                                                                   min="0" 
                                                                                   class="form-control form-control-sm item-input" 
                                                                                   id="{{ $item['kode'] }}" 
                                                                                   name="{{ $item['kode'] }}"
                                                                                   data-kelompok="{{ Str::slug($node['kategori']) }}"
                                                                                   value="{{ old($item['kode'], $default_data[$item['kode']] ?? '') }}" placeholder="0">
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
                    </div>
                    
                    <div class="card-footer bg-white border-top d-flex justify-content-between p-4">
                        <a href="{{ route('headkitchen.kitchen.terima-stok.index') }}" class="btn btn-light border px-4">Batal</a>
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
