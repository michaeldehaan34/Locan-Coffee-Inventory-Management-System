<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Manager;
use App\Models\Barista;
use App\Models\Bahan;
use App\Models\GudangKirimStok;
use App\Models\AmbilBahanGudang;

class IntegrasiRiwayatPenerimaanStokTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $barista;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->headbar = Barista::create([
            'username' => 'test_headbar',
            'nama_lengkap' => 'Test Headbar',
            'no_telp' => '081234567891',
            'role' => 'headbar',
        ]);
        
        $this->barista = Barista::create([
            'username' => 'test_barista',
            'nama_lengkap' => 'Test Barista',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);
        
        Bahan::create([
            'kode' => 'uht_milk',
            'nama' => 'Susu UHT',
            'satuan' => 'L',
            'is_active' => true,
        ]);
    }

    public function test_a_source_collision()
    {
        GudangKirimStok::forceCreate([
            'id' => 1,
            'tanggal' => now()->format('Y-m-d'),
            'manager' => 'Manager 1',
            'status' => 'diterima',
        ]);

        AmbilBahanGudang::forceCreate([
            'id' => 1,
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista 1',
        ]);

        $response1 = $this->withSession(['user_id' => $this->headbar->id, 'username' => $this->headbar->username, 'role' => 'headbar', 'name' => $this->headbar->nama_lengkap])
            ->get(route('headbar.coffee-shop.terima-stok.detail', ['id' => 1, 'source' => 'gudang_kirim']));
        
        $response1->assertStatus(200);
        $response1->assertSee('Gudang Kirim Stok');
        $response1->assertSee('Manager 1');

        $response2 = $this->withSession(['user_id' => $this->headbar->id, 'username' => $this->headbar->username, 'role' => 'headbar', 'name' => $this->headbar->nama_lengkap])
            ->get(route('headbar.coffee-shop.terima-stok.detail', ['id' => 1, 'source' => 'ambil_bahan_gudang']));
        
        $response2->assertStatus(200);
        $response2->assertSee('Ambil Bahan Gudang');
        $response2->assertSee('Barista 1');
    }

    public function test_b_ambil_bahan_tidak_ada_edit_hapus()
    {
        AmbilBahanGudang::forceCreate([
            'id' => 10,
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Test Barista',
        ]);
        
        $response = $this->withSession(['user_id' => $this->headbar->id, 'username' => $this->headbar->username, 'role' => 'headbar', 'name' => $this->headbar->nama_lengkap])
            ->get(route('headbar.coffee-shop.terima-stok.index'));
            
        $response->assertStatus(200);
        $response->assertSee('Test Barista');
        $response->assertSee('Ambil Bahan Gudang');
        
        $editUrl = route('headbar.coffee-shop.terima-stok.edit', 10);
        $response->assertDontSee($editUrl);
    }

    public function test_c_gudang_kirim_ada_edit_hapus()
    {
        GudangKirimStok::forceCreate([
            'id' => 20,
            'tanggal' => now()->format('Y-m-d'),
            'manager' => 'Test Manager',
            'status' => 'diterima',
        ]);
        
        $response = $this->withSession(['user_id' => $this->headbar->id, 'username' => $this->headbar->username, 'role' => 'headbar', 'name' => $this->headbar->nama_lengkap])
            ->get(route('headbar.coffee-shop.terima-stok.index'));
            
        $response->assertStatus(200);
        $response->assertSee('Test Manager');
        $response->assertSee('Gudang Kirim Stok');
        
        $editUrl = route('headbar.coffee-shop.terima-stok.edit', 20);
        $response->assertSee($editUrl);
    }

    public function test_d_combined_history_sorted()
    {
        GudangKirimStok::forceCreate([
            'id' => 1,
            'tanggal' => now()->subDays(2)->format('Y-m-d'),
            'manager' => 'Manager 1',
            'status' => 'diterima',
            'created_at' => now()->subDays(2),
        ]);
        
        AmbilBahanGudang::forceCreate([
            'id' => 1,
            'tanggal' => now()->subDays(1)->format('Y-m-d'),
            'shift' => 'Pagi',
            'barista' => 'Barista 1',
            'created_at' => now()->subDays(1),
        ]);

        GudangKirimStok::forceCreate([
            'id' => 2,
            'tanggal' => now()->format('Y-m-d'),
            'manager' => 'Manager 2',
            'status' => 'diterima',
            'created_at' => now(),
        ]);

        $response = $this->withSession(['user_id' => $this->headbar->id, 'username' => $this->headbar->username, 'role' => 'headbar', 'name' => $this->headbar->nama_lengkap])
            ->get(route('headbar.coffee-shop.terima-stok.index'));
            
        $response->assertStatus(200);
        
        $html = $response->getContent();
        $posKirim2 = strpos($html, 'Manager 2');
        $posAmbil1 = strpos($html, 'Barista 1');
        $posKirim1 = strpos($html, 'Manager 1');
        
        $this->assertTrue($posKirim2 < $posAmbil1);
        $this->assertTrue($posAmbil1 < $posKirim1);
    }

    public function test_e_dashboard_barista_link()
    {
        $response = $this->withSession(['user_id' => $this->barista->id, 'username' => $this->barista->username, 'role' => 'barista', 'name' => $this->barista->nama_lengkap])
            ->get(route('barista.dashboard'));
            
        $response->assertStatus(200);
        $response->assertSee('Ambil Bahan Gudang');
        
        $link = route('barista.ambil-bahan-gudang');
        $response->assertSee($link);
    }
}
