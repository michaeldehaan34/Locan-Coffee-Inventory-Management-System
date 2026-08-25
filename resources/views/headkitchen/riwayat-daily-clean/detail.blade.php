@extends('layouts.role')
@section('title', $title ?? ('Detail Daily Clean - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-3">
                        <i class="bi bi-camera-reels me-2 text-primary"></i>
                        Detail Daily Clean
                    </h3>
                </div>
                
                <div class="card-body p-4">
                    <h5 class="mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-info-circle me-2"></i>INFORMASI
                    </h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label text-muted small fw-medium mb-1">Diinput oleh</label>
                            <p class="mb-0 fw-bold">
                                {{ $record->user ? $record->user->nama_lengkap : ($record->barista ?: 'User tidak tercatat') }}<br>
                                <span class="text-muted small fw-normal">{{ $record->user ? \Illuminate\Support\Str::title($record->user->role) : 'Barista' }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Waktu Input</label>
                            <p class="mb-0 fw-bold">
                                {{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Tanggal Berlaku</label>
                            <p class="mb-0">{{ $record->tanggal ? $record->tanggal->format('d F Y') : '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-medium mb-1">Shift</label>
                            <p class="mb-0">{{ $record->shift }}</p>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2 mt-4">
                        <i class="bi bi-images me-2"></i>DETAIL (FOTO)
                    </h5>

                    @if (count($photos) > 0)
                        <div class="row g-3 mb-4">
                            @foreach ($photos as $photo)
                                <div class="col-6 col-md-4 photo-card">
                                    <a href="{{ $photo['url'] }}" target="_blank">
                                        <img src="{{ $photo['url'] }}"
                                             class="img-fluid rounded border photo-preview"
                                             alt="Daily Clean Photo"
                                             style="object-fit: cover; aspect-ratio: 1/1; width: 100%; transition: transform 0.2s;"
                                             onmouseover="this.style.transform='scale(1.05)'"
                                             onmouseout="this.style.transform='scale(1)'">
                                    </a>
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

