<?php

namespace Tests\Feature;

use App\Models\Barista;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Barista
        Barista::create([
            'username' => 'mekel_barista',
            'no_telp' => '081234567890', // password will be 567890
            'role' => 'barista',
        ]);

        // 2. Create Headbar
        Barista::create([
            'username' => 'mekel_headbar',
            'no_telp' => '081234567891', // password will be 567891
            'role' => 'headbar',
        ]);

        // 3. Create Kitchen
        Barista::create([
            'username' => 'mekel_kitchen',
            'no_telp' => '081234567892', // password will be 567892
            'role' => 'kitchen',
        ]);

        // 4. Create Headkitchen
        Barista::create([
            'username' => 'mekel_head_kitchen',
            'no_telp' => '081234567893', // password will be 567893
            'role' => 'headkitchen',
        ]);

        // 5. Create Admin Gudang
        Barista::create([
            'username' => 'mekel_gudang',
            'no_telp' => '081234567894', // password will be 567894
            'role' => 'admin gudang',
        ]);

        // 6. Create Manajemen
        Manager::create([
            'username' => 'Manajemen',
            'no_telp' => '081234567895',
            'password' => Hash::make('manajemen123'),
        ]);
    }

    public function test_barista_dapat_mengakses_dashboard_dan_fitur_barista()
    {
        $response = $this->post('/login', [
            'username' => 'barista:mekel_barista',
            'password' => '567890',
        ]);

        $response->assertRedirect('/barista/dashboard');
        
        $this->get('/barista/dashboard')->assertStatus(200);
        $this->get('/barista/update-stok')->assertStatus(200);
        
        // Cannot access temporary or manager dashboard
        $this->get('/dashboard/coming-soon')->assertStatus(403);
        $this->get('/manager/dashboard')->assertStatus(403);
    }

    public function test_manajemen_masuk_flow_manajemen_dan_tidak_dapat_akses_route_barista()
    {
        $response = $this->post('/login', [
            'username' => 'manager:Manajemen',
            'password' => 'manajemen123',
        ]);

        $response->assertRedirect('/manager/dashboard');
        
        $this->get('/manager/dashboard')->assertStatus(200);
        
        // Manager cannot access barista features using barista endpoints
        $this->get('/barista/dashboard')->assertStatus(403);
        $this->get('/barista/update-stok')->assertStatus(403);
        
        // Manager cannot access temporary dashboard
        $this->get('/dashboard/coming-soon')->assertStatus(403);
    }

    public function test_role_baru_diarahkan_ke_temporary_dashboard()
    {
        // Test headbar
        $response = $this->post('/login', [
            'username' => 'barista:mekel_headbar',
            'password' => '567891',
        ]);

        $response->assertRedirect('/headbar/dashboard');
        
        // Headbar cannot access barista or manager
        $this->get('/barista/dashboard')->assertStatus(403);
        $this->get('/manager/dashboard')->assertStatus(403);
        
        // Logout
        $this->post('/logout');

        // Test kitchen
        $response = $this->post('/login', [
            'username' => 'barista:mekel_kitchen',
            'password' => '567892',
        ]);

        $response->assertRedirect('/kitchen/dashboard');
        $this->get('/kitchen/dashboard')->assertStatus(200);
        $this->get('/barista/dashboard')->assertStatus(403);
    }

    public function test_manager_role_is_healed_to_manajemen_if_in_session()
    {
        // Simulate an old session
        $this->withSession(['role' => 'manager', 'username' => 'OldManager'])
             ->get('/manager/dashboard')
             ->assertStatus(200);
             
        $this->assertEquals('manajemen', session('role'));
    }
}
