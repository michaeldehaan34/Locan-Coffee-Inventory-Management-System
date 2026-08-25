<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Barista;
use App\Models\Manager;
use App\Models\GudangKirimStok;
use App\Models\AmbilBahanGudang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadbarAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Barista $headbar;
    private Barista $barista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headbar = Barista::create([
            'username' => 'headbar_user',
            'nama_lengkap' => 'Headbar User',
            'no_telp' => '08111111111',
            'role' => 'headbar',
        ]);

        $this->barista = Barista::create([
            'username' => 'barista_user',
            'nama_lengkap' => 'Barista User',
            'no_telp' => '08222222222',
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
                ]
            ]);
        }
    }

    private function loginAsHeadbar()
    {
        $this->withSession([
            'user_id' => $this->headbar->id,
            'username' => $this->headbar->username,
            'role' => 'headbar',
            'name' => $this->headbar->nama_lengkap,
        ]);
    }

    private function loginAsBarista()
    {
        $this->withSession([
            'user_id' => $this->barista->id,
            'username' => $this->barista->username,
            'role' => 'barista',
            'name' => $this->barista->nama_lengkap,
        ]);
    }

    public function test_headbar_has_access_to_dashboard_and_inventory_but_not_barista_features()
    {
        $this->loginAsHeadbar();

        // 1. Dashboard Headbar = 200
        $this->get('/headbar/dashboard')->assertStatus(200);

        // 2. Inventory Coffeeshop = 200
        $this->get('/headbar/coffee-shop/riwayat/terima-stok')->assertStatus(200);

        // 3. Create dummy data
        $gudang = GudangKirimStok::create([
            'tanggal' => now()->format('Y-m-d'),
            'admin_gudang' => 'Admin',
        ]);

        $ambil = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista User',
        ]);

        // 4. Detail Gudang Kirim Stok = 200
        $this->get("/headbar/coffee-shop/terima-stok/detail/{$gudang->id}?source=gudang_kirim")->assertStatus(200);

        // 5. Detail Ambil Bahan Gudang = 200
        $this->get("/headbar/coffee-shop/terima-stok/detail/{$ambil->id}?source=ambil_bahan_gudang")->assertStatus(200);

        // 6. Edit Gudang Kirim Stok = 200
        $this->get("/headbar/coffee-shop/terima-stok/edit/{$gudang->id}?source=gudang_kirim")->assertStatus(200);

        // 7. Edit Ambil Bahan Gudang = 403
        $this->get("/headbar/coffee-shop/terima-stok/edit/{$ambil->id}?source=ambil_bahan_gudang")->assertStatus(403);

        // 8. Update Ambil Bahan Gudang = 403
        $this->post("/headbar/coffee-shop/terima-stok/update/{$ambil->id}?source=ambil_bahan_gudang", [
            'tanggal' => now()->format('Y-m-d')
        ])->assertStatus(403);

        // 9. Delete Ambil Bahan Gudang = 403
        $this->post("/headbar/coffee-shop/terima-stok/hapus/{$ambil->id}?source=ambil_bahan_gudang")->assertStatus(403);

        // 10. Delete Gudang Kirim Stok = redirect (sesuai existing)
        $this->post("/headbar/coffee-shop/terima-stok/hapus/{$gudang->id}?source=gudang_kirim")
             ->assertRedirect(route('headbar.coffee-shop.terima-stok.index'));

        // 11. Barista Features = 403
        $this->get('/barista/dashboard')->assertStatus(403);
        $this->get('/barista/update-stok')->assertStatus(403);
        $this->get('/barista/ambil-bahan-gudang')->assertStatus(403);
        $this->get('/barista/daily-clean')->assertStatus(403);
        $this->get('/barista/token-listrik')->assertStatus(403);
    }

    public function test_barista_has_access_to_operational_features_but_not_headbar_features()
    {
        $this->loginAsBarista();

        // 1. Dashboard Barista = 200
        $this->get('/barista/dashboard')->assertStatus(200);

        // 2. Barista Features = 200
        $this->get('/barista/ambil-bahan-gudang')->assertStatus(200);
        $this->get('/barista/update-stok')->assertStatus(200);
        $this->get('/barista/daily-clean')->assertStatus(200);
        $this->get('/barista/token-listrik')->assertStatus(200);

        // 3. Headbar Features = 403
        $this->get('/headbar/dashboard')->assertStatus(403);
        $this->get('/headbar/coffee-shop/riwayat/terima-stok')->assertStatus(403);
    }
    /** @test */
    public function headbar_has_access_to_riwayat_monitoring()
    {
        $this->loginAsHeadbar();

        $response = $this->get(route('headbar.riwayat.update-stok'));
        $response->assertStatus(200);

        $response = $this->get(route('headbar.riwayat.daily-clean'));
        $response->assertStatus(200);

        $response = $this->get(route('headbar.riwayat.token-listrik'));
        $response->assertStatus(200);
    }
}



