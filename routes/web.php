<?php

use App\Http\Controllers\BaristaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\MasterBahanController;
use App\Http\Controllers\StokMasukController;
use Illuminate\Support\Facades\Route;

// Root entry point (preserves the login page design).
// - If the user is NOT logged in, redirect to the login page.
// - If the user IS logged in, redirect to the dashboard according to their role.
Route::get('/', function () {
    if (session()->has('username')) {
        $role = session('role');
        if ($role === 'manajemen' || $role === 'manager') {
            return redirect()->route('manager.dashboard');
        } elseif ($role === 'barista') {
            return redirect()->route('barista.dashboard');
        } elseif ($role === 'headbar') {
            return redirect()->route('headbar.dashboard');
        } elseif ($role === 'kitchen') {
            return redirect()->route('kitchen.dashboard');
        } elseif ($role === 'headkitchen') {
            return redirect()->route('headkitchen.dashboard');
        } elseif ($role === 'admin gudang') {
            return redirect()->route('gudang.dashboard');
        }
        
        return redirect()->route('dashboard.coming-soon');
    }

    return redirect()->route('login');
});

Route::get('/dashboard/coming-soon', function () {
    return view('coming-soon');
})->name('dashboard.coming-soon')->middleware(['session.auth', 'role:headbar,kitchen,headkitchen,admin gudang']);

/*
 * Barista features.
 * Accessible by Barista and Manager (the Manager role has full access).
 */
Route::middleware(['session.auth', 'role:barista'])->group(function () {
    Route::get('/barista/dashboard', [BaristaController::class, 'dashboard'])
        ->name('barista.dashboard');

    Route::get('/barista/ambil-bahan-gudang', [BaristaController::class, 'ambilBahanGudang'])
        ->name('barista.ambil-bahan-gudang');

    Route::post('/barista/ambil-bahan-gudang', [BaristaController::class, 'ambilBahanGudangStore'])
        ->name('barista.ambil-bahan-gudang.store');

    Route::get('/barista/update-stok', [BaristaController::class, 'updateStok'])
        ->name('barista.update-stok');

    Route::post('/barista/update-stok', [BaristaController::class, 'updateStokStore'])
        ->name('barista.update-stok.store');

    Route::get('/barista/daily-clean', [BaristaController::class, 'dailyClean'])
        ->name('barista.daily-clean');

    Route::get('/barista/token-listrik', [BaristaController::class, 'tokenListrik'])
        ->name('barista.token-listrik');

    // POST store endpoints (referenced by barista forms; methods exist in
    // BaristaController but were missing from the route definitions).
    Route::post('/barista/daily-clean/store', [BaristaController::class, 'dailyCleanStore'])
        ->name('barista.daily-clean.store');

    Route::post('/barista/token-listrik/store', [BaristaController::class, 'tokenListrikStore'])
        ->name('barista.token-listrik.store');
});

/*
 * Headbar features.
 * Headbar-only (Coffee Shop Inventory & Dashboard).
 */
