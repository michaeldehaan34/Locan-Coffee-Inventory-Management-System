<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Barista;
use App\Models\Manager;
use App\Models\UpdateStok;
use App\Models\AmbilBahanGudang;
use App\Models\AmbilBahanGudangItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private Barista $barista;
    private Barista $kitchen;
    private Manager $manager;
    private Barista $headbar;
    private Barista $headkitchen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Create Users
        $this->barista = Barista::create([
            'username' => 'user_barista',
            'nama_lengkap' => 'Nama User Barista',
            'no_telp' => '081111111111',
            'role' => 'barista',
        ]);

        $this->kitchen = Barista::create([
            'username' => 'user_kitchen',
            'nama_lengkap' => 'Mekel Kitchen',
            'no_telp' => '082222222222',
            'role' => 'kitchen',
        ]);

        $this->headkitchen = Barista::create([
            'username' => 'user_headkitchen',
            'nama_lengkap' => 'Head Kitchen',
            'no_telp' => '083333333333',
            'role' => 'headkitchen',
        ]);

        $this->headbar = Barista::create([
            'username' => 'user_headbar',
            'nama_lengkap' => 'Head Bar',
            'no_telp' => '084444444444',
            'role' => 'headbar',
        ]);

        $this->manager = Manager::create([
            'username' => 'admin_gudang',
            'no_telp' => '085555555555',
        ]);

        // Create Bahan
        Bahan::create([
            'kode' => 'arabica',
            'nama' => 'Arabica',
            'kategori' => 'Bahan Baku Bar',
            'kelompok' => 'Kopi',
            'satuan' => 'kg',
            'urutan' => 1,
            'is_active' => 1,
        ]);

        Bahan::create([
            'kode' => 'croissant',
            'nama' => 'Croissant',
            'kategori' => 'Bahan Baku Kitchen',
            'kelompok' => 'Pastry',
            'satuan' => 'pcs',
            'urutan' => 2,
            'is_active' => 1,
        ]);
        
        Bahan::create([
            'kode' => 'tissue',
            'nama' => 'Tissue',
            'kategori' => 'Equipment',
            'kelompok' => 'Utility',
            'satuan' => 'pcs',
            'urutan' => 3,
            'is_active' => 1,
        ]);

        // Add the dynamic columns to the test database schema
        \Illuminate\Support\Facades\Schema::table('update_stok', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('croissant')->nullable();
            $table->string('tissue')->nullable();
        });
        \Illuminate\Support\Facades\Schema::table('stok_masuk', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('croissant')->nullable();
            $table->string('tissue')->nullable();
        });
    }

    private function loginAs(Barista $user)
    {
        $this->withSession([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'name' => $user->nama_lengkap,
        ]);
    }

    private function loginAsManager()
    {
        $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'admin gudang', // Must match the role:admin gudang middleware
            'name' => 'Admin Gudang',
        ]);
    }

    public function test_kitchen_update_stok_shows_kitchen_role()
    {
        // Kitchen Update Stok
        $this->loginAs($this->kitchen);
        $this->withoutMiddleware();

        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->kitchen->nama_lengkap,
        ];
        foreach (Bahan::activeKeys('kitchen') as $kode) {
            $payload[$kode] = '10';
        }

        $this->post(route('kitchen.update-stok.store'), $payload);

        $record = UpdateStok::first();
        $this->assertNotNull($record);
        $this->assertEquals('kitchen', $record->inventory_type);
        $this->assertEquals($this->kitchen->id, $record->barista_id);

        // Viewed by Head Kitchen
        $this->loginAs($this->headkitchen);
        $response = $this->get(route('headkitchen.update-stok.detail', $record->id));
        $response->assertStatus(200);
        $response->assertSee('Mekel Kitchen');
        $response->assertSee('Kitchen');
        $response->assertDontSee('Barista');
    }

    public function test_barista_update_stok_shows_barista_role()
    {
        // Barista Update Stok
        $this->loginAs($this->barista);
        $this->withoutMiddleware();

        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->barista->nama_lengkap,
        ];
        foreach (Bahan::activeKeys('coffee_shop') as $kode) {
            $payload[$kode] = '5';
        }

        $this->post(route('barista.update-stok.store'), $payload);

        $record = UpdateStok::first();
        $this->assertNotNull($record);
        $this->assertEquals('coffee_shop', $record->inventory_type);
        $this->assertEquals($this->barista->id, $record->barista_id);

        // Viewed by Headbar
        $this->loginAs($this->headbar);
        $response = $this->get(route('headbar.update-stok.detail', $record->id));
        $response->assertStatus(200);
        $response->assertSee('Nama User Barista');
        $response->assertSee('Barista');
    }

    public function test_kitchen_ambil_bahan_gudang_tujuan_kitchen()
    {
        // Add stock to Gudang
        \App\Models\StokMasuk::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => 'Admin',
            'croissant' => 100,
            'tissue' => 100,
            'arabica' => 100,
        ]);

        $this->loginAs($this->kitchen);
        $this->withoutMiddleware();

        $this->post(route('kitchen.ambil-bahan.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->kitchen->nama_lengkap,
            'croissant' => '10',
            'tissue' => '5',
        ]);

        $record = AmbilBahanGudang::first();
        $this->assertNotNull($record);
        $this->assertEquals('kitchen', $record->inventory_type);

        // Viewed by Admin Gudang
        $this->loginAsManager();
        $response = $this->get(route('gudang.kirim-stok.detail', ['id' => $record->id, 'source' => 'ambil_bahan_gudang']));
        $response->assertStatus(200);
        
        $response->assertSee('Kitchen');
        // Ensure "Coffeeshop" is not shown for this Kitchen transaction
        $response->assertDontSee('Coffeeshop');
    }

    public function test_barista_ambil_bahan_gudang_tujuan_coffee_shop()
    {
        // Add stock to Gudang
        \App\Models\StokMasuk::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => 'Admin',
            'croissant' => 100,
            'tissue' => 100,
            'arabica' => 100,
        ]);

        $this->loginAs($this->barista);
        $this->withoutMiddleware();

        $this->post(route('barista.ambil-bahan-gudang.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->barista->nama_lengkap,
            'arabica' => '10',
            'tissue' => '5',
        ]);

        $record = AmbilBahanGudang::first();
        $this->assertNotNull($record);
        $this->assertEquals('coffee_shop', $record->inventory_type);

        // Viewed by Admin Gudang
        $this->loginAsManager();
        $response = $this->get(route('gudang.kirim-stok.detail', ['id' => $record->id, 'source' => 'ambil_bahan_gudang']));
        $response->assertStatus(200);
        
        $response->assertSee('Coffeeshop');
    }

    public function test_detail_ambil_bahan_gudang_kitchen_shows_role_kitchen()
    {
        $record = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->kitchen->nama_lengkap,
            'barista_id' => $this->kitchen->id,
            'inventory_type' => 'kitchen',
        ]);

        $this->loginAsManager();
        $response = $this->get(route('gudang.kirim-stok.detail', ['id' => $record->id, 'source' => 'ambil_bahan_gudang']));
        $response->assertStatus(200);
        $response->assertSee('Mekel Kitchen');
        $response->assertSee('Kitchen');
    }

    public function test_detail_ambil_bahan_gudang_barista_shows_role_barista()
    {
        $record = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => $this->barista->nama_lengkap,
            'barista_id' => $this->barista->id,
            'inventory_type' => 'coffee_shop',
        ]);

        $this->loginAsManager();
        $response = $this->get(route('gudang.kirim-stok.detail', ['id' => $record->id, 'source' => 'ambil_bahan_gudang']));
        $response->assertStatus(200);
        $response->assertSee('Nama User Barista');
        $response->assertSee('Barista');
    }

    public function test_inventory_isolation_in_ambil_bahan()
    {
        // Kitchen should see Kitchen and Equipment items, but not Bar items
        $this->loginAs($this->kitchen);
        $response = $this->get(route('kitchen.ambil-bahan'));
        $response->assertStatus(200);
        $response->assertSee('Croissant');
        $response->assertSee('Tissue');
        $response->assertDontSee('Arabica');

        // Barista should see Bar and Equipment items, but not Kitchen items
        $this->loginAs($this->barista);
        $response = $this->get(route('barista.ambil-bahan-gudang'));
        $response->assertStatus(200);
        $response->assertSee('Arabica');
        $response->assertSee('Tissue');
        $response->assertDontSee('Croissant');
    }

    public function test_data_transaksi_lama_backward_compatible_tanpa_error()
    {
        // Transaksi lama tanpa barista_id dan tanpa inventory_type
        $record = AmbilBahanGudang::create([
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Opening',
            'barista' => 'Nama Hardcode',
            'barista_id' => null,
            'inventory_type' => 'coffee_shop', 
        ]);

        $this->loginAsManager();
        $response = $this->get(route('gudang.kirim-stok.detail', ['id' => $record->id, 'source' => 'ambil_bahan_gudang']));
        $response->assertStatus(200);
        
        // Seharusnya menampilkan nama fallback
        $response->assertSee('Nama Hardcode');
        // Role fallback
        $response->assertSee('Barista');
        // Tujuan fallback
        $response->assertSee('Coffeeshop');
    }
}
