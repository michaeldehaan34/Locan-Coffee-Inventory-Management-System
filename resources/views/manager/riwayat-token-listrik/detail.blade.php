@extends('layouts.role')
@section('title', $title ?? ('Detail Token Listrik - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-lightning-charge-fill me-2"></i>Detail Token Listrik
                    </h2>
                    <a href="{{ route('manager.riwayat.token-listrik') }}" class="btn btn-outline-light btn-sm">
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

                    <h6 class="mb-3"><i class="bi bi-list-ul me-2"></i>Detail Pemakaian Token</h6>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width: 25%;">Nilai (kWh)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Token R17</td>
                                    <td>{{ number_format($record->token_r17 ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Token R18</td>
                                    <td>{{ number_format($record->token_r18 ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Token Mesin</td>
                                    <td>{{ number_format($record->token_mesin ?? 0, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

