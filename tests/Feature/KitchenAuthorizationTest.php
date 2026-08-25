<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Barista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Barista $kitchen;
    private Barista $headkitchen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kitchen = Barista::create([
            'username' => 'kitchen_user',
            'nama_lengkap' => 'Kitchen User',
            'no_telp' => '08111111111',
            'role' => 'kitchen',
        ]);

        $this->headkitchen = Barista::create([
            'username' => 'headkitchen_user',
            'nama_lengkap' => 'Head Kitchen User',
            'no_telp' => '08222222222',
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

    private function loginAsKitchen()
    {
        $this->withSession([
            'user_id' => $this->kitchen->id,
            'username' => $this->kitchen->username,
            'role' => 'kitchen',
            'name' => $this->kitchen->nama_lengkap,
        ]);
    }

    public function test_kitchen_has_access_to_operational_features_but_not_headkitchen_features()
    {
        $this->loginAsKitchen();

        // 1. Dashboard Kitchen = 200
        $this->get('/kitchen/dashboard')->assertStatus(200);

        // 2. Kitchen Features = 200
        $this->get('/kitchen/ambil-bahan')->assertStatus(200);
        $this->get('/kitchen/update-stok')->assertStatus(200);
        $this->get('/kitchen/daily-clean')->assertStatus(200);
        $this->get('/kitchen/token-listrik')->assertStatus(200);

        // 3. Head Kitchen Features = 403
        $this->get('/headkitchen/dashboard')->assertStatus(403);
        $this->get('/headkitchen/kitchen/riwayat/terima-stok')->assertStatus(403);
    }
}
