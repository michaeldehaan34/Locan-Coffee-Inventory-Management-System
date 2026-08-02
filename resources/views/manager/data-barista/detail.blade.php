@extends('layouts.role')
@section('title', $title ?? ('Detail Barista - ' . config('branding.app_name')))

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="mb-0">
                        <i class="bi bi-person me-2"></i>Detail Barista
                    </h2>
                    <a href="{{ route('manager.data-barista') }}" class="btn btn-outline-light btn-sm">
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
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 200px;">Nama Lengkap</td>
                            <td><strong>{{ $barista->nama_lengkap }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Username</td>
                            <td><code>{{ $barista->username }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nomor Telepon</td>
                            <td>{{ $barista->no_telp }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Role</td>
                            <td>
                                @if ($barista->role == 'manager')
                                    <span class="badge bg-warning text-dark">Manager</span>
                                @else
                                    <span class="badge bg-secondary">Barista</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if ($barista->status ?? false)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Dibuat</td>
                            <td>{{ $barista->created_at ? $barista->created_at->format('d-m-Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Terakhir Diperbarui</td>
                            <td>{{ $barista->updated_at ? $barista->updated_at->format('d-m-Y H:i') : '-' }}</td>
                        </tr>
                    </table>

                    <hr class="border-secondary">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('manager.data-barista.edit.form', $barista->id) }}" class="btn btn-outline-light">
                            <i class="bi bi-pencil me-1"></i>Edit Barista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