Route::middleware(['session.auth', 'role:headbar'])->group(function () {
    Route::prefix('headbar/coffee-shop')->name('headbar.coffee-shop.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\HeadbarController::class, 'dashboardCS'])
            ->name('dashboard');

        // Terima Stok (Dari Gudang)
        Route::get('/riwayat/terima-stok', [\App\Http\Controllers\HeadbarController::class, 'terimaStokIndex'])
            ->name('terima-stok.index');
        Route::get('/terima-stok/detail/{id}', [\App\Http\Controllers\HeadbarController::class, 'terimaStokDetail'])
            ->name('terima-stok.detail');
        Route::get('/terima-stok/edit/{id}', [\App\Http\Controllers\HeadbarController::class, 'terimaStokEdit'])
            ->name('terima-stok.edit');
        Route::post('/terima-stok/update/{id}', [\App\Http\Controllers\HeadbarController::class, 'terimaStokUpdate'])
            ->name('terima-stok.update');
        Route::post('/terima-stok/hapus/{id}', [\App\Http\Controllers\HeadbarController::class, 'terimaStokDestroy'])
            ->name('terima-stok.delete');
    });

    Route::prefix('headbar')->name('headbar.')->group(function () {
        // Monitoring Barista
        Route::get('/riwayat/update-stok', [\App\Http\Controllers\HeadbarController::class, 'riwayatUpdateStok'])
            ->name('riwayat.update-stok');
        Route::get('/riwayat/update-stok/detail/{id}', [\App\Http\Controllers\HeadbarController::class, 'updateStokDetail'])
            ->name('update-stok.detail');
        Route::get('/update-stok/edit/{id}', [\App\Http\Controllers\HeadbarController::class, 'updateStokEdit'])
            ->name('update-stok.edit');
        Route::post('/update-stok/update/{id}', [\App\Http\Controllers\HeadbarController::class, 'updateStokUpdate'])
            ->name('update-stok.update');
        Route::post('/update-stok/hapus/{id}', [\App\Http\Controllers\HeadbarController::class, 'updateStokDestroy'])
            ->name('update-stok.delete');

        Route::get('/riwayat/daily-clean', [\App\Http\Controllers\HeadbarController::class, 'riwayatDailyClean'])
            ->name('riwayat.daily-clean');
        Route::get('/riwayat/daily-clean/detail/{id}', [\App\Http\Controllers\HeadbarController::class, 'dailyCleanDetailPage'])
            ->name('riwayat.daily-clean.detail');
        Route::get('/riwayat/daily-clean/detail-json/{id}', [\App\Http\Controllers\HeadbarController::class, 'dailyCleanDetail'])
            ->name('riwayat.daily-clean.detail-json');
        Route::post('/riwayat/daily-clean/hapus/{id}', [\App\Http\Controllers\HeadbarController::class, 'dailyCleanDestroy'])
            ->name('riwayat.daily-clean.delete');
        Route::post('/riwayat/daily-clean/hapus-massal', [\App\Http\Controllers\HeadbarController::class, 'dailyCleanBulkDelete'])
            ->name('riwayat.daily-clean.bulk-delete');

        Route::get('/riwayat/token-listrik', [\App\Http\Controllers\HeadbarController::class, 'riwayatTokenListrik'])
            ->name('riwayat.token-listrik');
        Route::delete('/riwayat/token-listrik/hapus/{id}', [\App\Http\Controllers\HeadbarController::class, 'tokenListrikDestroy'])
            ->name('riwayat.token-listrik.delete');
        Route::post('/riwayat/token-listrik/hapus-massal', [\App\Http\Controllers\HeadbarController::class, 'tokenListrikBulkDelete'])
            ->name('riwayat.token-listrik.bulk-delete');

        // This acts as a default headbar route
        Route::get('/dashboard', [\App\Http\Controllers\HeadbarController::class, 'dashboardCS'])
            ->name('dashboard');
    });
});

/*
 * Kitchen features.
 */
Route::middleware(['session.auth', 'role:kitchen'])->group(function () {
    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\KitchenController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/ambil-bahan', [\App\Http\Controllers\KitchenController::class, 'ambilBahan'])
            ->name('ambil-bahan');
        Route::post('/ambil-bahan/store', [\App\Http\Controllers\KitchenController::class, 'ambilBahanStore'])
            ->name('ambil-bahan.store');

        Route::get('/update-stok', [\App\Http\Controllers\KitchenController::class, 'updateStok'])
            ->name('update-stok');
        Route::post('/update-stok/store', [\App\Http\Controllers\KitchenController::class, 'updateStokStore'])
            ->name('update-stok.store');

        Route::get('/daily-clean', [\App\Http\Controllers\KitchenController::class, 'dailyClean'])
            ->name('daily-clean');
        Route::post('/daily-clean/store', [\App\Http\Controllers\KitchenController::class, 'dailyCleanStore'])
            ->name('daily-clean.store');

        Route::get('/token-listrik', [\App\Http\Controllers\KitchenController::class, 'tokenListrik'])
            ->name('token-listrik');
        Route::post('/token-listrik/store', [\App\Http\Controllers\KitchenController::class, 'tokenListrikStore'])
            ->name('token-listrik.store');
    });
});

