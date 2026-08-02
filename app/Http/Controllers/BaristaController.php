<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use App\Models\TokenListrik;
use App\Models\DailyClean;
use App\Models\DailyCleanPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Barista features (also accessible by Manager, who has full access).
 *
 * 1. Input stok masuk
 * 2. Input update stok
 * 3. Input daily clean (with photo upload)
 * 4. Input jumlah token listrik
 */
class BaristaController extends Controller
{
    /**
     * Halaman Dashboard Barista (landing page setelah login).
     *
     * Menampilkan sapaan dan ringkasan singkat aktivitas barista yang
     * sedang login, serta kartu aksi cepat menuju fitur Barista.
     * Desain tidak diubah — hanya menambahkan halaman tujuan login barista.
     */
    public function dashboard(): View
    {
        $baristaName = session('name') ?: session('username');

        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');

        $updateStokHariIni = UpdateStok::where('barista', $baristaName)
            ->whereDate('tanggal', $today)
            ->count();
        $updateStokMingguIni = UpdateStok::where('barista', $baristaName)
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

        return view('barista.dashboard', [
            'title' => 'Dashboard Barista',
            'baristaName' => $baristaName,
            'update_stok_hari_ini' => $updateStokHariIni,
            'update_stok_minggu_ini' => $updateStokMingguIni,
            'daily_clean_hari_ini' => $dailyCleanHariIni,
            'daily_clean_minggu_ini' => $dailyCleanMingguIni,
            'token_hari_ini' => $tokenHariIni,
            'token_minggu_ini' => $tokenMingguIni,
        ]);
    }

    /**
     * Halaman Input Stok Masuk.
     */
    public function stokMasuk(): View
    {
        $bahanTree = Bahan::groupedActiveTree();

        return view('barista.stok-masuk', [
            'title' => 'Input Stok Masuk',
            'bahan_tree' => $bahanTree,
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
            'default_data' => session('form_data', []),
        ]);
    }

    /**
     * Proses simpan Stok Masuk (validasi + Eloquent insert).
     */
    public function stokMasukStore(Request $request): RedirectResponse
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

        $activeKeys = Bahan::activeKeys();
        $data = [
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista' => $barista,
        ];

        $hasValue = false;
        foreach ($activeKeys as $kode) {
            $val = trim((string) $request->input($kode, ''));
            if ($val !== '') {
                if (! is_numeric($val)) {
                    flash_danger("Nilai untuk {$kode} harus berupa angka.");

                    return back()->withInput();
                }
                $hasValue = true;
            }
            $data[$kode] = $val === '' ? null : $val;
        }

        if (! $hasValue) {
            flash_danger('Minimal satu item harus diisi.');

            return back()->withInput();
        }

        StokMasuk::create($data);

        flash_success('Data stok masuk berhasil disimpan.');

        return redirect()->route('barista.stok-masuk');
    }

    /**
     * Halaman Input Update Stok.
     */
    public function updateStok(): View
    {
        $bahanTree = Bahan::groupedActiveTree();

        return view('barista.update-stok', [
            'title' => 'Input Update Stok',
            'bahan_tree' => $bahanTree,
            'shift_list' => shift_list(),
            'barista_name' => session('name') ?: session('username'),
            'default_data' => session('form_data', []),
        ]);
    }

    /**
     * Proses simpan Update Stok (SEMUA item wajib diisi).
     */
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

        $activeKeys = Bahan::activeKeys();
        $data = [
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista' => $barista,
        ];

        $formData = [
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista' => $barista,
        ];

        foreach ($activeKeys as $kode) {
            $val = trim((string) $request->input($kode, ''));
            $formData[$kode] = $val;

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

        flash_success('Data update stok berhasil disimpan.');

        return redirect()->route('barista.update-stok');
    }

    /**
     * Halaman Input Daily Clean.
     */
    public function dailyClean(): View
    {
        return view('barista.daily-clean', [
            'title' => 'Input Daily Clean',
            'min_photos' => config('lotra.daily_clean_min_photos', 4),
            'shift_list' => shift_list(),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Proses simpan Daily Clean (upload foto + metadata).
     */
    public function dailyCleanStore(Request $request): RedirectResponse
    {
        $tanggal = (string) $request->input('tanggal', '');
        $shift = (string) $request->input('shift', '');
        $minPhotos = (int) config('lotra.daily_clean_min_photos', 4);

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

        flash_success('Daily clean berhasil dikirim.');

        return redirect()->route('barista.daily-clean');
    }

    /**
     * Halaman Input Token Listrik.
     */
    public function tokenListrik(): View
    {
        return view('barista.token-listrik', [
            'title' => 'Input Jumlah Token Listrik',
            'shift_list' => shift_list(),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Proses simpan Token Listrik.
     */
    public function tokenListrikStore(Request $request): RedirectResponse
    {
        $tanggal = (string) $request->input('tanggal', '');
        $shift = (string) $request->input('shift', '');
        $tokenR17 = str_replace(',', '.', trim((string) $request->input('token_r17', '')));
        $tokenR18 = str_replace(',', '.', trim((string) $request->input('token_r18', '')));
        $tokenMesin = str_replace(',', '.', trim((string) $request->input('token_mesin', '')));

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
        foreach (['token_r17' => 'R17', 'token_r18' => 'R18', 'token_mesin' => 'Mesin'] as $field => $label) {
            if (trim((string) $request->input($field, '')) === '') {
                flash_danger("Token Listrik {$label} (kWh) harus diisi.");

                return back()->withInput();
            }
        }

        TokenListrik::create([
            'tanggal' => $tanggal,
            'shift' => $shift,
            'barista_id' => session('user_id'),
            'barista' => session('name') ?: session('username'),
            'token_r17' => $tokenR17,
            'token_r18' => $tokenR18,
            'token_mesin' => $tokenMesin,
            'created_at' => now(),
        ]);

        flash_success('Token listrik berhasil disimpan.');

        return redirect()->route('barista.token-listrik');
    }
}