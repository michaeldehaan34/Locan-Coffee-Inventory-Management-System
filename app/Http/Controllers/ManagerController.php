<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\BahanLimit;
use App\Models\Barista;
use App\Models\DailyClean;
use App\Models\DailyCleanPhoto;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use App\Models\TokenListrik;
use App\Services\StockAnalytics;
use App\Services\ExportService;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manager features (full access).
 */
class ManagerController extends Controller
{
    // =========================================================
    // RIWAYAT STOK MASUK (EXPORT)
    // =========================================================
    public function exportStokMasuk(Request $request)
    {
        $tglAwal = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');
        $shift = $request->input('shift');
        $barista = $request->input('barista');
        $barang = $request->input('barang');

        $raw = StokMasuk::query()
            ->select(array_merge(['id', 'tanggal', 'shift', 'barista'], Bahan::activeKeys()))
            ->when($tglAwal, fn ($q) => $q->where('tanggal', '>=', $tglAwal))
            ->when($tglAkhir, fn ($q) => $q->where('tanggal', '<=', $tglAkhir))
            ->when($shift, fn ($q) => $q->where('shift', $shift))
            ->when($barista, fn ($q) => $q->where('barista', 'like', '%'.$barista.'%'));

        // Filter nama barang meniru Flask (build_item_name_clause).
        StockAnalytics::applyBarangNameFilter($raw, $barang);

        $raw = $raw->orderByDesc('tanggal')->orderByDesc('id')->get();

        $periode = ($tglAwal && $tglAkhir) ? "$tglAwal s.d. $tglAkhir" : 'Seluruh Riwayat';

        return ExportService::stokMasukExcel($raw, $periode, session('name') ?: 'Manager');
    }

    // =========================================================
    // RIWAYAT UPDATE STOK
    // =========================================================
    public function riwayatUpdateStok(Request $request): View
    {
        $barang = $request->input('barang');
        $tglAwal = $request->input('tgl_awal');
        $tglPembanding = $request->input('tgl_pembanding');

        $records = StockAnalytics::allUpdateStok($barang);
        $stats = StockAnalytics::updateStokStats($records);

        $comparison = $tglAwal || $tglPembanding
            ? StockAnalytics::comparisonForDates($tglAwal, $tglPembanding)
            : ['requested' => false, 'has_data' => false, 'tanggal_valid' => true, 'tanggal_awal' => '-', 'tanggal_pembanding' => '-', 'items' => []];

        return view('manager.riwayat-update-stok', [
            'title' => 'Riwayat Update Stok',
            'records' => $records,
            'records_json' => json_encode(array_values($records)),
            'stats' => $stats,
            'comparison' => $comparison,
            'filter_barang' => $barang,
            'tgl_awal' => $tglAwal,
            'tgl_pembanding' => $tglPembanding,
            'barang_suggestions' => Bahan::activeItems(),
        ]);
    }

    public function exportUpdateStok(Request $request)
    {
        $barang = $request->input('barang');
        $raw = UpdateStok::query()
            ->select(array_merge(['id', 'tanggal', 'shift', 'barista'], Bahan::activeKeys()))
            ->when($barang, fn ($q) => $q->where(function ($query) use ($barang) {
                foreach (Bahan::activeKeys() as $k) {
                    $query->orWhere($k, 'like', '%'.$barang.'%');
                }
            }))
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        $filterInfo = $barang ? "Barang: $barang" : '';

        return ExportService::updateStokExcel($raw, $filterInfo, session('name') ?: 'Manager');
    }

    public function exportUpdateStokPdf(Request $request)
    {
        $barang = $request->input('barang');
        $records = StockAnalytics::allUpdateStok($barang);
        $filterInfo = $barang ? "Barang: $barang" : '';

        return ExportService::updateStokPdf($records, $filterInfo, session('name') ?: 'Manager');
    }

    // =========================================================
    // UPDATE STOK EDIT / UPDATE / DELETE
    // =========================================================

