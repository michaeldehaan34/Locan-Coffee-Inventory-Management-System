@extends('layouts.role')
@section('title', $title ?? ('Detail Daily Clean - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-camera-reels me-2"></i>Detail Daily Clean
                    </h2>
                    <a href="{{ route('manager.riwayat.daily-clean') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Tanggal</small>
                            <strong>{{ $record->tanggal ? $record->tanggal->format('Y-m-d') : '-' }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Shift</small>
                            <strong>{{ $record->shift }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Barista</small>
                            <strong>{{ $record->barista }}</strong>
                        </div>
                    </div>

                    <hr class="border-secondary mb-3">

                    <h6 class="mb-3"><i class="bi bi-images me-2"></i>Foto Dokumentasi ({{ $jumlah_foto ?? count($photos) }})</h6>

                    @if (count($photos) > 0)
                        <div class="row g-3">
                            @foreach ($photos as $photo)
                                <div class="col-6 col-md-4">
                                    <a href="{{ $photo['url'] }}" target="_blank">
                                        <img src="{{ $photo['url'] }}"
                                             class="img-fluid rounded border"
                                             style="object-fit:cover;height:160px;width:100%;"
                                             alt="{{ $photo['original_name'] ?? 'Foto' }}">
                                    </a>
                                    <div class="small text-muted text-truncate mt-1">
                                        {{ $photo['original_name'] ?? 'Foto' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">Tidak ada foto.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

