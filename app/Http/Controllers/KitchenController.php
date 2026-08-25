<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\UpdateStok;
use App\Models\TokenListrik;
use App\Models\DailyClean;
use App\Models\DailyCleanPhoto;
use App\Models\AmbilBahanGudang;
use App\Models\AmbilBahanGudangItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function dashboard(): View
    {
        $baristaName = session('name') ?: session('username');

        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');

        $updateStokHariIni = UpdateStok::where('barista', $baristaName)
            ->where('inventory_type', 'kitchen')
            ->whereDate('tanggal', $today)
            ->count();
        $updateStokMingguIni = UpdateStok::where('barista', $baristaName)
            ->where('inventory_type', 'kitchen')
            ->whereDate('tanggal', '>=', $weekAgo)
            ->count();

        $dailyCleanHariIni = DailyClean::where('barista', $baristaName)
            ->whereDate('tanggal', $today)
            ->count();
        $dailyCleanMingguIni = DailyClean::where('barista', $baristaName)
            ->whereDate('tanggal', '>=', $weekAgo)
            ->count();

        $tokenHariIni = TokenListrik::where('barista', $baristaName)
            ->whereDate('tanggal', $today)
            ->count();
        $tokenMingguIni = TokenListrik::where('barista', $baristaName)
            ->whereDate('tanggal', '>=', $weekAgo)
            ->count();

        return view('kitchen.dashboard', [
            'title' => 'Dashboard Kitchen',
            'baristaName' => $baristaName,
            'update_stok_hari_ini' => $updateStokHariIni,
            'update_stok_minggu_ini' => $updateStokMingguIni,
            'daily_clean_hari_ini' => $dailyCleanHariIni,
            'daily_clean_minggu_ini' => $dailyCleanMingguIni,
            'token_hari_ini' => $tokenHariIni,
            'token_minggu_ini' => $tokenMingguIni,
        ]);
    }

    public function updateStok(): View
    {
        $bahanTree = Bahan::groupedActiveTree('kitchen');

        return view('kitchen.update-stok', [
            'title' => 'Input Update Stok Kitchen',
            'bahan_tree' => $bahanTree,
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
            'default_data' => session('form_data', []),
        ]);
    }

    public function updateStokStore(Request $request): RedirectResponse
    {
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

        $activeKeys = Bahan::activeKeys('kitchen');
        $data = [
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista' => $barista,
            'barista_id' => session('user_id'),
            'inventory_type' => 'kitchen',
            'created_at' => now(),
        ];

        foreach ($activeKeys as $kode) {
            $val = trim((string) $request->input($kode, ''));

            if ($val === '') {
                flash_danger('Semua data stok wajib diisi sebelum disimpan.');
                return back()->withInput();
            }
            if (! is_numeric($val)) {
                flash_danger("Nilai untuk {$kode} harus berupa angka.");
                return back()->withInput();
            }
            $data[$kode] = $val;
        }

        UpdateStok::create($data);

        flash_success('Data update stok kitchen berhasil disimpan.');

        return redirect()->route('kitchen.update-stok');
    }

    public function ambilBahan(): View
    {
        $bahanTree = Bahan::groupedActiveTree('kitchen');
        $gudangStocks = \App\Services\StockAnalytics::getGudangStockMap();

        return view('kitchen.ambil-bahan', [
            'title' => 'Input Ambil Bahan Kitchen',
            'bahan_tree' => $bahanTree,
            'gudang_stocks' => $gudangStocks,
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
        ]);
    }

    public function ambilBahanStore(Request $request): RedirectResponse
    {
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

        $activeKeys = Bahan::activeKeys('kitchen');
        $gudangStocks = \App\Services\StockAnalytics::getGudangStockMap();
        $kodeToId = [];
        foreach (Bahan::activeItems('kitchen') as $b) {
            $kodeToId[$b['kode']] = $b['id'];
        }

        $items = [];
        foreach ($activeKeys as $kode) {
            $val = trim((string) $request->input($kode, ''));
            if ($val !== '' && $val !== '0') {
                if (! is_numeric($val)) {
                    flash_danger("Nilai untuk {$kode} harus berupa angka positif.");
                    return back()->withInput();
                }
                
                $vFloat = (float) $val;
                if ($vFloat < 0) {
                    flash_danger("Nilai untuk {$kode} tidak boleh negatif.");
                    return back()->withInput();
                }

                $maxStock = $gudangStocks[$kode] ?? 0;
                if ($vFloat > $maxStock) {
                    flash_danger("Jumlah pengambilan {$kode} melebihi stok gudang yang tersedia.");
                    return back()->withInput();
                }

                if ($vFloat > 0) {
                    $items[] = [
                        'bahan_id' => $kodeToId[$kode],
                        'jumlah' => $vFloat
                    ];
                }
            }
        }

        if (empty($items)) {
            flash_danger('Minimal satu bahan harus diambil (jumlah > 0).');
            return back()->withInput();
        }

        try {
            DB::transaction(function () use ($tanggal, $shift, $barista, $items) {
                $header = AmbilBahanGudang::create([
                    'tanggal' => $tanggal,
                    'shift' => $shift,
                    'barista' => $barista,
                    'barista_id' => session('user_id'),
                    'inventory_type' => 'kitchen',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($items as $item) {
                    AmbilBahanGudangItem::create([
                        'ambil_bahan_gudang_id' => $header->id,
                        'bahan_id' => $item['bahan_id'],
                        'jumlah' => $item['jumlah'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            flash_success('Transaksi Ambil Bahan Kitchen berhasil disimpan.');
        } catch (\Exception $e) {
            flash_danger('Terjadi kesalahan saat menyimpan transaksi: ' . $e->getMessage());
            return back()->withInput();
        }

        return redirect()->route('kitchen.ambil-bahan');
    }

    public function dailyClean(): View
    {
        return view('kitchen.daily-clean', [
            'title' => 'Input Daily Clean Kitchen',
            'min_photos' => config('lotra.daily_clean_min_photos', 4),
            'shift_list' => shift_list(),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function dailyCleanStore(Request $request): RedirectResponse
    {
        $tanggal = (string) $request->input('tanggal', '');
        $shift = (string) $request->input('shift', '');
        $minPhotos = (int) config('lotra.daily_clean_min_photos', 4);

        $request->validate([
            'foto' => ['required', 'array', 'min:'.$minPhotos],
            'foto.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
        ]);

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

        $files = $request->file('foto', []);
        $files = is_array($files) ? $files : [$files];
        $validFiles = array_filter($files, fn ($f) => $f !== null);

        if (count($validFiles) < $minPhotos) {
            flash_danger("Minimal {$minPhotos} foto harus dikirim.");
            return back()->withInput();
        }

        $submission = DailyClean::create([
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista_id' => session('user_id'),
            'barista' => session('name') ?: session('username'),
            'inventory_type' => 'kitchen',
            'created_at' => now(),
        ]);

        foreach ($validFiles as $file) {
            $filename = $file->store('daily_clean', 'public');
            $submission->photos()->create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'created_at' => now(),
            ]);
        }

        flash_success('Daily clean kitchen berhasil dikirim.');

        return redirect()->route('kitchen.daily-clean');
    }

    public function tokenListrik(): View
    {
        return view('kitchen.token-listrik', [
            'title' => 'Input Jumlah Token Listrik Kitchen',
            'shift_list' => shift_list(),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function tokenListrikStore(Request $request): RedirectResponse
    {
        $tanggal = (string) $request->input('tanggal', '');
        $shift = (string) $request->input('shift', '');
        $tokenListrik = str_replace(',', '.', trim((string) $request->input('token_listrik', '')));

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
        if ($tokenListrik === '') {
            flash_danger("Token Listrik (kWh) harus diisi.");
            return back()->withInput();
        }

        TokenListrik::create([
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista_id' => session('user_id'),
            'barista' => session('name') ?: session('username'),
            'inventory_type' => 'kitchen',
            'token_r17' => $tokenListrik,
            'created_at' => now(),
        ]);

        flash_success('Token listrik kitchen berhasil disimpan.');

        return redirect()->route('kitchen.token-listrik');
    }
}