    /**
     * Halaman form edit Update Stok.
     *
     * Data transaksi diambil via Eloquent ORM lalu dipetakan ke default_data
     * agar accordion Master Barang terisi dengan nilai yang sudah ada.
     */
    public function updateStokEdit(int $id): View
    {
        $record = UpdateStok::findOrFail($id);

        $defaultData = [
            'tanggal' => $record->tanggal?->format('Y-m-d'),
            'shift' => $record->shift,
            'barista' => $record->barista,
        ];

        foreach (Bahan::activeKeys() as $kode) {
            $defaultData[$kode] = $record->{$kode};
        }

        return view('manager.update-stok.edit', [
            'title' => 'Edit Update Stok',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'shift_list' => shift_list(),
            'default_data' => $defaultData,
        ]);
    }

    /**
     * Proses update Update Stok (validasi inline + Eloquent update).
     *
     * SEMUA item bahan wajib diisi (sama seperti form Barista).
     */
    public function updateStokUpdate(Request $request, int $id): RedirectResponse
    {
        $record = UpdateStok::findOrFail($id);

        $tanggal = (string) $request->input('tanggal', '');
        $shift = (string) $request->input('shift', '');
        $barista = (string) $request->input('barista', '');

        if ($tanggal === '') {
            flash_danger('Tanggal harus diisi.');
            return back()->withInput();
        }
        if (! is_valid_date($tanggal)) {
            flash_danger('Format tanggal tidak valid.');
            return back()->withInput();
        }
        if (! is_valid_shift($shift)) {
            flash_danger('Shift tidak valid.');
            return back()->withInput();
        }
        if ($barista === '') {
            flash_danger('Nama barista harus diisi.');
            return back()->withInput();
        }

        $activeKeys = Bahan::activeKeys();
        $data = [
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista' => $barista,
        ];

        foreach ($activeKeys as $kode) {
            $val = trim((string) $request->input($kode, ''));
            if ($val === '') {
                continue; // Keep existing value when Manager does not change this item.
            }
            if (! is_numeric($val)) {
                flash_danger("Nilai untuk {$kode} harus berupa angka.");
                return back()->withInput();
            }
            $data[$kode] = $val;
        }

        $record->update($data);

        flash_success('Data update stok berhasil diperbarui.');

        return redirect()->route('manager.riwayat.update-stok');
    }

