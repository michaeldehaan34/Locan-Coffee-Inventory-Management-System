<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Barista;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use App\Models\AmbilBahanGudang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmbilBahanGudangTest extends TestCase
{
    use RefreshDatabase;

    private Barista $barista;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->barista = Barista::create([
            'username' => 'tester_ambil_bahan',
            'nama_lengkap' => 'Tester Ambil Bahan',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);

        if (Bahan::active()->count() === 0) {
            Bahan::insert([
                [
                    'kode' => 'uht_milk',
                    'nama' => 'Susu UHT',
                    'kategori' => 'Bahan Baku Bar',
                    'kelompok' => 'Susu',
                    'satuan' => 'liter',
                    'urutan' => 1,
                    'is_active' => 1,
                ],
                [
                    'kode' => 'arabica',
                    'nama' => 'Kopi Arabica',
                    'kategori' => 'Bahan Baku Bar',
                    'kelompok' => 'Kopi',
                    'satuan' => 'kg',
                    'urutan' => 2,
                    'is_active' => 1,
                ],
                [
                    'kode' => 'gula_pasir',
                    'nama' => 'Gula',
                    'kategori' => 'Bahan Baku Bar',
                    'kelompok' => 'Gula',
                    'satuan' => 'kg',
                    'urutan' => 3,
                    'is_active' => 1,
                ],
            ]);
        }
    }

    private function login(): void
    {
        $this->withSession([
            'user_id' => $this->barista->id,
            'username' => $this->barista->username,
            'role' => 'barista',
            'name' => $this->barista->nama_lengkap,
        ]);
    }

    private function setupStokGudang()
    {
        // Set stok gudang awal
        // Susu UHT = 20, Kopi = 15, Gula = 10
        StokMasuk::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Admin Gudang',
            'uht_milk' => 20,
            'arabica' => 15,
            'gula_pasir' => 10,
        ]);
    }

    private function setupUpdateStokBar()
    {
        // Set stok bar awal
        // Susu UHT = 5, Kopi = 5, Gula = 5
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'uht_milk' => 5,
            'arabica' => 5,
            'gula_pasir' => 5,
            'created_at' => now()->subMinutes(10), // memastikan UpdateStok lebih dulu
        ]);
    }

    public function test_scenario_1_pengambilan_berhasil()
    {
        $this->setupStokGudang();
        $this->setupUpdateStokBar();
        $this->login();
        $this->withoutMiddleware();

        $response = $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => 'Tester Ambil Bahan',
            'uht_milk' => '3',
        ]);

        $response->assertRedirect(route('barista.ambil-bahan-gudang'));
        
        $gudang = \App\Services\StockAnalytics::getGudangStockMap();
        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();

        $this->assertEquals(17, $gudang['uht_milk']); // 20 - 3 = 17
        $this->assertEquals(8, $bar['uht_milk']);    // 5 + 3 = 8
    }

    public function test_scenario_2_ambil_seluruh_stok()
    {
        $this->setupStokGudang(); // Gula = 10
        $this->setupUpdateStokBar(); // Gula = 5
        $this->login();
        $this->withoutMiddleware();

        $response = $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => 'Tester Ambil Bahan',
            'gula_pasir' => '10',
        ]);

        $response->assertRedirect();
        
        $gudang = \App\Services\StockAnalytics::getGudangStockMap();
        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();

        $this->assertEquals(0, $gudang['gula_pasir']); // 10 - 10 = 0
        $this->assertEquals(15, $bar['gula_pasir']);    // 5 + 10 = 15
    }

    public function test_scenario_3_jumlah_melebihi_stok()
    {
        $this->setupStokGudang(); // Kopi = 15
        $this->setupUpdateStokBar(); // Kopi = 5
        $this->login();
        $this->withoutMiddleware();

        $response = $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => 'Tester Ambil Bahan',
            'arabica' => '20', // Melebihi 15
        ]);

        $response->assertSessionHas('__flash'); // Error
        
        $gudang = \App\Services\StockAnalytics::getGudangStockMap();
        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();

        $this->assertEquals(15, $gudang['arabica']); // Tetap 15
        $this->assertEquals(5, $bar['arabica']);     // Tetap 5
        $this->assertDatabaseMissing('ambil_bahan_gudang_items', ['jumlah' => 20]);
    }

    public function test_scenario_4_quantity_0_diabaikan()
    {
        $this->setupStokGudang();
        $this->setupUpdateStokBar();
        $this->login();
        $this->withoutMiddleware();

        $response = $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => 'Tester Ambil Bahan',
            'uht_milk' => '0',
            'arabica' => '',
            'gula_pasir' => '0',
        ]);

        $response->assertSessionHas('__flash'); // Minimal satu bahan harus diambil
        $this->assertDatabaseCount('ambil_bahan_gudang', 0);
    }

    public function test_scenario_5_multiple_bahan()
    {
        $this->setupStokGudang();
        $this->setupUpdateStokBar();
        $this->login();
        $this->withoutMiddleware();

        $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => 'Tester Ambil Bahan',
            'uht_milk' => '3',
            'arabica' => '2',
            'gula_pasir' => '1',
        ]);

        $gudang = \App\Services\StockAnalytics::getGudangStockMap();
        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();

        $this->assertEquals(17, $gudang['uht_milk']);
        $this->assertEquals(8, $bar['uht_milk']);
        
        $this->assertEquals(13, $gudang['arabica']);
        $this->assertEquals(7, $bar['arabica']);
        
        $this->assertEquals(9, $gudang['gula_pasir']);
        $this->assertEquals(6, $bar['gula_pasir']);
    }

    public function test_scenario_a_chronological_ambil_sesudah_update()
    {
        $this->setupStokGudang(); // Susu = 20
        
        // 10:00 Update Stok = 20 (Susu Bar)
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'uht_milk' => 20,
            'created_at' => now()->subHours(2),
        ]);

        // 11:00 Ambil Gudang = 5
        $header = AmbilBahanGudang::forceCreate([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);
        $susuId = Bahan::where('kode', 'uht_milk')->first()->id;
        \App\Models\AmbilBahanGudangItem::forceCreate([
            'ambil_bahan_gudang_id' => $header->id,
            'bahan_id' => $susuId, 
            'jumlah' => 5, 
            'created_at' => now()->subHours(1), 
            'updated_at' => now()->subHours(1)
        ]);

        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();
        $this->assertEquals(25, $bar['uht_milk']);
    }

    public function test_scenario_b_chronological_ambil_sebelum_update()
    {
        $this->setupStokGudang(); // Susu = 20
        
        // 10:00 Ambil Gudang = 5
        $header = AmbilBahanGudang::forceCreate([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $susuId = Bahan::where('kode', 'uht_milk')->first()->id;
        \App\Models\AmbilBahanGudangItem::forceCreate([
            'ambil_bahan_gudang_id' => $header->id,
            'bahan_id' => $susuId, 
            'jumlah' => 5, 
            'created_at' => now()->subHours(2), 
            'updated_at' => now()->subHours(2)
        ]);

        // 11:00 Update Stok = 20
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'uht_milk' => 20,
            'created_at' => now()->subHours(1),
        ]);

        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();
        $this->assertEquals(20, $bar['uht_milk']); // Replace logic, ignore past Ambil
    }

    public function test_scenario_c_chronological_update_ambil_update_ambil()
    {
        $this->setupStokGudang();
        $susuId = Bahan::where('kode', 'uht_milk')->first()->id;

        // 10:00 Update Stok = 20
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'uht_milk' => 20,
            'created_at' => now()->subHours(4),
        ]);

        // 11:00 Ambil = 5
        $h1 = AmbilBahanGudang::forceCreate(['tanggal' => now()->format('Y-m-d'), 'shift' => 'Pagi', 'barista' => 'Barista', 'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3)]);
        \App\Models\AmbilBahanGudangItem::forceCreate(['ambil_bahan_gudang_id' => $h1->id, 'bahan_id' => $susuId, 'jumlah' => 5, 'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3)]);

        // 12:00 Update Stok = 30
        UpdateStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista',
            'uht_milk' => 30,
            'created_at' => now()->subHours(2),
        ]);

        // 13:00 Ambil = 2
        $h2 = AmbilBahanGudang::forceCreate(['tanggal' => now()->format('Y-m-d'), 'shift' => 'Pagi', 'barista' => 'Barista', 'created_at' => now()->subHours(1), 'updated_at' => now()->subHours(1)]);
        \App\Models\AmbilBahanGudangItem::forceCreate(['ambil_bahan_gudang_id' => $h2->id, 'bahan_id' => $susuId, 'jumlah' => 2, 'created_at' => now()->subHours(1), 'updated_at' => now()->subHours(1)]);

        $bar = \App\Services\StockAnalytics::getCoffeeShopStockMap();
        $this->assertEquals(32, $bar['uht_milk']); // Last update is 30, then took 2 = 32.
    }
}
