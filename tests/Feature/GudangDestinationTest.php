<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\GudangKirimStok;
use App\Models\GudangKirimStokItem;
use App\Models\StokMasuk;
use App\Services\StockAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GudangDestinationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_gudang_kirim_coffee_shop_isolation()
    {
        $bahan = Bahan::first();
        
        // Setup initial Gudang stock = 100
        StokMasuk::create([
            $bahan->kode => 100,
            'tanggal' => now(),
            'shift' => '-',
            'barista' => 'tester'
        ]);

        // Send 10 to Coffeeshop
        $kirim = GudangKirimStok::create([
            'tanggal' => now(),
            'manager' => 'tester',
            'status' => 'diterima',
            'tujuan' => 'coffee_shop'
        ]);
        GudangKirimStokItem::create([
            'gudang_kirim_stok_id' => $kirim->id,
            'bahan_id' => $bahan->id,
            'jumlah' => 10
        ]);

        $analytics = new StockAnalytics();
        
        $gudangStock = $analytics->getGudangStockMap();
        $this->assertEquals(90, $gudangStock[$bahan->kode]);

        $csStock = $analytics->getCoffeeShopStockMap();
        $this->assertEquals(10, $csStock[$bahan->kode]);

        $kitchenStock = $analytics->getKitchenStockMap();
        $this->assertArrayNotHasKey($bahan->kode, $kitchenStock);
    }

    public function test_gudang_kirim_kitchen_isolation()
    {
        $bahan = Bahan::where('kategori', 'Bahan Baku Kitchen')->first();
        
        StokMasuk::create([
            $bahan->kode => 100,
            'tanggal' => now(),
            'shift' => '-',
            'barista' => 'tester'
        ]);

        // Send 15 to Kitchen
        $kirim = GudangKirimStok::create([
            'tanggal' => now(),
            'manager' => 'tester',
            'status' => 'diterima',
            'tujuan' => 'kitchen'
        ]);
        GudangKirimStokItem::create([
            'gudang_kirim_stok_id' => $kirim->id,
            'bahan_id' => $bahan->id,
            'jumlah' => 15
        ]);

        $analytics = new StockAnalytics();
        
        $gudangStock = $analytics->getGudangStockMap();
        $this->assertEquals(85, $gudangStock[$bahan->kode]);

        $csStock = $analytics->getCoffeeShopStockMap();
        $this->assertArrayNotHasKey($bahan->kode, $csStock);

        $kitchenStock = $analytics->getKitchenStockMap();
        $this->assertEquals(15, $kitchenStock[$bahan->kode]);
    }

    public function test_gudang_kirim_both_destinations()
    {
        $bahan = Bahan::first();
        
        StokMasuk::create([
            $bahan->kode => 100,
            'tanggal' => now(),
            'shift' => '-',
            'barista' => 'tester'
        ]);

        $kirim1 = GudangKirimStok::create([
            'tanggal' => now(),
            'manager' => 'tester',
            'status' => 'diterima',
            'tujuan' => 'coffee_shop'
        ]);
        GudangKirimStokItem::create(['gudang_kirim_stok_id' => $kirim1->id, 'bahan_id' => $bahan->id, 'jumlah' => 10]);

        $kirim2 = GudangKirimStok::create([
            'tanggal' => now(),
            'manager' => 'tester',
            'status' => 'diterima',
            'tujuan' => 'kitchen'
        ]);
        GudangKirimStokItem::create(['gudang_kirim_stok_id' => $kirim2->id, 'bahan_id' => $bahan->id, 'jumlah' => 15]);

        $analytics = new StockAnalytics();
        
        $gudangStock = $analytics->getGudangStockMap();
        $this->assertEquals(75, $gudangStock[$bahan->kode]);

        $csStock = $analytics->getCoffeeShopStockMap();
        $this->assertEquals(10, $csStock[$bahan->kode]);

        $kitchenStock = $analytics->getKitchenStockMap();
        $this->assertArrayNotHasKey($bahan->kode, $kitchenStock); // Because $bahan is a Bar item
    }

    public function test_gudang_kirim_backward_compatibility()
    {
        $bahan = Bahan::first();
        
        StokMasuk::create([
            $bahan->kode => 100,
            'tanggal' => now(),
            'shift' => '-',
            'barista' => 'tester'
        ]);

        // Simulating legacy data where `tujuan` might not be explicitly set when created through DB facade
        // Migration sets default to 'coffee_shop'
        $kirimId = \Illuminate\Support\Facades\DB::table('gudang_kirim_stok')->insertGetId([
            'tanggal' => now(),
            'manager' => 'tester',
            'status' => 'diterima',
            // no tujuan
        ]);
        GudangKirimStokItem::create([
            'gudang_kirim_stok_id' => $kirimId,
            'bahan_id' => $bahan->id,
            'jumlah' => 10
        ]);

        $analytics = new StockAnalytics();
        
        $gudangStock = $analytics->getGudangStockMap();
        $this->assertEquals(90, $gudangStock[$bahan->kode]);

        $csStock = $analytics->getCoffeeShopStockMap();
        $this->assertEquals(10, $csStock[$bahan->kode]);

        $kitchenStock = $analytics->getKitchenStockMap();
        $this->assertArrayNotHasKey($bahan->kode, $kitchenStock);
    }
}
