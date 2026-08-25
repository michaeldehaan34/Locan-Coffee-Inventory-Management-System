<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\UpdateStok;
use App\Models\AmbilBahanGudang;
use App\Models\AmbilBahanGudangItem;
use App\Services\StockAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossInventoryStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_coffee_shop_transaction_does_not_affect_kitchen_stock()
    {
        $bahan = Bahan::where('kode', 'arabica')->first();

        // 1. Coffeeshop Update Stok
        $updateTime = now()->subMinutes(10);
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista User',
            'inventory_type' => 'coffee_shop',
            'arabica' => 10,
            'created_at' => $updateTime,
        ]);

        // 2. Coffeeshop Ambil Bahan
        $ambil = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista User',
            'inventory_type' => 'coffee_shop',
            'created_at' => now(),
        ]);

        AmbilBahanGudangItem::create([
            'ambil_bahan_gudang_id' => $ambil->id,
            'bahan_id' => $bahan->id,
            'jumlah' => 5,
        ]);

        // Calculate Stock
        $coffeeShopMap = StockAnalytics::getCoffeeShopStockMap();
        $kitchenMap = StockAnalytics::getKitchenStockMap();

        $this->assertEquals(15, $coffeeShopMap['arabica'], 'Coffeeshop stock should be 15');
        $this->assertArrayNotHasKey('arabica', $kitchenMap, 'Kitchen stock should not track Bar items');
    }

    public function test_kitchen_transaction_does_not_affect_coffee_shop_stock()
    {
        $bahan = Bahan::where('kode', 'salt')->first();

        // 1. Kitchen Update Stok
        $updateTime = now()->subMinutes(10);
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Kitchen User',
            'inventory_type' => 'kitchen',
            'salt' => 8,
            'created_at' => $updateTime,
        ]);

        // 2. Kitchen Ambil Bahan
        $ambil = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Kitchen User',
            'inventory_type' => 'kitchen',
            'created_at' => now(),
        ]);

        AmbilBahanGudangItem::create([
            'ambil_bahan_gudang_id' => $ambil->id,
            'bahan_id' => $bahan->id,
            'jumlah' => 4, // 4 taken from 8
        ]);

        // Calculate Stock
        $coffeeShopMap = StockAnalytics::getCoffeeShopStockMap();
        $kitchenMap = StockAnalytics::getKitchenStockMap();

        $this->assertEquals(12, $kitchenMap['salt'], 'Kitchen stock should be 12 (8 + 4)');
        $this->assertArrayNotHasKey('salt', $coffeeShopMap, 'Coffeeshop stock should not track Kitchen items');
    }
}
