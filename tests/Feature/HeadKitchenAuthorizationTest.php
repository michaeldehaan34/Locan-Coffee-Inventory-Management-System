<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Barista;
use App\Models\AmbilBahanGudang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadKitchenAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Barista $headkitchen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headkitchen = Barista::create([
            'username' => 'headkitchen_user',
            'nama_lengkap' => 'Head Kitchen User',
            'no_telp' => '08111111111',
            'role' => 'headkitchen',
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

    private function loginAsHeadKitchen()
    {
        $this->withSession([
            'user_id' => $this->headkitchen->id,
            'username' => $this->headkitchen->username,
            'role' => 'headkitchen',
            'name' => $this->headkitchen->nama_lengkap,
        ]);
    }

    public function test_headkitchen_has_access_to_dashboard_and_inventory_but_not_kitchen_features()
    {
        $this->loginAsHeadKitchen();

        // 1. Dashboard Head Kitchen = 200
        $this->get('/headkitchen/dashboard')->assertStatus(200);

        // 2. Inventory Kitchen = 200
        $this->get('/headkitchen/kitchen/riwayat/terima-stok')->assertStatus(200);

        // 3. Create dummy data
        $ambil = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Kitchen User',
            'inventory_type' => 'kitchen',
        ]);

        // 4. Detail Ambil Bahan Gudang = 200
        $this->get("/headkitchen/kitchen/terima-stok/detail/{$ambil->id}?source=ambil_bahan_gudang")->assertStatus(200);

        // 5. Kitchen Features = 403
        $this->get('/kitchen/dashboard')->assertStatus(403);
        $this->get('/kitchen/update-stok')->assertStatus(403);
        $this->get('/kitchen/ambil-bahan')->assertStatus(403);
        $this->get('/kitchen/daily-clean')->assertStatus(403);
        $this->get('/kitchen/token-listrik')->assertStatus(403);
    }
}
