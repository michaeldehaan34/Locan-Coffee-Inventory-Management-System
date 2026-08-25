<?php

namespace Tests\Feature;

use App\Models\Barista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGudangAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function loginAsRole($role)
    {
        if ($role === 'manajemen') {
            session()->put([
                'user_id' => 1,
                'username' => 'test_manager',
                'role' => 'manajemen',
                'name' => 'Test User'
            ]);
            return;
        }

        $account = Barista::create([
            'username' => 'test_' . str_replace(' ', '_', $role),
            'role' => $role,
            'no_telp' => '1234567890',
            'status' => 'aktif'
        ]);

        session()->put([
            'user_id' => $account->id,
            'username' => $account->username,
            'role' => $role,
            'name' => 'Test User'
        ]);
    }

    private function getAdminGudangRoutes()
    {
        return [
            route('gudang.dashboard'),
            route('gudang.stok-masuk.index'),
            route('gudang.kirim-stok.index'),
            route('gudang.kirim-stok.create'),
            route('gudang.master-bahan'),
        ];
    }

    public function test_admin_gudang_can_access_own_routes()
    {
        $this->loginAsRole('admin gudang');

        foreach ($this->getAdminGudangRoutes() as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_admin_gudang_cannot_access_other_domains()
    {
        $this->loginAsRole('admin gudang');

        $forbiddenRoutes = [
            route('barista.dashboard'),
            route('headbar.dashboard'),
            route('kitchen.dashboard'),
            route('headkitchen.dashboard'),
            route('manager.dashboard'),
        ];

        foreach ($forbiddenRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403);
        }
    }

    public function test_other_roles_cannot_access_admin_gudang_routes()
    {
        $roles = ['barista', 'headbar', 'kitchen', 'headkitchen', 'manajemen'];

        foreach ($roles as $role) {
            $this->loginAsRole($role);

            foreach ($this->getAdminGudangRoutes() as $route) {
                $response = $this->get($route);
                $response->assertStatus(403);
            }
            
            session()->flush();
        }
    }
}
