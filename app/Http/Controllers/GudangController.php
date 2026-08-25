<?php

namespace App\Http\Controllers;

use App\Http\Requests\StokMasukRequest;
use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Models\GudangKirimStok;
use App\Models\GudangKirimStokItem;
use App\Services\StockAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GudangController extends Controller
{
    // =========================================================
    // DASHBOARD GUDANG
    // =========================================================
    public function dashboard(): View
    {
        $data = StockAnalytics::dashboard('gudang');
        
        return view('manager.dashboard-gudang', [
            'title' => 'Dashboard Gudang',
            'data' => $data,
            'hideLoader' => true,
        ]);
    }

    // =========================================================
    // STOK MASUK GUDANG (Dari Supplier)
    // =========================================================
    public function stokMasukIndex(Request $request): View
    {
        $tglAwal = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');
        $shift = $request->input('shift');
        $barista = $request->input('barista');
        $barang = $request->input('barang');

        $transactions = StockAnalytics::groupedStokMasuk(
            $tglAwal,
            $tglAkhir,
            $shift,
            $barista,
            $barang
        );
        $stats = StockAnalytics::stokMasukStats($transactions);

        $rows = [];
        foreach ($transactions as $t) {
            $rows[] = [
                'id' => $t['id'],
                'tanggal_display' => $t['tanggal_display'],
                'shift' => $t['shift'],
                'barista' => $t['barista'],
                'jumlah_item' => $t['jumlah_item'],
            ];
        }

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = Collection::make($rows);
        $paginator = new LengthAwarePaginator(
            $collection->forPage($currentPage, $perPage)->values(),
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('manager.gudang.stok-masuk.index', [
            'title' => 'Riwayat Stok Masuk Gudang',
            'rows' => $paginator->items(),
            'paginator' => $paginator,
            'records' => array_values($transactions),
            'stats' => $stats,
            'filter_tgl_awal' => $tglAwal,
            'filter_tgl_akhir' => $tglAkhir,
            'filter_shift' => $shift,
            'filter_barista' => $barista,
            'filter_barang' => $barang,
            'shift_list' => shift_list(),
            'barang_suggestions' => Bahan::activeItems(),
        ]);
    }

    public function stokMasukCreate(): View
    {
        return view('manager.gudang.stok-masuk.create', [
            'title' => 'Tambah Stok Masuk Gudang',
            'bahan_tree' => Bahan::groupedActiveTree(),
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
            'default_data' => session('form_data', []),
        ]);
    }

    public function stokMasukStore(StokMasukRequest $request): RedirectResponse
    {
        StokMasuk::create($request->validatedStokData());

        flash_success('Data stok masuk berhasil disimpan.');

        return redirect()->route('gudang.stok-masuk.index');
    }

    public function stokMasukDetail(int $id): View
    {
        $record = StokMasuk::findOrFail($id);
        $items = [];

        foreach (Bahan::activeKeys() as $kode) {
            $val = $record->{$kode};
            if ($val !== null && $val !== '' && (float)$val > 0) {
                $bahan = Bahan::where('kode', $kode)->first();
                $items[] = [
                    'kode' => $kode,
                    'nama' => $bahan?->nama ?? $kode,
                    'kelompok' => $bahan?->kelompok ?? '-',
                    'kategori' => $bahan?->kategori ?? '-',
                    'satuan' => $bahan?->satuan ?? '',
                    'nilai' => $val,
                ];
            }
        }

        return view('manager.gudang.stok-masuk.detail', [
            'title' => 'Detail Stok Masuk',
            'record' => $record,
            'items' => $items,
        ]);
    }

    public function stokMasukEdit(int $id): View
    {
        $record = StokMasuk::findOrFail($id);

        $defaultData = [
            'tanggal' => $record->tanggal?->format('Y-m-d'),
            'shift' => $record->shift,
            'barista' => $record->barista,
        ];

        foreach (Bahan::activeKeys() as $kode) {
            $defaultData[$kode] = $record->{$kode};
        }

        return view('manager.gudang.stok-masuk.edit', [
            'title' => 'Edit Stok Masuk',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'shift_list' => shift_list(),
            'barista_name' => $record->barista,
            'default_data' => $defaultData,
        ]);
    }

    public function stokMasukUpdate(StokMasukRequest $request, int $id): RedirectResponse
    {
        $record = StokMasuk::findOrFail($id);
        $record->update($request->validatedStokData());

        flash_success('Data stok masuk berhasil diperbarui.');

        return redirect()->route('gudang.stok-masuk.index');
    }

    public function stokMasukDestroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $record = StokMasuk::findOrFail($id);
            $record->delete();
        });

        flash_success('Data stok masuk berhasil dihapus.');

        return redirect()->route('gudang.stok-masuk.index');
    }

    // =========================================================
    // KIRIM STOK GUDANG (Gudang -> Coffeeshop)
    // =========================================================
    public function kirimStokIndex(Request $request): View
    {
        $kirimStok = \App\Models\GudangKirimStok::with(['items.bahan', 'user'])->get()->map(function($item) {
            $item->source = 'gudang_kirim';
            $item->pelaku = $item->user ? $item->user->nama_lengkap : $item->manager;
            $item->pelaku_role = $item->user ? $item->user->role : '';
            $item->waktu_wib = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            return $item;
        });

        $ambilBahan = \App\Models\AmbilBahanGudang::with(['items.bahan', 'user'])->get()->map(function($item) {
            $item->source = 'ambil_bahan_gudang';
            $item->pelaku = $item->user ? $item->user->nama_lengkap : $item->barista;
            
            if (empty($item->inventory_type)) {
                if ($item->user && in_array($item->user->role, ['kitchen', 'headkitchen'])) {
                    $item->inventory_type = 'kitchen';
                } else {
                    $item->inventory_type = 'coffee_shop';
                }
            }
            
            $item->pelaku_role = $item->user 
                ? $item->user->role 
                : ($item->inventory_type === 'kitchen' ? 'kitchen' : 'barista');
            
            $item->waktu_wib = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            $item->status = 'diterima'; 
            return $item;
        });

        $combined = $kirimStok->concat($ambilBahan);
        
        $sorted = $combined->sort(function($a, $b) {
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
            
        return view('manager.gudang.kirim-stok.index', [
            'title' => 'Riwayat Kirim Stok',
            'records' => $records,
        ]);
    }

    public function kirimStokCreate(): View
    {
        return view('manager.gudang.kirim-stok.create', [
            'title' => 'Tambah Kirim Stok',
            'bahan_tree' => Bahan::groupedActiveTree(),
        ]);
    }

    public function kirimStokStore(Request $request): RedirectResponse
    {
        // Custom request logic because the form uses dynamic input names
        // based on active Bahan
        
        $request->validate([
            'tujuan' => 'required|in:coffee_shop,kitchen',
        ]);
        
        $tanggal = $request->input('tanggal');
        if (empty($tanggal)) {
            flash_danger('Tanggal harus diisi.');
            return back()->withInput();
        }

        DB::transaction(function () use ($request, $tanggal) {
            $kirimStok = GudangKirimStok::create([
                'tanggal' => $tanggal,
                'manager' => session('name') ?: session('username'),
                'barista_id' => session('user_id'),
                'status' => 'pending',
                'tujuan' => $request->input('tujuan'),
            ]);

            $activeItems = Bahan::activeItems($request->input('tujuan'));
            foreach ($activeItems as $bahan) {
                $jumlah = $request->input($bahan['kode']);
                if ($jumlah !== null && $jumlah !== '' && is_numeric($jumlah) && (float)$jumlah > 0) {
                    GudangKirimStokItem::create([
                        'gudang_kirim_stok_id' => $kirimStok->id,
                        'bahan_id' => $bahan['id'],
                        'jumlah' => (float)$jumlah,
                    ]);
                }
            }
        });

        flash_success('Berhasil membuat transaksi kirim stok.');
        return redirect()->route('gudang.kirim-stok.index');
    }

    public function kirimStokDetail(Request $request, int $id): View
    {
        $source = $request->query('source', 'gudang_kirim');
        
        if ($source === 'ambil_bahan_gudang') {
            $record = \App\Models\AmbilBahanGudang::with(['items.bahan', 'user'])->findOrFail($id);
            $record->source = 'ambil_bahan_gudang';
            $record->pelaku = $record->user ? $record->user->nama_lengkap : $record->barista;
            
            if (empty($record->inventory_type)) {
                if ($record->user && in_array($record->user->role, ['kitchen', 'headkitchen'])) {
                    $record->inventory_type = 'kitchen';
                } else {
                    $record->inventory_type = 'coffee_shop';
                }
            }
            
            $record->pelaku_role = $record->user 
                ? \Illuminate\Support\Str::title($record->user->role) 
                : ($record->inventory_type === 'kitchen' ? 'Kitchen' : 'Barista');
            $record->status = 'diterima';
        } else if ($source === 'gudang_kirim') {
            $record = \App\Models\GudangKirimStok::with('items.bahan')->findOrFail($id);
            $record->source = 'gudang_kirim';
            $record->pelaku = $record->manager;
        } else {
            abort(404, 'Invalid source type');
        }
        
        return view('manager.gudang.kirim-stok.detail', [
            'title' => 'Detail Kirim Stok',
            'record' => $record,
            'source' => $source,
        ]);
    }

    public function kirimStokEdit(int $id): View
    {
        $record = GudangKirimStok::with('items.bahan')->findOrFail($id);
        $defaultData = [
            'tanggal' => $record->tanggal,
            'tujuan' => $record->tujuan,
        ];
        foreach ($record->items as $item) {
            $defaultData[$item->bahan->kode] = $item->jumlah;
        }

        return view('manager.gudang.kirim-stok.edit', [
            'title' => 'Edit Kirim Stok',
            'id' => $id,
            'bahan_tree' => Bahan::groupedActiveTree(),
            'default_data' => $defaultData,
        ]);
    }

    public function kirimStokUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'tujuan' => 'required|in:coffee_shop,kitchen',
        ]);

        $tanggal = $request->input('tanggal');
        if (empty($tanggal)) {
            flash_danger('Tanggal harus diisi.');
            return back()->withInput();
        }

        DB::transaction(function () use ($request, $tanggal, $id) {
            $kirimStok = GudangKirimStok::findOrFail($id);
            $kirimStok->update([
                'tanggal' => $tanggal,
                'tujuan' => $request->input('tujuan'),
            ]);

            GudangKirimStokItem::where('gudang_kirim_stok_id', $kirimStok->id)->delete();

            $activeItems = Bahan::activeItems($request->input('tujuan'));
            foreach ($activeItems as $bahan) {
                $jumlah = $request->input($bahan['kode']);
                if ($jumlah !== null && $jumlah !== '' && is_numeric($jumlah) && (float)$jumlah > 0) {
                    GudangKirimStokItem::create([
                        'gudang_kirim_stok_id' => $kirimStok->id,
                        'bahan_id' => $bahan['id'],
                        'jumlah' => (float)$jumlah,
                    ]);
                }
            }
        });

        flash_success('Transaksi kirim stok berhasil diperbarui.');
        return redirect()->route('gudang.kirim-stok.index');
    }

    public function kirimStokDestroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $record = GudangKirimStok::findOrFail($id);
            $record->delete(); // Cascades items
        });

        flash_success('Transaksi kirim stok berhasil dihapus.');
        return redirect()->route('gudang.kirim-stok.index');
    }
    // =========================================================
    // PENGATURAN LIMIT
    // =========================================================
    public function pengaturanLimit(Request $request): View
    {
        $type = 'gudang'; // Hardcoded

        $limits = Bahan::forInventory($type)->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->select('id', 'kode', 'nama', 'satuan', 'kategori', 'urutan')
            ->get()
            ->map(function ($b) use ($type) {
                $lim = \App\Models\BahanLimit::where('bahan_id', $b->id)->where('inventory_type', $type)->first();
                return (object) [
                    'id' => $b->id,
                    'kode' => $b->kode,
                    'nama' => $b->nama,
                    'satuan' => $b->satuan,
                    'limit_habis' => $lim->limit_habis ?? StockAnalytics::DEFAULT_LIMIT_HABIS,
                    'limit_tipis' => $lim->limit_tipis ?? StockAnalytics::DEFAULT_LIMIT_TIPIS,
                ];
            });

        return view('manager.gudang.pengaturan-limit.index', [
            'title' => 'Pengaturan Limit Stok (Gudang)',
            'limits' => $limits,
            'inventory_type' => $type,
        ]);
    }

    public function pengaturanLimitEdit(Request $request, int $id): View
    {
        $type = 'gudang';
        $bahan = Bahan::findOrFail($id);
        $lim = \App\Models\BahanLimit::where('bahan_id', $bahan->id)->where('inventory_type', $type)->first();

        return view('manager.gudang.pengaturan-limit.edit', [
            'title' => 'Edit Limit Stok (Gudang)',
            'bahan' => $bahan,
            'limit_habis' => $lim->limit_habis ?? StockAnalytics::DEFAULT_LIMIT_HABIS,
            'limit_tipis' => $lim->limit_tipis ?? StockAnalytics::DEFAULT_LIMIT_TIPIS,
            'inventory_type' => $type,
        ]);
    }

    public function pengaturanLimitUpdate(Request $request, int $id): RedirectResponse
    {
        $bahan = Bahan::findOrFail($id);
        $type = 'gudang';

        $request->validate([
            'limit_habis' => 'required|numeric|min:0',
            'limit_tipis' => 'required|numeric|min:0',
        ]);

        \App\Models\BahanLimit::updateOrCreate(
            [
                'bahan_id' => $bahan->id,
                'inventory_type' => $type
            ],
            [
                'limit_habis' => $request->input('limit_habis'),
                'limit_tipis' => $request->input('limit_tipis'),
            ]
        );

        flash_success('Limit stok berhasil disimpan.');
        return redirect()->route('gudang.pengaturan-limit');
    }

    // =========================================================
    // EDIT AKUN SAYA (UPDATE PROFILE)
    // =========================================================

    public function updateProfile(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $userId = (int) session('user_id');
        $user = \App\Models\Barista::findOrFail($userId);

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
            $exists = \App\Models\Barista::where('username', $username)
                ->where('id', '<>', $userId)
                ->exists();
            if ($exists) {
                $errors['username'] = ['Username sudah digunakan oleh akun lain.'];
            }
        }

        // ---- Validasi Password (jika ingin ganti) ----
        $gantiPassword = $passwordBaru !== '';

        if ($gantiPassword) {
            if ($passwordLama === '') {
                $errors['password_lama'] = ['Password lama harus diisi jika ingin mengganti password.'];
            } else {
                if ($user->password) {
                    if (! \Illuminate\Support\Facades\Hash::check($passwordLama, $user->password)) {
                        $errors['password_lama'] = ['Password lama tidak sesuai.'];
                    }
                } else {
                    $expectedPassword = substr((string) $user->no_telp, -6);
                    if ($passwordLama !== $expectedPassword) {
                        $errors['password_lama'] = ['Password lama tidak sesuai. Gunakan 6 digit terakhir nomor telepon.'];
                    }
                }
            }

            if (strlen($passwordBaru) < 8) {
                $errors['password_baru'] = ['Password baru minimal 8 karakter.'];
            }

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
            $updateData['password'] = $passwordBaru; // Will be hashed automatically by Eloquent cast
        }

        $user->update($updateData);

        // ---- Update session ----
        session()->put('name', $nama);
        if ($username !== '') {
            session()->put('username', $username);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
            ]);
        }

        flash_success('Profil berhasil diperbarui.');
        return back();
    }
}
