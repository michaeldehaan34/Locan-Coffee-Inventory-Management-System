<?php

namespace Tests\Feature;

use App\Models\Barista;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EditUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $barista;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user manager untuk login
        $this->manager = Manager::create([
            'username' => 'manager_test',
            'no_telp' => '081234567890',
        ]);

        // Buat user barista yang akan diedit
        $this->barista = Barista::create([
            'username' => 'barista_andi',
            'nama_lengkap' => 'Andi',
            'no_telp' => '089876543210', // password: 543210
            'role' => 'barista',
        ]);
    }

    public function test_management_dapat_mengubah_username()
    {
        $response = $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manajemen',
        ])->post("/manager/data-barista/edit/{$this->barista->id}", [
            'username' => 'andi_baru',
            'nama_lengkap' => 'Andi',
            'no_telp' => '089876543210',
            'role' => 'barista',
        ]);

        $response->assertRedirect(route('manager.data-barista'));

        $this->assertDatabaseHas('barista', [
            'id' => $this->barista->id,
            'username' => 'andi_baru',
        ]);
    }

    public function test_username_baru_dapat_digunakan_login()
    {
        // Ubah username
        $this->barista->update(['username' => 'andi_baru']);

        // Login dengan username baru
        $response = $this->post('/login', [
            'username' => 'andi_baru',
            'password' => '543210', // 6 digit terakhir dari 089876543210
        ]);

        $response->assertRedirect(route('barista.dashboard'));
        $this->assertAuthenticatedAsUser('andi_baru');
    }

    public function test_username_lama_tidak_dapat_digunakan_login()
    {
        // Ubah username
        $this->barista->update(['username' => 'andi_baru']);

        // Login dengan username lama
        $response = $this->post('/login', [
            'username' => 'barista_andi',
            'password' => '543210',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_password_tetap_sama_setelah_edit_username()
    {
        $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manajemen',
        ])->post("/manager/data-barista/edit/{$this->barista->id}", [
            'username' => 'andi_baru',
            'nama_lengkap' => 'Andi',
            'no_telp' => '089876543210',
            'role' => 'barista',
        ]);

        $this->assertDatabaseHas('barista', [
            'id' => $this->barista->id,
            'username' => 'andi_baru',
            'no_telp' => '089876543210',
        ]);

        // Login dengan password lama
        $response = $this->post('/login', [
            'username' => 'andi_baru',
            'password' => '543210', // 6 digit terakhir
        ]);

        $response->assertRedirect(route('barista.dashboard'));
    }

    public function test_username_duplicate_ditolak()
    {
        // Buat user B
        $baristaB = Barista::create([
            'username' => 'budi',
            'nama_lengkap' => 'Budi',
            'no_telp' => '081111111111',
            'role' => 'barista',
        ]);

        // Edit User A menjadi Budi
        $response = $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manajemen',
        ])->post("/manager/data-barista/edit/{$this->barista->id}", [
            'username' => 'budi',
            'nama_lengkap' => 'Andi',
            'no_telp' => '089876543210',
            'role' => 'barista',
        ]);

        $response->assertSessionHas('__flash'); // Expect a flash message to be set
        
        $this->assertDatabaseHas('barista', [
            'id' => $this->barista->id,
            'username' => 'barista_andi', // Tetap lama
        ]);
    }

    public function test_username_sendiri_tetap_valid()
    {
        $response = $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manajemen',
        ])->post("/manager/data-barista/edit/{$this->barista->id}", [
            'username' => 'barista_andi', // Sama dengan sebelumnya
            'nama_lengkap' => 'Andi Edit',
            'no_telp' => '089876543210',
            'role' => 'barista',
        ]);

        $response->assertRedirect(route('manager.data-barista'));

        $this->assertDatabaseHas('barista', [
            'id' => $this->barista->id,
            'username' => 'barista_andi',
            'nama_lengkap' => 'Andi Edit',
        ]);
    }

    public function test_non_management_tidak_dapat_mengubah_username()
    {
        // Login sebagai barista
        $response = $this->withSession([
            'user_id' => $this->barista->id,
            'username' => $this->barista->username,
            'role' => 'barista',
        ])->post("/manager/data-barista/edit/{$this->barista->id}", [
            'username' => 'hacked_andi',
            'nama_lengkap' => 'Andi',
            'no_telp' => '089876543210',
            'role' => 'barista',
        ]);

        $this->assertDatabaseMissing('barista', [
            'id' => $this->barista->id,
            'username' => 'hacked_andi',
        ]);
    }

    private function assertAuthenticatedAsUser($username)
    {
        $this->assertEquals($username, session('username'));
    }
}