/*
 * Head Kitchen features.
 */
Route::middleware(['session.auth', 'role:headkitchen'])->group(function () {
    Route::prefix('headkitchen')->name('headkitchen.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\HeadKitchenController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/kitchen/riwayat/terima-stok', [\App\Http\Controllers\HeadKitchenController::class, 'terimaStokIndex'])
            ->name('terima-stok.index');
        Route::get('/kitchen/terima-stok/detail/{id}', [\App\Http\Controllers\HeadKitchenController::class, 'terimaStokDetail'])
            ->name('terima-stok.detail');
    });
});

/*
 * Manager features.
 * Manager-only (full access).
 */
Route::middleware(['session.auth', 'role:manajemen'])->group(function () {
    // ==========================================
    // DOMAIN COFFEE SHOP (Diakses Manager) - OPERASIONAL (DIMATIKAN)
    // ==========================================
    /*
    Route::prefix('manager/coffee-shop')->name('manager.coffee-shop.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ManagerController::class, 'dashboardCS'])
            ->name('dashboard');

        // Terima Stok (Dari Gudang)
        Route::get('/riwayat/terima-stok', [\App\Http\Controllers\ManagerController::class, 'terimaStokIndex'])
            ->name('terima-stok.index');
        Route::get('/terima-stok/detail/{id}', [\App\Http\Controllers\ManagerController::class, 'terimaStokDetail'])
            ->name('terima-stok.detail');
        Route::get('/terima-stok/edit/{id}', [\App\Http\Controllers\ManagerController::class, 'terimaStokEdit'])
            ->name('terima-stok.edit');
        Route::post('/terima-stok/update/{id}', [\App\Http\Controllers\ManagerController::class, 'terimaStokUpdate'])
            ->name('terima-stok.update');
        Route::post('/terima-stok/hapus/{id}', [\App\Http\Controllers\ManagerController::class, 'terimaStokDestroy'])
            ->name('terima-stok.delete');
    });
    */

    // Dashboard Manajemen (Multi-Inventory Monitoring)
    Route::get('/manager/dashboard', [\App\Http\Controllers\ManagerController::class, 'dashboard'])
        ->name('manager.dashboard');

    // ==========================================
    // DOMAIN GUDANG - OPERASIONAL (DIMATIKAN)
    // ==========================================
    /*
    Route::prefix('manager/gudang')->name('manager.gudang.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GudangController::class, 'dashboard'])
            ->name('dashboard');

        // Stok Masuk Gudang
        Route::get('/riwayat/stok-masuk', [\App\Http\Controllers\GudangController::class, 'stokMasukIndex'])
            ->name('stok-masuk.index');
        Route::get('/stok-masuk/create', [\App\Http\Controllers\GudangController::class, 'stokMasukCreate'])
            ->name('stok-masuk.create');
        Route::post('/stok-masuk/store', [\App\Http\Controllers\GudangController::class, 'stokMasukStore'])
            ->name('stok-masuk.store');
        Route::get('/stok-masuk/detail/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukDetail'])
            ->name('stok-masuk.detail');
        Route::get('/stok-masuk/edit/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukEdit'])
            ->name('stok-masuk.edit');
        Route::post('/stok-masuk/update/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukUpdate'])
            ->name('stok-masuk.update');
        Route::post('/stok-masuk/hapus/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukDestroy'])
            ->name('stok-masuk.delete');

        // Kirim Stok (Gudang -> CS)
        Route::get('/riwayat/kirim-stok', [\App\Http\Controllers\GudangController::class, 'kirimStokIndex'])
            ->name('kirim-stok.index');
        Route::get('/kirim-stok/create', [\App\Http\Controllers\GudangController::class, 'kirimStokCreate'])
            ->name('kirim-stok.create');
        Route::post('/kirim-stok/store', [\App\Http\Controllers\GudangController::class, 'kirimStokStore'])
            ->name('kirim-stok.store');
        Route::get('/kirim-stok/detail/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokDetail'])
            ->name('kirim-stok.detail');
        Route::get('/kirim-stok/edit/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokEdit'])
            ->name('kirim-stok.edit');
        Route::post('/kirim-stok/update/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokUpdate'])
            ->name('kirim-stok.update');
        Route::post('/kirim-stok/hapus/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokDestroy'])
            ->name('kirim-stok.delete');
    });

    // Update Stok uses the same controller flow and validation as Barista.
    Route::get('/manager/update-stok', [\App\Http\Controllers\BaristaController::class, 'updateStok'])
        ->name('manager.update-stok');
    Route::post('/manager/update-stok', [\App\Http\Controllers\BaristaController::class, 'updateStokStore'])
        ->name('manager.update-stok.store');

    Route::get('/manager/riwayat/update-stok', [ManagerController::class, 'riwayatUpdateStok'])
        ->name('manager.riwayat.update-stok');
    Route::get('/manager/update-stok/detail/{id}', [ManagerController::class, 'updateStokDetail'])
        ->name('manager.update-stok.detail');
    Route::get('/manager/update-stok/edit/{id}', [ManagerController::class, 'updateStokEdit'])
        ->name('manager.update-stok.edit');
    Route::put('/manager/update-stok/update/{id}', [ManagerController::class, 'updateStokUpdate'])
        ->name('manager.update-stok.update');
    Route::delete('/manager/update-stok/hapus/{id}', [ManagerController::class, 'updateStokDestroy'])
        ->name('manager.update-stok.delete');

    Route::get('/manager/riwayat/daily-clean', [ManagerController::class, 'riwayatDailyClean'])
        ->name('manager.riwayat.daily-clean');

    Route::get('/manager/riwayat/token-listrik', [ManagerController::class, 'riwayatTokenListrik'])
        ->name('manager.riwayat.token-listrik');
    */

    Route::get('/manager/data-barista', [ManagerController::class, 'dataBarista'])
        ->name('manager.data-barista');

    /*
    Route::get('/manager/master-bahan', [MasterBahanController::class, 'index'])
        ->name('manager.master-bahan');

    Route::get('/manager/master-bahan/create', [MasterBahanController::class, 'create'])
        ->name('manager.master-bahan.create');

    Route::get('/manager/master-bahan/detail/{id}', [MasterBahanController::class, 'detail'])
        ->name('manager.master-bahan.detail');

    Route::get('/manager/master-bahan/edit/{id}', [MasterBahanController::class, 'edit'])
        ->name('manager.master-bahan.edit');

    Route::get('/manager/pengaturan-limit', [ManagerController::class, 'pengaturanLimit'])
        ->name('manager.pengaturan-limit');

    Route::get('/manager/laporan', [ManagerController::class, 'laporan'])
        ->name('manager.laporan');

    Route::get('/manager/forecast', [ManagerController::class, 'forecast'])
        ->name('manager.forecast');
    */

    // Data Barista (CRUD)
    Route::get('/manager/data-barista/create', [ManagerController::class, 'baristaCreate'])
        ->name('manager.data-barista.create');
    Route::post('/manager/data-barista/store', [ManagerController::class, 'baristaStore'])
        ->name('manager.data-barista.store');
    Route::get('/manager/data-barista/detail/{id}', [ManagerController::class, 'baristaDetail'])
        ->name('manager.data-barista.detail');
    Route::get('/manager/data-barista/edit/{id}', [ManagerController::class, 'baristaEditForm'])
        ->name('manager.data-barista.edit.form');
    Route::post('/manager/data-barista/tambah', [ManagerController::class, 'baristaAdd'])
        ->name('manager.data-barista.add');
    Route::post('/manager/data-barista/edit/{id}', [ManagerController::class, 'baristaEdit'])
        ->name('manager.data-barista.edit');
    Route::post('/manager/data-barista/hapus/{id}', [ManagerController::class, 'baristaDelete'])
        ->name('manager.data-barista.delete');

    /*
    // Master Bahan (CRUD + toggle + kelompok) - Tahap 6
    Route::get('/manager/master-bahan/kelompok', [MasterBahanController::class, 'kelompok'])
        ->name('manager.master-bahan.kelompok');
    Route::post('/manager/master-bahan/tambah', [MasterBahanController::class, 'store'])
        ->name('manager.master-bahan.add');
    Route::post('/manager/master-bahan/edit/{id}', [MasterBahanController::class, 'update'])
        ->name('manager.master-bahan.update');
    Route::post('/manager/master-bahan/hapus/{id}', [MasterBahanController::class, 'destroy'])
        ->name('manager.master-bahan.delete');
    Route::post('/manager/master-bahan/status/{id}', [MasterBahanController::class, 'toggle'])
        ->name('manager.master-bahan.toggle');

    // Pengaturan Limit
    Route::post('/manager/pengaturan-limit/simpan', [ManagerController::class, 'pengaturanLimitSimpan'])
        ->name('manager.pengaturan-limit.simpan');
    Route::get('/manager/pengaturan-limit/edit/{id}', [ManagerController::class, 'pengaturanLimitEdit'])
        ->name('manager.pengaturan-limit.edit');
    Route::post('/manager/pengaturan-limit/edit/{id}', [ManagerController::class, 'pengaturanLimitUpdate'])
        ->name('manager.pengaturan-limit.update');

    // Export endpoints
    Route::get('/manager/export/stok-masuk', [ManagerController::class, 'exportStokMasuk'])
        ->name('manager.export.stok-masuk');
    Route::get('/manager/export/update-stok', [ManagerController::class, 'exportUpdateStok'])
        ->name('manager.export.update-stok');
    Route::get('/manager/export/update-stok-pdf', [ManagerController::class, 'exportUpdateStokPdf'])
        ->name('manager.export.update-stok-pdf');
    Route::get('/manager/export/daily-clean', [ManagerController::class, 'exportDailyClean'])
        ->name('manager.export.daily-clean');
    Route::get('/manager/export/token-listrik', [ManagerController::class, 'exportTokenListrik'])
        ->name('manager.export.token-listrik');
    Route::get('/manager/laporan/export', [ManagerController::class, 'laporanExport'])
        ->name('manager.laporan.export');
    Route::get('/manager/forecast/export-excel', [ManagerController::class, 'forecastExportExcel'])
        ->name('manager.forecast.export-excel');
    Route::get('/manager/forecast/export-pdf', [ManagerController::class, 'forecastExportPdf'])
        ->name('manager.forecast.export-pdf');

    // Daily Clean detail (JSON)
    Route::get('/manager/riwayat/daily-clean/detail/{id}', [ManagerController::class, 'dailyCleanDetail'])
        ->name('manager.riwayat.daily-clean.detail');

    // Token Listrik delete (single)
    Route::delete('/manager/riwayat/token-listrik/hapus/{id}', [ManagerController::class, 'tokenListrikDestroy'])
        ->name('manager.token-listrik.delete');

    // Token Listrik bulk delete
    Route::post('/manager/riwayat/token-listrik/hapus-massal', [ManagerController::class, 'tokenListrikBulkDelete'])
        ->name('manager.token-listrik.bulk-delete');

// Token Listrik detail page (full page, bukan modal)
    Route::get('/manager/riwayat/token-listrik/detail/{id}', [ManagerController::class, 'tokenListrikDetail'])
        ->name('manager.token-listrik.detail');

    // Daily Clean detail page (full page, bukan modal)
    Route::get('/manager/riwayat/daily-clean/view/{id}', [ManagerController::class, 'dailyCleanDetailPage'])
        ->name('manager.daily-clean.detail');

    // Daily Clean delete (single)
    Route::delete('/manager/riwayat/daily-clean/hapus/{id}', [ManagerController::class, 'dailyCleanDestroy'])
        ->name('manager.riwayat.daily-clean.delete');

    // Daily Clean bulk delete
    Route::post('/manager/riwayat/daily-clean/hapus-massal', [ManagerController::class, 'dailyCleanBulkDelete'])
        ->name('manager.riwayat.daily-clean.bulk-delete');
    */

    // Edit Akun Saya (Update Profile)
    Route::post('/manager/profile/update', [ManagerController::class, 'updateProfile'])
        ->name('manager.profile.update');
});

