@extends('layouts.role')
@section('title', 'Pengaturan Limit Stok - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-sliders me-2"></i>Pengaturan Limit Stok
                    </h2>
                    <div>
                        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Penjelasan -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info border-0 mb-0" role="alert">
                <i class="bi bi-info-circle me-1"></i>
                Atur batas stok tiap bahan. Klasifikasi:
                <strong>Habis</strong> = stok &le; Limit Habis,
                <strong>Tipis</strong> = Limit Habis < stok &le; Limit Tipis,
                <strong>Aman</strong> = stok > Limit Tipis.
                Nilai ini dipakai bersama oleh Dashboard & Forecast.
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="limitTable">
                            <thead>
                                <tr>
                                    <th style="width: 4%;">No</th>
                                    <th>Kode</th>
                                    <th>Nama Bahan</th>
                                    <th>Satuan</th>
                                    <th style="width: 18%;">Limit Habis</th>
                                    <th style="width: 18%;">Limit Tipis</th>
                                    <th style="width: 14%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($limits as $i => $b)
                                    @php $no = $i + 1; @endphp
                                    <tr>
                                        <td>{{ $no }}</td>
                                        <td><code>{{ $b->kode }}</code></td>
                                        <td>{{ $b->nama }}</td>
                                        <td>{{ $b->satuan }}</td>
                                        <td>{{ $b->limit_habis }}</td>
                                        <td>{{ $b->limit_tipis }}</td>
                                        <td>
                                            <a href="{{ route('manager.pengaturan-limit.edit', $b->id) }}" class="btn btn-sm btn-outline-light">
                                                <i class="bi bi-pencil"></i> Atur
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3 mb-0">Belum ada bahan aktif.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
