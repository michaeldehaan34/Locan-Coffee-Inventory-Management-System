@extends('layouts.role')

@section('title', 'Daily Clean - ' . config('branding.app_name'))

@section('content')
<div class="page-container">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="mb-0"><i class="bi bi-camera me-2"></i>Daily Clean</h2>
                        <a href="{{ route('kitchen.update-stok') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                    </div>
                    <hr class="border-secondary">
                    <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Wajib mengirim <strong>minimal {{ $min_photos }} foto</strong> setiap shift sebagai bukti daily clean.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('kitchen.daily-clean.store') }}" enctype="multipart/form-data" id="dailyCleanForm">
                        @csrf
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ $today }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="shift" class="form-label">Shift</label>
                            <input type="text" class="form-control" id="shift" name="shift" placeholder="Contoh: Pagi, Shift 1, dll" required>
                        </div>
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto Daily Clean (minimal {{ $min_photos }}, maks 7MB/foto)</label>
                            <input type="file" class="form-control" id="foto" name="foto[]" accept="image/*" multiple onchange="updateCount(this)" required>
                            <div class="form-text" id="fileCount">Belum ada foto dipilih.</div>
                            @if ($errors->has('foto') || $errors->has('foto.*'))
                                <div class="text-danger small mt-2">{{ $errors->first('foto') ?: $errors->first('foto.*') }}</div>
                            @endif
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Kirim Daily Clean</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('extra_js')
<script>
function updateCount(input) {
    var n = input.files ? input.files.length : 0;
    var el = document.getElementById('fileCount');
    var min = {{ $min_photos }};
    if (n === 0) { el.textContent = 'Belum ada foto dipilih.'; }
    else if (n < min) { el.innerHTML = '<span class="text-danger">Baru ' + n + ' foto. Minimal ' + min + ' foto wajib dikirim.</span>'; }
    else { el.innerHTML = '<span class="text-success">' + n + ' foto dipilih. Siap dikirim.</span>'; }
}
document.getElementById('dailyCleanForm').addEventListener('submit', function (e) {
    var input = document.getElementById('foto');
    var n = input.files ? input.files.length : 0;
    var min = {{ $min_photos }};
    if (n < min) { 
        e.preventDefault(); 
        Swal.fire({ icon: 'warning', title: 'Periksa Kembali', text: 'Minimal ' + min + ' foto harus dikirim. Saat ini hanya ' + n + '.', confirmButtonColor: '#DC3545', background: '#1F2026', color: '#e4e4e7', borderRadius: '16px', customClass: { popup: 'swal-dark-popup' } }); 
        return; 
    }
    
    // Validasi ukuran max 7MB
    for (var i = 0; i < n; i++) {
        if (input.files[i].size > 7 * 1024 * 1024) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Ukuran Terlalu Besar', text: 'Ukuran maksimal per foto adalah 7MB. Silakan kompres atau pilih foto lain.', confirmButtonColor: '#DC3545', background: '#1F2026', color: '#e4e4e7', borderRadius: '16px', customClass: { popup: 'swal-dark-popup' } });
            return;
        }
    }
});
</script>
@endpush
@endsection
