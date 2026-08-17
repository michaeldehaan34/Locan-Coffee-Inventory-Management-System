<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\UpdateStok;
use App\Models\DailyClean;
use App\Models\TokenListrik;
use Illuminate\Support\Facades\Storage;
use App\Models\StokMasuk;
use App\Models\GudangKirimStok;
use App\Models\GudangKirimStokItem;
use App\Services\StockAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HeadbarController extends Controller
{
    public function dashboardCS(): View
    {
        $data = StockAnalytics::dashboard('coffee_shop');
        
        return view('headbar.dashboard-cs', [
            'title' => 'Dashboard Coffeeshop',
            'data' => $data,
            'hideLoader' => true,
        ]);
    }

    public function terimaStokIndex(Request $request): View
    {
        $kirimStok = \App\Models\GudangKirimStok::with('items.bahan')->get()->map(function($item) {
            $item->source = 'gudang_kirim';
            $item->pelaku = $item->manager;
            return $item;
        });

        $ambilBahan = \App\Models\AmbilBahanGudang::with('items.bahan')->get()->map(function($item) {
            $item->source = 'ambil_bahan_gudang';
            $item->pelaku = $item->barista;
            $item->status = 'diterima'; 
            return $item;
        });

        $combined = $kirimStok->concat($ambilBahan);
        
        $sorted = $combined->sort(function($a, $b) {
            $aStatusOrder = $a->status === 'pending' ? 0 : 1;
            $bStatusOrder = $b->status === 'pending' ? 0 : 1;
            
            if ($aStatusOrder !== $bStatusOrder) {
                return $aStatusOrder <=> $bStatusOrder;
            }
            
            $aTime = $a->created_at ? $a->created_at->timestamp : strtotime($a->tanggal);
            $bTime = $b->created_at ? $b->created_at->timestamp : strtotime($b->tanggal);
            
            if ($aTime !== $bTime) {
                return $bTime <=> $aTime; 
            }
            
            return $b->id <=> $a->id;
        })->values();

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $sliced = $sorted->slice(($page - 1) * $perPage, $perPage);
        
        $records = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
            
        return view('headbar.coffee-shop.terima-stok.index', [
            'title' => 'Terima Stok dari Gudang',
            'records' => $records,
        ]);
    }

    public function terimaStokDetail(Request $request, int $id): View
    {
        $source = $request->query('source', 'gudang_kirim');
        
        if ($source === 'ambil_bahan_gudang') {
            $record = \App\Models\AmbilBahanGudang::with('items.bahan')->findOrFail($id);
            $record->source = 'ambil_bahan_gudang';
            $record->pelaku = $record->barista;
            $record->status = 'diterima';
        } else if ($source === 'gudang_kirim') {
            $record = \App\Models\GudangKirimStok::with('items.bahan')->findOrFail($id);
            $record->source = 'gudang_kirim';
            $record->pelaku = $record->manager;
        } else {
            abort(404, 'Invalid source type');
        }
        
        return view('headbar.coffee-shop.terima-stok.detail', [
            'title' => 'Detail Terima Stok',
            'record' => $record,
            'source' => $source,
        ]);
    }

    public function terimaStokEdit(Request $request, int $id): View
    {
        $source = $request->query('source', 'gudang_kirim');
        if ($source === 'ambil_bahan_gudang') {
            abort(403, 'Transaksi Ambil Bahan Gudang tidak dapat diubah.');
        }

        $record = \App\Models\GudangKirimStok::with('items.bahan')->findOrFail($id);
        $defaultData = [
            'tanggal' => $record->tanggal,
        ];
        foreach ($record->items as $item) {
            $defaultData[$item->bahan->kode] = $item->jumlah;
        }

        return view('headbar.coffee-shop.terima-stok.edit', [
            'title' => 'Edit Terima Stok',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'default_data' => $defaultData,
        ]);
    }

    public function terimaStokUpdate(Request $request, int $id): RedirectResponse
    {
        $source = $request->query('source', 'gudang_kirim');
        if ($source === 'ambil_bahan_gudang') {
            abort(403, 'Transaksi Ambil Bahan Gudang tidak dapat diubah.');
        }

        $tanggal = $request->input('tanggal');
        if (empty($tanggal)) {
            flash_danger('Tanggal harus diisi.');
            return back()->withInput();
        }

        DB::transaction(function () use ($request, $tanggal, $id) {
            $record = \App\Models\GudangKirimStok::findOrFail($id);
            $record->update([
                'tanggal' => $tanggal,
            ]);

            \App\Models\GudangKirimStokItem::where('gudang_kirim_stok_id', $record->id)->delete();

            $activeItems = Bahan::activeItems();
            foreach ($activeItems as $bahan) {
                $jumlah = $request->input($bahan['kode']);
                if ($jumlah !== null && $jumlah !== '' && is_numeric($jumlah) && (float)$jumlah > 0) {
                    \App\Models\GudangKirimStokItem::create([
                        'gudang_kirim_stok_id' => $record->id,
                        'bahan_id' => $bahan['id'],
                        'jumlah' => (float)$jumlah,
                    ]);
                }
            }
        });

        flash_success('Transaksi terima stok berhasil diperbarui.');
        return redirect()->route('headbar.coffee-shop.terima-stok.index');
    }

    public function terimaStokDestroy(Request $request, int $id): RedirectResponse
    {
        $source = $request->query('source', 'gudang_kirim');
        if ($source === 'ambil_bahan_gudang') {
            abort(403, 'Transaksi Ambil Bahan Gudang tidak dapat dihapus.');
        }

        DB::transaction(function () use ($id) {
            $record = \App\Models\GudangKirimStok::findOrFail($id);
            $record->delete();
        });

        flash_success('Transaksi terima stok berhasil dihapus.');
        return redirect()->route('headbar.coffee-shop.terima-stok.index');
    }

    // RIWAYAT UPDATE STOK
    // =========================================================
    public function riwayatUpdateStok(Request $request): View
    {
        $barang = $request->input('barang');
        $tglAwal = $request->input('tgl_awal');
        $tglPembanding = $request->input('tgl_pembanding');

        $records = StockAnalytics::allUpdateStok($barang, 'coffee_shop');
        $stats = StockAnalytics::updateStokStats($records);

        $comparison = $tglAwal || $tglPembanding
            ? StockAnalytics::comparisonForDates($tglAwal, $tglPembanding)
            : ['requested' => false, 'has_data' => false, 'tanggal_valid' => true, 'tanggal_awal' => '-', 'tanggal_pembanding' => '-', 'items' => []];

        return view('headbar.riwayat-update-stok', [
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

        return view('headbar.update-stok.edit', [
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
        $shift = '-';
        $barista = (string) $request->input('barista', '');

        if ($tanggal === '') {
            flash_danger('Tanggal harus diisi.');
            return back()->withInput();
        }
        if (! is_valid_date($tanggal)) {
            flash_danger('Format tanggal tidak valid.');
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

        return redirect()->route('headbar.riwayat.update-stok');
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

        return redirect()->route('headbar.riwayat.update-stok');
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

        return view('headbar.riwayat-daily-clean', [
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
                'url' => asset('storage/' . ltrim($p->filename ?? '', '/')),
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
            'url' => \Illuminate\Support\Facades\Storage::url($p->filename),
            'original_name' => $p->original_name,
        ]);

        return view('headbar.riwayat-daily-clean.detail', [
            'title' => 'Detail Daily Clean',
            'record' => $rec,
            'photos' => $photos,
            'jumlah_foto' => $rec->photos->count(),
        ]);
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
            return redirect()->route('headbar.riwayat.daily-clean');
        }

        // Sanitasi: pastikan semua ID adalah integer
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn ($v) => $v > 0);

        if (empty($ids)) {
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('headbar.riwayat.daily-clean');
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
        return redirect()->route('headbar.riwayat.daily-clean');



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
        $activeKeys = Bahan::activeKeys();
        $bahanMap = Bahan::query()
            ->whereIn('kode', $activeKeys)
            ->get(['kode', 'nama', 'kategori', 'kelompok', 'satuan'])
            ->keyBy('kode');
        $items = [];

        foreach ($activeKeys as $kode) {
            $val = $record->{$kode};
            if ($val !== null && $val !== '' && (float) $val > 0) {
                $bahan = $bahanMap->get($kode);
                $items[] = [
                    'kode' => $kode,
                    'nama' => $bahan?->nama ?? $kode,
                    'kategori' => $bahan?->kategori ?: '-',
                    'kelompok' => $bahan?->kelompok ?: '-',
                    'satuan' => $bahan?->satuan ?: '-',
                    'nilai' => $val,
                ];
            }
        }

        return view('headbar.riwayat-update-stok.detail', [
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
            ->orderByDesc('tanggal')->orderByDesc('id')->take(1)->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'tanggal' => $r->tanggal ? $r->tanggal->format('Y-m-d') : '',
                'shift' => $r->shift,
                'barista' => $r->barista,
                'token_listrik_total' => (float)$r->token_r17 + (float)$r->token_r18 + (float)$r->token_mesin,
            ]);

        return view('headbar.riwayat-token-listrik', [
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
        return redirect()->route('headbar.riwayat.token-listrik');
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
            return redirect()->route('headbar.riwayat.token-listrik');
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn ($v) => $v > 0);

        if (empty($ids)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu data terlebih dahulu.']);
            }
            flash_danger('Pilih minimal satu data terlebih dahulu.');
            return redirect()->route('headbar.riwayat.token-listrik');
        }

        $count = count($ids);
        TokenListrik::whereIn('id', $ids)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $count . ' data Token Listrik berhasil dihapus.']);
        }

        flash_success("{$count} data Token Listrik berhasil dihapus.");
        return redirect()->route('headbar.riwayat.token-listrik');
    }

    

    /**
     * Halaman detail Token Listrik (full page info, bukan modal).
     */
    public function tokenListrikDetail(int $id): View
    {
        $record = TokenListrik::findOrFail($id);

        return view('headbar.riwayat-token-listrik.detail', [
            'title' => 'Detail Token Listrik',
            'record' => $record,
        ]);
    }

    // =========================================================

}