/*
 * Admin Gudang features.
 */
Route::middleware(['session.auth', 'role:admin gudang'])->group(function () {
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\GudangController::class, 'dashboard'])
            ->name('dashboard');

        // Stok Masuk Gudang
        Route::get('/riwayat/stok-masuk', [\App\Http\Controllers\GudangController::class, 'stokMasukIndex'])
            ->name('stok-masuk.index');
        Route::get('/stok-masuk/create', [\App\Http\Controllers\GudangController::class, 'stokMasukCreate'])
            ->name('stok-masuk.create');
        Route::post('/stok-masuk/store', [\App\Http\Controllers\GudangController::class, 'stokMasukStore'])
            ->name('stok-masuk.store');
        Route::get('/stok-masuk/detail/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukDetail'])
            ->name('stok-masuk.detail');
        Route::get('/stok-masuk/edit/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukEdit'])
            ->name('stok-masuk.edit');
        Route::post('/stok-masuk/update/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukUpdate'])
            ->name('stok-masuk.update');
        Route::post('/stok-masuk/hapus/{id}', [\App\Http\Controllers\GudangController::class, 'stokMasukDestroy'])
            ->name('stok-masuk.delete');

        // Kirim Stok (Gudang -> CS / Kitchen)
        Route::get('/riwayat/kirim-stok', [\App\Http\Controllers\GudangController::class, 'kirimStokIndex'])
            ->name('kirim-stok.index');
        Route::get('/kirim-stok/create', [\App\Http\Controllers\GudangController::class, 'kirimStokCreate'])
            ->name('kirim-stok.create');
        Route::post('/kirim-stok/store', [\App\Http\Controllers\GudangController::class, 'kirimStokStore'])
            ->name('kirim-stok.store');
        Route::get('/kirim-stok/detail/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokDetail'])
            ->name('kirim-stok.detail');
        Route::get('/kirim-stok/edit/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokEdit'])
            ->name('kirim-stok.edit');
        Route::post('/kirim-stok/update/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokUpdate'])
            ->name('kirim-stok.update');
        Route::post('/kirim-stok/hapus/{id}', [\App\Http\Controllers\GudangController::class, 'kirimStokDestroy'])
            ->name('kirim-stok.delete');

        // Master Bahan (CRUD + toggle + kelompok)
        Route::get('/master-bahan', [\App\Http\Controllers\MasterBahanController::class, 'index'])
            ->name('master-bahan');
        Route::get('/master-bahan/create', [\App\Http\Controllers\MasterBahanController::class, 'create'])
            ->name('master-bahan.create');
        Route::get('/master-bahan/detail/{id}', [\App\Http\Controllers\MasterBahanController::class, 'detail'])
            ->name('master-bahan.detail');
        Route::get('/master-bahan/edit/{id}', [\App\Http\Controllers\MasterBahanController::class, 'edit'])
            ->name('master-bahan.edit');
        Route::get('/master-bahan/kelompok', [\App\Http\Controllers\MasterBahanController::class, 'kelompok'])
            ->name('master-bahan.kelompok');
        Route::post('/master-bahan/tambah', [\App\Http\Controllers\MasterBahanController::class, 'store'])
            ->name('master-bahan.add');
        Route::post('/master-bahan/edit/{id}', [\App\Http\Controllers\MasterBahanController::class, 'update'])
            ->name('master-bahan.update');
        Route::post('/master-bahan/hapus/{id}', [\App\Http\Controllers\MasterBahanController::class, 'destroy'])
            ->name('master-bahan.delete');
        Route::post('/master-bahan/status/{id}', [\App\Http\Controllers\MasterBahanController::class, 'toggle'])
            ->name('master-bahan.toggle');
    });
});