    /**
     * Proses hapus Update Stok (Eloquent delete dalam transaksi DB).
     */
    public function updateStokDestroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $record = UpdateStok::findOrFail($id);
            $record->delete();
        });

        flash_success('Data update stok berhasil dihapus.');

        return redirect()->route('manager.riwayat.update-stok');
    }

    // =========================================================
    // RIWAYAT DAILY CLEAN
    // =========================================================
    public function riwayatDailyClean(Request $request): View
    {
        $tanggal = $request->input('tanggal');
        $shift = $request->input('shift');
        $barista = $request->input('barista');

        $records = DailyClean::query()
            ->withCount('photos')
            ->when($tanggal, fn ($q) => $q->where('tanggal', $tanggal))
            ->when($shift, fn ($q) => $q->where('shift', $shift))
            ->when($barista, fn ($q) => $q->where('barista', 'like', '%'.$barista.'%'))
            ->orderByDesc('tanggal')->orderByDesc('id')->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'tanggal' => $r->tanggal ? $r->tanggal->format('Y-m-d') : '',
                'shift' => $r->shift,
                'barista' => $r->barista,
                'jumlah_foto' => $r->photos_count,
            ]);

        return view('manager.riwayat-daily-clean', [
            'title' => 'Riwayat Daily Clean',
            'records' => $records,
            'filter_tanggal' => $tanggal,
            'filter_shift' => $shift,
            'filter_barista' => $barista,
            'shift_list' => shift_list(),
        ]);
    }

    public function dailyCleanDetail(int $id)
    {
        $rec = DailyClean::with('photos')->findOrFail($id);

        return response()->json([
            'tanggal' => $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '',
            'shift' => $rec->shift,
            'barista' => $rec->barista,
            'photos' => $rec->photos->map(fn ($p) => [
                'url' => Storage::disk('public')->url($p->filename),
                'name' => $p->original_name,
            ]),
        ]);
    }

    /**
     * Halaman detail Daily Clean (full page info, bukan modal).
     */
    public function dailyCleanDetailPage(int $id): View
    {
        $rec = DailyClean::with('photos')->findOrFail($id);

        $photos = $rec->photos->map(fn ($p) => [
            'url' => Storage::disk('public')->url($p->filename),
            'original_name' => $p->original_name,
        ]);

        return view('manager.riwayat-daily-clean.detail', [
            'title' => 'Detail Daily Clean',
            'record' => $rec,
            'photos' => $photos,
            'jumlah_foto' => $rec->photos->count(),
        ]);
    }

    public function exportDailyClean()
    {
        $records = DailyClean::query()
            ->withCount('photos')
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        return ExportService::dailyCleanExcel($records, session('name') ?: 'Manager');
    }

    /**
     * Hapus satu data Daily Clean beserta semua file foto yang berkaitan.
     *
     * Proses dalam DB::transaction:
     * 1. Hapus setiap file foto dari storage.
     * 2. Hapus record daily_clean_photo.
     * 3. Hapus folder jika kosong.
     * 4. Hapus record daily_clean.
     */
    public function dailyCleanDestroy(int $id): RedirectResponse
    {
        $record = DailyClean::with('photos')->find($id);

        if (! $record) {
            flash_danger('Data Daily Clean tidak ditemukan.');
            return redirect()->back();
        }

        DB::transaction(function () use ($record) {
            // Hapus semua file foto dari storage
            foreach ($record->photos as $photo) {
                $filePath = $photo->filename;
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
                $photo->delete();
            }

            // Hapus folder tanggal jika kosong
            $firstPhoto = $record->photos->first();
            if ($firstPhoto && $firstPhoto->filename) {
                $folderPath = dirname($firstPhoto->filename);
                if ($folderPath && $folderPath !== '.' && $folderPath !== '/') {
                    $remainingFiles = Storage::disk('public')->allFiles($folderPath);
                    if (empty($remainingFiles)) {
                        Storage::disk('public')->deleteDirectory($folderPath);
                    }
                }
            }

            $record->delete();
        });

        flash_success('Daily Clean berhasil dihapus.');
        return redirect()->back();
    }

    /**
     * Hapus massal beberapa data Daily Clean.
     *
     * Menerima array ID via POST, lalu memproses penghapusan
     * dalam satu transaksi database.
     */
    public function dailyCleanBulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('manager.riwayat.daily-clean');
        }

        // Sanitasi: pastikan semua ID adalah integer
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn ($v) => $v > 0);

        if (empty($ids)) {
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('manager.riwayat.daily-clean');
        }

        $count = count($ids);

        DB::transaction(function () use ($ids) {
            $records = DailyClean::with('photos')->whereIn('id', $ids)->get();

            foreach ($records as $record) {
                // Hapus file foto
                foreach ($record->photos as $photo) {
                    $filePath = $photo->filename;
                    if ($filePath && Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                    $photo->delete();
                }

                // Hapus folder per tanggal jika kosong
                $firstPhoto = $record->photos->first();
                if ($firstPhoto && $firstPhoto->filename) {
                    $folderPath = dirname($firstPhoto->filename);
                    if ($folderPath && $folderPath !== '.' && $folderPath !== '/') {
                        $remainingFiles = Storage::disk('public')->allFiles($folderPath);
                        if (empty($remainingFiles)) {
                            Storage::disk('public')->deleteDirectory($folderPath);
                        }
                    }
                }

                $record->delete();
            }
        });

        flash_success("{$count} data Daily Clean berhasil dihapus.");
        return redirect()->route('manager.riwayat.daily-clean');
    }

    // =========================================================
    // UPDATE STOK DETAIL (FULL PAGE)
    // =========================================================
    /**
     * Halaman detail Update Stok (full page info, bukan modal).
     */
    public function updateStokDetail(int $id): View
    {
        $record = UpdateStok::findOrFail($id);
        $items = [];

        foreach (Bahan::activeKeys() as $kode) {
            $val = $record->{$kode};
            if ($val !== null && $val !== '' && (float)$val > 0) {
                $bahan = Bahan::where('kode', $kode)->first();
                $items[] = [
                    'kode' => $kode,
                    'nama' => $bahan?->nama ?? $kode,
                    'satuan' => $bahan?->satuan ?? '',
                    'nilai' => $val,
                ];
            }
        }

        return view('manager.riwayat-update-stok.detail', [
            'title' => 'Detail Update Stok',
            'record' => $record,
            'items' => $items,
            'tanggal' => $record->tanggal?->format('Y-m-d'),
        ]);
    }

    // =========================================================
    // RIWAYAT TOKEN LISTRIK
    // =========================================================
public function riwayatTokenListrik(Request $request): View
    {
        $tglAwal = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');
        $shift = $request->input('shift');
        $barista = $request->input('barista');

        $records = TokenListrik::query()
            ->when($tglAwal, fn ($q) => $q->where('tanggal', '>=', $tglAwal))
            ->when($tglAkhir, fn ($q) => $q->where('tanggal', '<=', $tglAkhir))
            ->when($shift, fn ($q) => $q->where('shift', $shift))
            ->when($barista, fn ($q) => $q->where('barista', 'like', '%'.$barista.'%'))
            ->orderByDesc('tanggal')->orderByDesc('id')->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'tanggal' => $r->tanggal ? $r->tanggal->format('Y-m-d') : '',
                'shift' => $r->shift,
                'barista' => $r->barista,
                'token_r17' => $r->token_r17,
                'token_r18' => $r->token_r18,
                'token_mesin' => $r->token_mesin,
            ]);

        return view('manager.riwayat-token-listrik', [
            'title' => 'Riwayat Token Listrik',
            'records' => $records,
            'filter_tgl_awal' => $tglAwal,
            'filter_tgl_akhir' => $tglAkhir,
            'filter_shift' => $shift,
            'filter_barista' => $barista,
            'shift_list' => shift_list(),
        ]);
    }

    /**
     * Hapus satu data Token Listrik.
     */
    public function tokenListrikDestroy(int $id): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $record = TokenListrik::find($id);

        if (! $record) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data Token Listrik tidak ditemukan.']);
            }
            flash_danger('Data Token Listrik tidak ditemukan.');
            return redirect()->back();
        }

        $record->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Token Listrik berhasil dihapus.']);
        }

        flash_success('Token Listrik berhasil dihapus.');
        return redirect()->route('manager.riwayat.token-listrik');
    }

    /**
     * Hapus massal beberapa data Token Listrik.
     */
    public function tokenListrikBulkDelete(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu data terlebih dahulu.']);
            }
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('manager.riwayat.token-listrik');
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn ($v) => $v > 0);

        if (empty($ids)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu data terlebih dahulu.']);
            }
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('manager.riwayat.token-listrik');
        }

        $count = count($ids);
        TokenListrik::whereIn('id', $ids)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $count . ' data Token Listrik berhasil dihapus.']);
        }

        flash_success("{$count} data Token Listrik berhasil dihapus.");
        return redirect()->route('manager.riwayat.token-listrik');
    }

    public function exportTokenListrik()
    {
        $records = TokenListrik::query()->orderByDesc('tanggal')->orderByDesc('id')->get();

        return ExportService::tokenListrikExcel($records, session('name') ?: 'Manager');
    }

    /**
     * Halaman detail Token Listrik (full page info, bukan modal).
     */
    public function tokenListrikDetail(int $id): View
    {
        $record = TokenListrik::findOrFail($id);

        return view('manager.riwayat-token-listrik.detail', [
            'title' => 'Detail Token Listrik',
            'record' => $record,
        ]);
    }

    // =========================================================
    // DATA BARISTA (CRUD)
    // =========================================================
    public function dataBarista(): View
    {
        $baristaList = Barista::query()->orderBy('nama_lengkap')->get()->map(fn ($b) => [
            'id' => $b->id,
            'nama_lengkap' => $b->nama_lengkap,
            'no_telp' => $b->no_telp,
            'role' => $b->role,
            'created_at' => $b->created_at ? $b->created_at->format('d-m-Y H:i') : '-',
        ]);

        return view('manager.data-barista', [
            'title' => 'Data Barista',
            'barista_list' => $baristaList,
            'current_user_id' => session('user_id'),
        ]);
    }

    /**
     * Buat username unik dari nama lengkap (meniru kolom username yang
     * ditambahkan di migrasi Laravel untuk mendukung dropdown login).
     * Tidak mengubah struktur database; hanya mengisi kolom yang wajib ada.
     */
    private function makeBaristaUsername(string $namaLengkap, ?int $excludeId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $namaLengkap), '_'));
        if ($base === '') {
            $base = 'barista';
        }

        $username = $base;
        $i = 1;
        while (Barista::where('username', $username)->where('id', '<>', $excludeId ?? 0)->exists()) {
            $i++;
            $username = $base.'_'.$i;
        }

        return $username;
    }

    /**
     * Validasi input barista meniru Flask (modules/barista_manager.py::validate):
     *  - nama lengkap wajib
     *  - no_telp minimal 10 digit
     *  - role harus barista|manager
     *  - no_telp unik (abaikan id sendiri saat edit)
     */
    private function validateBarista(Request $request, ?int $excludeId = null): ?RedirectResponse
    {
        $nama = trim((string) $request->input('nama_lengkap', ''));
        $noTelp = trim((string) $request->input('no_telp', ''));
        $role = trim((string) $request->input('role', ''));

        if ($nama === '') {
            flash_danger('Nama lengkap tidak boleh kosong.');

            return back()->withInput();
        }

        $digitCount = preg_match_all('/\d/', $noTelp);
        if ($digitCount < 10) {
            flash_danger('Nomor telepon minimal 10 digit.');

            return back()->withInput();
        }

        if (! in_array($role, ['barista', 'manager'], true)) {
            flash_danger('Role tidak valid.');

            return back()->withInput();
        }

        if (Barista::where('no_telp', $noTelp)->where('id', '<>', $excludeId ?? 0)->exists()) {
            flash_danger('Nomor telepon sudah terdaftar.');

            return back()->withInput();
        }

        return null;
    }

    /**
     * Halaman form tambah Barista (full page).
     */
    public function baristaCreate(): View
    {
        return view('manager.data-barista.create', [
            'title' => 'Tambah Barista',
        ]);
    }

    /**
     * Proses simpan Barista baru (full page form).
     */
    public function baristaStore(Request $request): RedirectResponse
    {
        $redirect = $this->validateBarista($request);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $nama = trim((string) $request->input('nama_lengkap'));
        $noTelp = trim((string) $request->input('no_telp'));
        $role = trim((string) $request->input('role'));

        Barista::create([
            'username' => $this->makeBaristaUsername($nama),
            'nama_lengkap' => $nama,
            'no_telp' => $noTelp,
            'role' => $role,
        ]);

        flash_success('Barista berhasil ditambahkan.');

        return redirect()->route('manager.data-barista');
    }

    /**
     * Halaman detail Barista (full page info, bukan modal).
     */
    public function baristaDetail(int $id): View
    {
        $barista = Barista::findOrFail($id);

        return view('manager.data-barista.detail', [
            'title' => 'Detail Barista',
            'barista' => $barista,
        ]);
    }

    public function baristaAdd(Request $request): RedirectResponse
    {
        $redirect = $this->validateBarista($request);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $nama = trim((string) $request->input('nama_lengkap'));
        $noTelp = trim((string) $request->input('no_telp'));
        $role = trim((string) $request->input('role'));

        Barista::create([
            'username' => $this->makeBaristaUsername($nama),
            'nama_lengkap' => $nama,
            'no_telp' => $noTelp,
            'role' => $role,
        ]);

        flash_success('Barista berhasil ditambahkan.');

        return redirect()->route('manager.data-barista');
    }

    /**
     * Halaman form edit Barista (full page, bukan modal).
     *
     * Mengikuti pola Edit Master Bahan: menampilkan form dalam card besar
     * dengan data barista yang sudah ada.
     */
    public function baristaEditForm(int $id): View
    {
        $barista = Barista::findOrFail($id);

        return view('manager.data-barista.edit', [
            'title' => 'Edit Barista',
            'barista' => $barista,
        ]);
    }

    public function baristaEdit(Request $request, int $id): RedirectResponse
    {
        $barista = Barista::findOrFail($id);

        $redirect = $this->validateBarista($request, $barista->id);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $nama = trim((string) $request->input('nama_lengkap'));
        $noTelp = trim((string) $request->input('no_telp'));
        $role = trim((string) $request->input('role'));

        $barista->update([
            'nama_lengkap' => $nama,
            'no_telp' => $noTelp,
            'role' => $role,
        ]);

        flash_success('Barista berhasil diperbarui.');

        return redirect()->route('manager.data-barista');
    }

    public function baristaDelete(int $id): RedirectResponse
    {
        if ($id == session('user_id')) {
            flash_danger('Tidak dapat menghapus akun sendiri.');

            return redirect()->route('manager.data-barista');
        }

        Barista::findOrFail($id)->delete();
        flash_success('Barista berhasil dihapus.');

        return redirect()->route('manager.data-barista');
    }

    // =========================================================
    // PENGATURAN LIMIT
    // =========================================================
    public function pengaturanLimit(): View
    {
        $limits = Bahan::with('limit')
            ->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->select('id', 'kode', 'nama', 'satuan', 'kategori', 'urutan')
            ->get()
            ->map(function ($b) {
                return (object) [
                    'id' => $b->id,
                    'kode' => $b->kode,
                    'nama' => $b->nama,
                    'satuan' => $b->satuan,
                    'limit_habis' => $b->limit?->limit_habis ?? StockAnalytics::DEFAULT_LIMIT_HABIS,
                    'limit_tipis' => $b->limit?->limit_tipis ?? StockAnalytics::DEFAULT_LIMIT_TIPIS,
                ];
            });

        return view('manager.pengaturan-limit', [
            'title' => 'Pengaturan Limit Stok',
            'limits' => $limits,
        ]);
    }

    public function pengaturanLimitSimpan(Request $request): RedirectResponse
    {
        $request->validate([
            'bahan_id' => 'required|exists:bahan,id',
            'limit_habis' => 'required|numeric|min:0',
            'limit_tipis' => 'required|numeric|min:0',
        ]);

        BahanLimit::updateOrCreate(
            ['bahan_id' => $request->input('bahan_id')],
            [
                'limit_habis' => $request->input('limit_habis'),
                'limit_tipis' => $request->input('limit_tipis'),
            ]
        );

        flash_success('Limit stok berhasil disimpan.');

        return redirect()->route('manager.pengaturan-limit');
    }

    /**
     * Halaman form edit limit stok untuk satu bahan (full page, bukan modal).
     *
     * Mengikuti pola Edit Master Bahan: card besar dengan data limit yang sudah ada.
     */
    public function pengaturanLimitEdit(int $id): View
    {
        $bahan = Bahan::with('limit')->findOrFail($id);

        return view('manager.pengaturan-limit.edit', [
            'title' => 'Edit Limit Stok',
            'bahan' => $bahan,
            'limit_habis' => $bahan->limit?->limit_habis ?? StockAnalytics::DEFAULT_LIMIT_HABIS,
            'limit_tipis' => $bahan->limit?->limit_tipis ?? StockAnalytics::DEFAULT_LIMIT_TIPIS,
        ]);
    }

    /**
     * Proses update limit stok untuk satu bahan.
     */
    public function pengaturanLimitUpdate(Request $request, int $id): RedirectResponse
    {
        $bahan = Bahan::findOrFail($id);

        $request->validate([
            'limit_habis' => 'required|numeric|min:0',
            'limit_tipis' => 'required|numeric|min:0',
        ]);

        BahanLimit::updateOrCreate(
            ['bahan_id' => $bahan->id],
            [
                'limit_habis' => $request->input('limit_habis'),
                'limit_tipis' => $request->input('limit_tipis'),
            ]
        );

        flash_success('Limit stok berhasil disimpan.');

        return redirect()->route('manager.pengaturan-limit');
    }

    // =========================================================
    // LAPORAN
    // =========================================================
    public function laporan(Request $request): View
    {
        $tglAwal = $request->input('tanggal_awal');
        $tglAkhir = $request->input('tanggal_akhir');

        $summary = null;
        if ($tglAwal && $tglAkhir) {
            // Hitung sekali saja: read data, limit map, dan label map
            // (tidak ada query duplikat untuk forecast/top habis/top tipis).
            $data = StockAnalytics::readUpdateStok();
            $limitMap = StockAnalytics::limitMap();
            $keyToLabel = StockAnalytics::keyToLabel();

            // Filter baris dalam rentang tanggal (homogen dengan Flask:
            // hanya baris yang tanggalnya antara tglAwal dan tglAkhir).
            $periodeRows = array_filter($data['rows'], function ($r) use ($tglAwal, $tglAkhir) {
                return $tglAwal <= $r['tanggal'] && $r['tanggal'] <= $tglAkhir;
            });
            $totalUpdateStok = count($periodeRows);

            $aman = $tipis = $habis = 0;
            $last = $data['last_row'];
            if ($last) {
                foreach ($data['item_keys'] as $key) {
                    [$lh, $lt] = $limitMap[$key] ?? [StockAnalytics::DEFAULT_LIMIT_HABIS, StockAnalytics::DEFAULT_LIMIT_TIPIS];
                    $v = StockAnalytics::toFloat($last['values'][$key] ?? null);
                    if ($v === null || $v <= $lh) {
                        $habis++;
                    } elseif ($v <= $lt) {
                        $tipis++;
                    } else {
                        $aman++;
                    }
                }
            }

            $forecast = StockAnalytics::forecast($tglAwal, $tglAkhir, $data, $limitMap);

            // Format periode label dengan Bahasa Indonesia (homolog Flask _format_tanggal_id)
            $periodeLabel = format_tanggal_id($tglAwal).' - '.format_tanggal_id($tglAkhir);

            $summary = [
                'periode_label' => $periodeLabel,
                'total_update_stok' => $totalUpdateStok,
                'barang_aman' => $aman,
                'barang_tipis' => $tipis,
                'barang_habis' => $habis,
                'has_data' => $data['has_data'],
                'top_barang_habis' => StockAnalytics::topHabis($data, $limitMap, $keyToLabel),
                'top_barang_tipis' => StockAnalytics::topTipis($data, $limitMap, $keyToLabel),
                'aktivitas_barista' => StockAnalytics::aktivitasBarista($data),
                'total_kebutuhan' => $forecast['total_kebutuhan'],
                'total_estimasi_pembelian' => $forecast['total_estimasi_pembelian'],
                'forecast_items' => $forecast['items'],
                'forecast_items_tree' => $forecast['items_tree'],
            ];
        }

        return view('manager.laporan', [
            'title' => 'Laporan',
            'tanggal_awal' => $tglAwal,
            'tanggal_akhir' => $tglAkhir,
            'summary' => $summary,
        ]);
    }

    public function laporanExport(Request $request)
    {
        $tglAwal = $request->input('tanggal_awal');
        $tglAkhir = $request->input('tanggal_akhir');

        return ExportService::laporanPdf($tglAwal, $tglAkhir, session('name') ?: 'Manager');
    }

    // =========================================================
    // FORECAST
    // =========================================================
    public function forecast(Request $request): View
    {
        $tglAwal = $request->input('tanggal_awal');
        $tglAkhir = $request->input('tanggal_akhir');

        $data = StockAnalytics::forecast($tglAwal, $tglAkhir);
        $periodeValid = $data['periode_valid'];

        return view('manager.forecast', [
            'title' => 'Forecast Mingguan',
            'tanggal_awal' => $tglAwal,
            'tanggal_akhir' => $tglAkhir,
            'periode_valid' => $periodeValid,
            'items_tree' => $data['items_tree'],
            'total_kebutuhan' => $data['total_kebutuhan'],
            'total_estimasi_pembelian' => $data['total_estimasi_pembelian'],
            'jumlah_hari' => $data['jumlah_hari'],
        ]);
    }

    public function forecastExportExcel(Request $request)
    {
        $tglAwal = $request->input('tanggal_awal');
        $tglAkhir = $request->input('tanggal_akhir');
        $data = StockAnalytics::forecast($tglAwal, $tglAkhir);
        $periode = ($tglAwal && $tglAkhir) ? "$tglAwal s.d. $tglAkhir" : '-';

        return ExportService::forecastExcel($data, $periode, session('name') ?: 'Manager');
    }

    public function forecastExportPdf(Request $request)
    {
        $tglAwal = $request->input('tanggal_awal');
        $tglAkhir = $request->input('tanggal_akhir');
        $data = StockAnalytics::forecast($tglAwal, $tglAkhir);

        return ExportService::forecastPdf($data, session('name') ?: 'Manager');
    }

    // =========================================================
    // EDIT AKUN SAYA (UPDATE PROFILE)
    // =========================================================

    /**
     * Update profil Manager yang sedang login.
     *
     * Method ini memungkinkan Manager mengubah:
     *   - Nama (nama_lengkap)
     *   - Username (jika diisi, unique kecuali dirinya sendiri)
     *   - Password (opsional):
     *       * Password lama wajib diisi jika ingin ganti password.
     *       * Password baru minimal 8 karakter.
     *       * Konfirmasi password baru wajib sama.
     *       * Jika password baru kosong, password lama tetap digunakan.
     *
     * Mendukung dua mode response:
     *   - AJAX (X-Requested-With: XMLHttpRequest) -> JSON
     *   - Biasa (browser normal) -> Redirect + session flash
     */
    public function updateProfile(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $managerId = (int) session('user_id');
        $manager = Manager::findOrFail($managerId);

        $nama = trim((string) $request->input('nama', ''));
        $username = trim((string) $request->input('username', ''));
        $passwordLama = (string) $request->input('password_lama', '');
        $passwordBaru = (string) $request->input('password_baru', '');
        $passwordKonfirmasi = (string) $request->input('password_baru_confirmation', '');

        $errors = [];

        // ---- Validasi Nama ----
        if ($nama === '') {
            $errors['nama'] = ['Nama lengkap wajib diisi.'];
        }

        // ---- Validasi Username (unique) ----
        if ($username !== '') {
            $exists = Manager::where('username', $username)
                ->where('id', '<>', $managerId)
                ->exists();
            if ($exists) {
                $errors['username'] = ['Username sudah digunakan oleh akun lain.'];
            }
        }

        // ---- Validasi Password (jika ingin ganti) ----
        $gantiPassword = $passwordBaru !== '';

        if ($gantiPassword) {
            // Password lama wajib diisi
            if ($passwordLama === '') {
                $errors['password_lama'] = ['Password lama harus diisi jika ingin mengganti password.'];
            } else {
                // Verifikasi password lama:
                // - Jika sudah ada hash (non-legacy): gunakan Hash::check()
                // - Jika belum ada hash (legacy account): fallback ke 6 digit terakhir no_telp
                if ($manager->password) {
                    // Akun non-legacy — verifikasi dengan hash yang tersimpan
                    if (! Hash::check($passwordLama, $manager->password)) {
                        $errors['password_lama'] = ['Password lama tidak sesuai.'];
                    }
                } else {
                    // Legacy account (belum pernah ganti password) — verifikasi dengan 6 digit terakhir no_telp
                    $expectedPassword = substr((string) $manager->no_telp, -6);
                    if ($passwordLama !== $expectedPassword) {
                        $errors['password_lama'] = ['Password lama tidak sesuai. Gunakan 6 digit terakhir nomor telepon.'];
                    }
                }
            }

            // Password baru minimal 8 karakter
            if (strlen($passwordBaru) < 8) {
                $errors['password_baru'] = ['Password baru minimal 8 karakter.'];
            }

            // Konfirmasi password baru
            if ($passwordBaru !== $passwordKonfirmasi) {
                $errors['password_baru_confirmation'] = ['Konfirmasi password baru tidak sesuai.'];
            }
        }

        // ---- Jika ada error, return response sesuai mode request ----
        if (count($errors) > 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors' => $errors,
                ], 422);
            }

            // Mode biasa (redirect)
            foreach ($errors as $field => $msgs) {
                foreach ($msgs as $msg) {
                    flash_danger($msg);
                }
            }
            return back()->withInput();
        }

        // ---- Update data ----
        $updateData = [
            'nama_lengkap' => $nama,
        ];

        if ($username !== '') {
            $updateData['username'] = $username;
        }

        if ($gantiPassword) {
            // Simpan plain string — biarkan Model cast 'hashed' meng-hash otomatis
            $updateData['password'] = $passwordBaru;
        }

        $manager->update($updateData);

        // ---- Update session ----
        session()->put('name', $nama);
        if ($username !== '') {
            session()->put('username', $username);
        }

        // ---- Response sukses ----
        if ($request->wantsJson() || $request->ajax()) {
            $response = [
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'nama' => $nama,
                'username' => $username ?: session('username'),
            ];

            // Jika password diubah: terminate session, redirect ke login
            if ($gantiPassword) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $response['session_terminated'] = true;
                $response['message'] = 'Password berhasil diubah. Silakan login kembali dengan password baru.';
                $response['redirect_url'] = route('login');
            }

            return response()->json($response);
        }

        // Non-AJAX: jika password diubah, logout
        if ($gantiPassword) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            flash_success('Password berhasil diubah. Silakan login kembali dengan password baru.');
            return redirect()->route('login');
        }

        flash_success('Profil berhasil diperbarui.');
        return back();
    }
}
