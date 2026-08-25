@extends('layouts.role')
@section('title', 'Dashboard Coffeeshop - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div>
                        <h2 class="mb-0">
                            @if($inventory_type === 'kitchen')
                                <i class="bi bi-shop me-2 text-warning"></i> Dashboard Stok Kitchen Terkini
                            @elseif($inventory_type === 'gudang')
                                <i class="bi bi-box-seam me-2 text-primary"></i> Dashboard Stok Gudang Terkini
                            @else
                                <i class="bi bi-cup-hot me-2 text-teal"></i> Dashboard Stok Coffeeshop Terkini
                            @endif
                        </h2>
                        <p class="text-muted mb-0 mt-1">Selamat datang, {{ session('name') ?: session('username') }}! Berikut Ringkasan Stok.</p>
                    </div>
                    
                    <div class="mt-3 mt-md-0">
                        <label for="dashboardType" class="form-label visually-hidden">Pilih Dashboard</label>
                        <select id="dashboardType" class="form-select" onchange="window.location.href='?type=' + this.value">
                            <option value="kitchen" {{ $inventory_type === 'kitchen' ? 'selected' : '' }}>Dashboard Stok Kitchen Terkini</option>
                            <option value="coffee_shop" {{ $inventory_type === 'coffee_shop' ? 'selected' : '' }}>Dashboard Stok Coffeeshop Terkini</option>
                            <option value="gudang" {{ $inventory_type === 'gudang' ? 'selected' : '' }}>Dashboard Stok Gudang Terkini</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- STOK BAHAN COFFEESHOP -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3 text-center text-uppercase">
                        @if($inventory_type === 'kitchen')
                            <i class="bi bi-shop me-2 text-warning"></i> STOK BAHAN KITCHEN
                        @elseif($inventory_type === 'gudang')
                            <i class="bi bi-box-seam me-2 text-primary"></i> STOK BAHAN GUDANG
                        @else
                            <i class="bi bi-box-seam me-2 text-primary"></i> STOK BAHAN COFFEESHOP
                        @endif
                    </h5>
                    <p class="text-muted small mb-3 text-center">Nilai stok terkini di lokasi {{ ucwords(str_replace('_', ' ', $inventory_type)) }}.</p>
                    
                    @if(count($data['global_stock']) > 0)
                    <!-- Search Input -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchStokCs" class="form-control" placeholder="Cari Nama Bahan...">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Bahan</th>
                                    <th>Stok Terkini</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="stokCsTbody">
                                @foreach($data['global_stock'] as $item)
                                <tr>
                                    <td>{{ $item['nama'] }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $item['stok'] }}</span> {{ $item['satuan'] }}
                                    </td>
                                    <td>
                                        @if($item['status'] === 'aman')
                                            <span class="badge bg-success">Aman</span>
                                        @elseif($item['status'] === 'tipis')
                                            <span class="badge bg-warning text-dark">Tipis</span>
                                        @else
                                            <span class="badge bg-danger">Habis</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">Belum ada data stok Coffeeshop.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>
</div>
@endsection

@push('extra_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchStokCs');
        const tbody = document.getElementById('stokCsTbody');
        if (searchInput && tbody) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase().trim();
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    row.style.display = name.includes(term) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush
