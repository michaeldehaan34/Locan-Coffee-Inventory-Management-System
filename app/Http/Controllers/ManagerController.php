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

        $allowedRoles = ['barista', 'manajemen', 'headbar', 'kitchen', 'headkitchen', 'admin gudang'];
        if (! in_array($role, $allowedRoles, true)) {
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

        $username = trim((string) $request->input('username', ''));
        if ($username === '') {
            flash_danger('Username tidak boleh kosong.');
            return back()->withInput();
        }

        $existsInBarista = Barista::where('username', $username)->where('id', '<>', $barista->id)->exists();
        $existsInManager = Manager::where('username', $username)->exists();

        if ($existsInBarista || $existsInManager) {
            flash_danger('Username sudah digunakan.');
            return back()->withInput();
        }

        $redirect = $this->validateBarista($request, $barista->id);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $nama = trim((string) $request->input('nama_lengkap'));
        $noTelp = trim((string) $request->input('no_telp'));
        $role = trim((string) $request->input('role'));

        $barista->update([
            'username' => $username,
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
    public function pengaturanLimit(Request $request): View
    {
        $type = $request->input('type', 'coffee_shop'); // Default coffee_shop

        $limits = Bahan::where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->select('id', 'kode', 'nama', 'satuan', 'kategori', 'urutan')
            ->get()
            ->map(function ($b) use ($type) {
                // Get the limit for this specific type
                $lim = BahanLimit::where('bahan_id', $b->id)->where('inventory_type', $type)->first();
                return (object) [
                    'id' => $b->id,
                    'kode' => $b->kode,
                    'nama' => $b->nama,
                    'satuan' => $b->satuan,
                    'limit_habis' => $lim->limit_habis ?? StockAnalytics::DEFAULT_LIMIT_HABIS,
                    'limit_tipis' => $lim->limit_tipis ?? StockAnalytics::DEFAULT_LIMIT_TIPIS,
                ];
            });

        return view('manager.pengaturan-limit', [
            'title' => 'Pengaturan Limit Stok (' . ucfirst(str_replace('_', ' ', $type)) . ')',
            'limits' => $limits,
            'inventory_type' => $type,
        ]);
    }

    // =========================================================
    // TERIMA STOK (Coffeeshop)
    // =========================================================
    
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
            
        return view('manager.coffee-shop.terima-stok.index', [
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
        
        return view('manager.coffee-shop.terima-stok.detail', [
            'title' => 'Detail Terima Stok',
            'record' => $record,
            'source' => $source,
        ]);
    }

    public function terimaStokEdit(int $id): View
    {
        $record = \App\Models\GudangKirimStok::with('items.bahan')->findOrFail($id);
        $defaultData = [
            'tanggal' => $record->tanggal,
        ];
        foreach ($record->items as $item) {
            $defaultData[$item->bahan->kode] = $item->jumlah;
        }

        return view('manager.coffee-shop.terima-stok.edit', [
            'title' => 'Edit Terima Stok',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'default_data' => $defaultData,
        ]);
    }

    public function terimaStokUpdate(Request $request, int $id): RedirectResponse
    {
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
        return redirect()->route('manager.coffee-shop.terima-stok.index');
    }

    public function terimaStokDestroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $record = \App\Models\GudangKirimStok::findOrFail($id);
            $record->delete();
        });

        flash_success('Transaksi terima stok berhasil dihapus.');
        return redirect()->route('manager.coffee-shop.terima-stok.index');
    }

    public function dashboard(Request $request): View
    {
        $type = $request->query('type', 'kitchen');
        if (!in_array($type, ['kitchen', 'coffee_shop', 'gudang'])) {
            $type = 'kitchen';
        }

        $data = StockAnalytics::dashboard($type);
        
        $titles = [
            'kitchen' => 'Dashboard Stok Kitchen Terkini',
            'coffee_shop' => 'Dashboard Stok Coffeeshop Terkini',
            'gudang' => 'Dashboard Stok Gudang Terkini',
        ];

        return view('manager.dashboard', [
            'title' => $titles[$type],
            'data' => $data,
            'inventory_type' => $type,
            'hideLoader' => true,
        ]);
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

    /**
     * Hapus satu data Update Stok.
     */
    public function updateStokDestroy(int $id): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $record = \App\Models\UpdateStok::find($id);

        if (! $record) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data Update Stok tidak ditemukan.']);
            }
            flash_danger('Data Update Stok tidak ditemukan.');
            return redirect()->back();
        }

        $record->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Update Stok berhasil dihapus.']);
        }

        flash_success('Update Stok berhasil dihapus.');
        return redirect()->route('manager.riwayat.update-stok');
    }

    /**
     * Hapus massal beberapa data Update Stok.
     */
    public function updateStokBulkDelete(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu data untuk dihapus.']);
            }
            flash_danger('Pilih minimal satu data untuk dihapus.');
            return redirect()->back();
        }

        $count = \App\Models\UpdateStok::whereIn('id', $ids)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $count . ' data Update Stok berhasil dihapus.']);
        }

        flash_success("{$count} data Update Stok berhasil dihapus.");
        return redirect()->route('manager.riwayat.update-stok');
    }


    /**
     * Hapus massal beberapa data Terima Stok.
     */
    public function terimaStokBulkDelete(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu data untuk dihapus.']);
            }
            flash_danger('Pilih minimal satu data untuk dihapus.');
            return redirect()->back();
        }

        $count = \App\Models\StokMasuk::whereIn('id', $ids)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $count . ' data Terima Stok berhasil dihapus.']);
        }

        flash_success("{$count} data Terima Stok berhasil dihapus.");
        return redirect()->route('manager.riwayat.terima-stok');
    }
}

