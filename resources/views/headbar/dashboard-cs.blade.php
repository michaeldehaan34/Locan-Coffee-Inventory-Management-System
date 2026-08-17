@extends('layouts.role')
@section('title', 'Dashboard Headbar - ' . config('branding.app_name'))
@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-0">
                        <i class="bi bi-cup-hot me-2 text-teal"></i>
                        Dashboard Headbar
                    </h2>
                    <p class="text-muted mb-0">Selamat datang, {{ session('name') ?: session('username') }}! Berikut Ringkasan Stok Coffeeshop Terkini.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- STOK BAHAN COFFEESHOP -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3 text-center">
                        <i class="bi bi-box-seam me-2 text-primary"></i>
                        STOK BAHAN COFFEESHOP TERKINI
                    </h5>
                    <p class="text-muted small mb-3 text-center">Nilai stok terkini di lokasi Coffeeshop.</p>
                    
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
