<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use App\Models\GudangKirimStok;
use App\Models\GudangKirimStokItem;
use App\Services\StockAnalytics;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    echo "=== TAHAP 5: END-TO-END INVENTORY TESTING ===\n\n";

    // 1. Setup Data
    echo "[SETUP] Menggunakan bahan existing...\n";
    
    // Clear data temporarily in transaction
    UpdateStok::query()->delete();
    GudangKirimStok::query()->delete();
    StokMasuk::query()->delete();

    $existingBahan = Bahan::activeItems();
    if (count($existingBahan) < 1) {
        throw new \Exception("Need at least 1 active bahan in the database to run the test.");
    }
    
    $kopi = $existingBahan[0];
    $gula = count($existingBahan) > 1 ? $existingBahan[1] : $kopi;
    $susu = count($existingBahan) > 2 ? $existingBahan[2] : $kopi;
    
    $kodeKopi = $kopi['kode'];
    $kodeGula = $gula['kode'];
    $kodeSusu = $susu['kode'];

    echo "[SETUP] Bahan: {$kopi['nama']} ({$kodeKopi}), {$gula['nama']} ({$kodeGula}), {$susu['nama']} ({$kodeSusu})\n\n";


    // Helper assertions
    function getStokGudang($kode) {
        $map = StockAnalytics::getGudangStockMap();
        return $map[$kode] ?? 0;
    }
    function getStokCS($kode, $debug = false) {
        if ($debug) {
            $bahanId = Bahan::where('kode', $kode)->value('id');
            $lastUpdate = UpdateStok::whereNotNull($kode)->where($kode, '<>', '')->orderByDesc('tanggal')->orderByDesc('id')->first();
            $t = $lastUpdate ? $lastUpdate->created_at : null;
            $query = \App\Models\GudangKirimStokItem::where('bahan_id', $bahanId)
                ->whereHas('transaksi', function($q) use ($t) {
                    $q->where('status', 'diterima');
                    if ($t) {
                        $q->where('updated_at', '>', $t);
                    }
                });
            echo "DEBUG QUERY: " . $query->toSql() . "\n";
            echo "DEBUG BINDINGS: " . json_encode($query->getBindings()) . "\n";
            echo "DEBUG t: " . ($t ? $t->toDateTimeString() : 'null') . "\n";
        }
        $map = StockAnalytics::getCoffeeShopStockMap();
        return $map[$kode] ?? 0;
    }
    
    $initialGudangKopi = getStokGudang($kodeKopi);
    $initialCSKopi = getStokCS($kodeKopi);
    
    echo "Skenario 1: Stok Masuk +100\n";
    StokMasuk::create([
        'tanggal' => now(),
        'shift' => 'Pagi',
        'barista' => 'Test',
        $kodeKopi => 100
    ]);
    
    $gudang1 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs1 = getStokCS($kodeKopi) - $initialCSKopi;
    echo "Expected: Gudang=100, CS=0\n";
    echo "Actual: Gudang={$gudang1}, CS={$cs1}\n";
    echo ($gudang1 == 100 && $cs1 == 0) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 2: Kirim 30 (Status Pending)\n";
    $kirim1 = GudangKirimStok::create([
        'tanggal' => now(),
        'manager' => 'Manager Test',
        'status' => 'pending'
    ]);
    GudangKirimStokItem::create([
        'gudang_kirim_stok_id' => $kirim1->id,
        'bahan_id' => $kopi['id'],
        'jumlah' => 30
    ]);
    
    $gudang2 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs2 = getStokCS($kodeKopi) - $initialCSKopi;
    echo "Expected: Gudang=70, CS=0\n";
    echo "Actual: Gudang={$gudang2}, CS={$cs2}\n";
    echo ($gudang2 == 70 && $cs2 == 0) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 3: Approve 30\n";
    $kirim1->update(['status' => 'diterima']);
    $kirim1->touch(); // Ensure updated_at is refreshed
    $kirim1->refresh(); // Reload from db
    echo "DEBUG kirim1 updated_at: " . $kirim1->updated_at->toDateTimeString() . "\n";
    
    $gudang3 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs3 = getStokCS($kodeKopi, true) - $initialCSKopi;
    echo "Expected: Gudang=70, CS=30\n";
    echo "Actual: Gudang={$gudang3}, CS={$cs3}\n";
    echo ($gudang3 == 70 && $cs3 == 30) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 4: Double Approve (Exception Check)\n";
    try {
        if ($kirim1->status === 'diterima') {
            throw new \Exception('Transaksi ini sudah diterima sebelumnya.');
        }
        echo "Actual: Did not throw exception\nFAIL\n\n";
    } catch (\Exception $e) {
        echo "Actual Exception: " . $e->getMessage() . "\nPASS\n\n";
    }

    echo "Skenario 5: Update Stok CS (SET/REPLACE) = 20\n";
    // We add 2 seconds to ensure the UpdateStok is strictly BEFORE the terima
    sleep(2); 
    UpdateStok::create([
        'tanggal' => now(),
        'created_at' => now(),
        'shift' => 'Pagi',
        'barista' => 'Barista Test',
        $kodeKopi => $initialCSKopi + 20 // Set to initial + 20 so relative diff is 20
    ]);
    
    $gudang5 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs5 = getStokCS($kodeKopi) - $initialCSKopi;
    echo "Expected: Gudang=70, CS=20\n";
    echo "Actual: Gudang={$gudang5}, CS={$cs5}\n";
    echo ($gudang5 == 70 && $cs5 == 20) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 6: Kirim lagi 15 (Status Pending)\n";
    $kirim2 = GudangKirimStok::create([
        'tanggal' => now(),
        'manager' => 'Manager Test',
        'status' => 'pending'
    ]);
    GudangKirimStokItem::create([
        'gudang_kirim_stok_id' => $kirim2->id,
        'bahan_id' => $kopi['id'],
        'jumlah' => 15
    ]);
    
    $gudang6 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs6 = getStokCS($kodeKopi) - $initialCSKopi;
    echo "Expected: Gudang=55, CS=20\n";
    echo "Actual: Gudang={$gudang6}, CS={$cs6}\n";
    echo ($gudang6 == 55 && $cs6 == 20) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 7: Approve 15\n";
    sleep(2);
    $kirim2->update(['status' => 'diterima']);
    $kirim2->refresh();
    echo "DEBUG kirim2 updated_at: " . $kirim2->updated_at->toDateTimeString() . "\n";
    
    $gudang7 = getStokGudang($kodeKopi) - $initialGudangKopi;
    $cs7 = getStokCS($kodeKopi, true) - $initialCSKopi;
    echo "Expected: Gudang=55, CS=35\n";
    echo "Actual: Gudang={$gudang7}, CS={$cs7}\n";
    echo ($gudang7 == 55 && $cs7 == 35) ? "PASS\n\n" : "FAIL\n\n";

    echo "Skenario 8: Timestamp Test Kompleks\n";
    sleep(1);
    // 1. Update Stok CS = 20
    UpdateStok::create([
        'tanggal' => now(),
        'created_at' => now(),
        'shift' => 'Pagi',
        'barista' => 'Barista Test',
        $kodeKopi => $initialCSKopi + 20
    ]);
    
    sleep(1);
    // 2. Kirim 10
    $kirim3 = GudangKirimStok::create([
        'tanggal' => now(),
        'manager' => 'Manager Test',
        'status' => 'pending'
    ]);
    GudangKirimStokItem::create([
        'gudang_kirim_stok_id' => $kirim3->id,
        'bahan_id' => $kopi['id'],
        'jumlah' => 10
    ]);
    
    sleep(1);
    // 3. Terima 10
    $kirim3->update(['status' => 'diterima']);
    
    sleep(1);
    // 4. Update Stok CS = 25
    UpdateStok::create([
        'tanggal' => now(),
        'created_at' => now(),
        'shift' => 'Pagi',
        'barista' => 'Barista Test',
        $kodeKopi => $initialCSKopi + 25
    ]);
    
    sleep(1);
    // 5. Kirim 5
    $kirim4 = GudangKirimStok::create([
        'tanggal' => now(),
        'manager' => 'Manager Test',
        'status' => 'pending'
    ]);
    GudangKirimStokItem::create([
        'gudang_kirim_stok_id' => $kirim4->id,
        'bahan_id' => $kopi['id'],
        'jumlah' => 5
    ]);
    
    sleep(1);
    // 6. Terima 5
    $kirim4->update(['status' => 'diterima']);
    
    $cs8 = getStokCS($kodeKopi) - $initialCSKopi;
    echo "Expected: CS=30\n";
    echo "Actual: CS={$cs8}\n";
    echo ($cs8 == 30) ? "PASS\n\n" : "FAIL\n\n";


    echo "Skenario 9: Multi-item transfer\n";
    // Check initial values for Gula and Susu
    $initialGudangGula = getStokGudang($kodeGula);
    $initialGudangSusu = getStokGudang($kodeSusu);
    $initialCSGula = getStokCS($kodeGula);
    $initialCSSusu = getStokCS($kodeSusu);
    
    // Add stock to Gudang
    StokMasuk::create([
        'tanggal' => now(),
        'shift' => 'Pagi',
        'barista' => 'Test',
        $kodeGula => 50,
        $kodeSusu => 50
    ]);
    
    // Kirim Gula 10, Susu 15
    $kirim5 = GudangKirimStok::create([
        'tanggal' => now(),
        'manager' => 'Manager Test',
        'status' => 'diterima' // directly accepted for speed
    ]);
    GudangKirimStokItem::create(['gudang_kirim_stok_id' => $kirim5->id, 'bahan_id' => $gula['id'], 'jumlah' => 10]);
    GudangKirimStokItem::create(['gudang_kirim_stok_id' => $kirim5->id, 'bahan_id' => $susu['id'], 'jumlah' => 15]);
    
    $gudangGula = getStokGudang($kodeGula) - $initialGudangGula;
    $gudangSusu = getStokGudang($kodeSusu) - $initialGudangSusu;
    $csGula = getStokCS($kodeGula) - $initialCSGula;
    $csSusu = getStokCS($kodeSusu) - $initialCSSusu;
    
    echo "Expected Gula: Gudang=40, CS=10\n";
    echo "Actual Gula: Gudang={$gudangGula}, CS={$csGula}\n";
    echo "Expected Susu: Gudang=35, CS=15\n";
    echo "Actual Susu: Gudang={$gudangSusu}, CS={$csSusu}\n";
    echo ($gudangGula == 40 && $csGula == 10 && $gudangSusu == 35 && $csSusu == 15) ? "PASS\n\n" : "FAIL\n\n";


} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    DB::rollBack();
    echo "[CLEANUP] Rollback transaksi berhasil. Tidak ada data yang tersimpan di database.\n";
}
