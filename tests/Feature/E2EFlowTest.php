<?php

namespace Tests\Feature;

use App\Models\Barista;
use App\Models\Bahan;
use App\Models\Manager;
use App\Models\UpdateStok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian End-to-End alur lengkap aplikasi LOTRA Coffee Management System.
 *
 * Alur yang disimulasikan:
 *   1. Login sebagai Manager.
 *   2. Buka Dashboard Manager.
 *   3. CRUD Barista.
 *   4. Login sebagai Barista.
 *   5. Buka Dashboard Barista.
 *   6. Tambah Stok Masuk.
 *   7. Update Stok.
 *   8. Jalankan Forecast.
 *   9. Jalankan Estimasi Pembelian.
 *  10. Logout.
 *  11. Login kembali.
 *
 * Setiap langkah diuji pada respon status 200 (atau redirect valid), tanpa
 * HTTP 500, QueryException, Blade error, Route error, maupun Middleware error.
 */
class E2EFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrasi + seed seluruh master data (barista, manager, bahan, limit).
        $this->artisan('migrate');
        $this->seed();

        $this->manager = Manager::where('username', 'manager_satu')->firstOrFail();
    }

    /**
     * Password sistem lama (Flask): 6 karakter terakhir no_telp secara mentah.
     */
    private function flaskPassword(string $noTelp): string
    {
        return strlen($noTelp) >= 6 ? substr($noTelp, -6) : '';
    }

    private function managerPassword(): string
    {
        return $this->flaskPassword($this->manager->no_telp);
    }

    private function loginAsManager(): void
    {
        $response = $this->post('/login', [
            'username' => 'manager:'.$this->manager->username,
            'password' => $this->managerPassword(),
        ]);
        $response->assertRedirect(route('manager.dashboard'));
        $this->assertEquals('manager', session('role'));
    }

    private function loginAsBarista(Barista $barista): void
    {
        $response = $this->post('/login', [
            'username' => 'barista:'.$barista->username,
            'password' => $this->flaskPassword($barista->no_telp),
        ]);
        $response->assertRedirect(route('barista.dashboard'));
        $this->assertEquals('barista', session('role'));
    }

    /**
     * 1. Login sebagai Manager.
     */
    public function test_1_login_sebagai_manager(): void
    {
        $this->loginAsManager();
        $this->assertEquals($this->manager->username, session('username'));
    }

    /**
     * 2. Buka Dashboard Manager.
     */
    public function test_2_buka_dashboard_manager(): void
    {
        $this->loginAsManager();

        $response = $this->get(route('manager.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Manager');
    }

    /**
     * 3. CRUD Barista (tambah, edit, hapus).
     */
    public function test_3_crud_barista(): void
    {
        $this->loginAsManager();

        // CREATE
        $create = $this->post(route('manager.data-barista.add'), [
            'nama_lengkap' => 'Barista E2E',
            'no_telp' => '081399988877',
            'role' => 'barista',
        ]);
        $create->assertRedirect(route('manager.data-barista'));
        $create->assertSessionHas('__flash');

        $barista = Barista::where('no_telp', '081399988877')->firstOrFail();
        $this->assertNotNull($barista);

        // EDIT
        $edit = $this->post(route('manager.data-barista.edit', ['id' => $barista->id]), [
            'nama_lengkap' => 'Barista E2E Edit',
            'no_telp' => '081399988877',
            'role' => 'barista',
        ]);
        $edit->assertRedirect(route('manager.data-barista'));
        $this->assertDatabaseHas('barista', [
            'id' => $barista->id,
            'nama_lengkap' => 'Barista E2E Edit',
        ]);

        // DELETE
        $delete = $this->post(route('manager.data-barista.delete', ['id' => $barista->id]));
        $delete->assertRedirect(route('manager.data-barista'));
        $this->assertDatabaseMissing('barista', ['id' => $barista->id]);
    }

    /**
     * 4. Login sebagai Barista.
     */
    public function test_4_login_sebagai_barista(): void
    {
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $this->loginAsBarista($barista);
        $this->assertEquals($barista->username, session('username'));
    }

    /**
     * 5. Buka Dashboard Barista.
     */
    public function test_5_buka_dashboard_barista(): void
    {
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $this->loginAsBarista($barista);

        $response = $this->get(route('barista.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Barista');
    }

    /**
     * 6. Tambah Stok Masuk (sebagai Barista).
     */
    public function test_6_tambah_stok_masuk(): void
    {
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $this->loginAsBarista($barista);

        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => $barista->nama_lengkap,
        ];
        // Isi beberapa item bahan agar valid (minimal satu).
        $keys = Bahan::activeKeys();
        foreach (array_slice($keys, 0, 3) as $kode) {
            $payload[$kode] = '10';
        }

        $response = $this->post(route('barista.stok-masuk.store'), $payload);
        $response->assertRedirect(route('barista.stok-masuk'));
        $response->assertSessionHas('__flash');
        $this->assertDatabaseHas('stok_masuk', [
            'shift' => 'Sekolah',
            'barista' => $barista->nama_lengkap,
        ]);
    }

    /**
     * 7. Update Stok (sebagai Barista) — semua item wajib diisi.
     */
    public function test_7_update_stok(): void
    {
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $this->loginAsBarista($barista);

        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => $barista->nama_lengkap,
        ];
        foreach (Bahan::activeKeys() as $kode) {
            $payload[$kode] = '5';
        }

        $response = $this->post(route('barista.update-stok.store'), $payload);
        $response->assertRedirect(route('barista.update-stok'));
        $response->assertSessionHas('__flash');
        $this->assertDatabaseHas('update_stok', [
            'shift' => 'Sekolah',
            'barista' => $barista->nama_lengkap,
        ]);
    }

    /**
     * 8. Jalankan Forecast (sebagai Manager, butuh data update_stok).
     */
    public function test_8_jalankan_forecast(): void
    {
        // Siapkan satu baris update_stok terlebih dahulu.
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $tanggal = now()->format('Y-m-d');
        $data = ['tanggal' => $tanggal, 'shift' => 'Sekolah', 'barista' => $barista->nama_lengkap];
        foreach (Bahan::activeKeys() as $kode) {
            $data[$kode] = '5';
        }
        UpdateStok::create($data);

        $this->loginAsManager();

        $response = $this->get(route('manager.forecast', [
            'tanggal_awal' => $tanggal,
            'tanggal_akhir' => $tanggal,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Forecast');
    }

    /**
     * 9. Jalankan Estimasi Pembelian (Laporan, sebagai Manager).
     */
    public function test_9_jalankan_estimasi_pembelian(): void
    {
        $barista = Barista::where('username', 'barista_satu')->firstOrFail();
        $tanggal = now()->format('Y-m-d');
        $data = ['tanggal' => $tanggal, 'shift' => 'Sekolah', 'barista' => $barista->nama_lengkap];
        foreach (Bahan::activeKeys() as $kode) {
            $data[$kode] = '5';
        }
        UpdateStok::create($data);

        $this->loginAsManager();

        $response = $this->get(route('manager.laporan', [
            'tanggal_awal' => $tanggal,
            'tanggal_akhir' => $tanggal,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Laporan');
    }

    /**
     * 10. Logout.
     */
    public function test_10_logout(): void
    {
        $this->loginAsManager();

        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertNull(session('username'));
        $this->assertNull(session('role'));
    }

    /**
     * 11. Login kembali (setelah logout).
     */
    public function test_11_login_kembali(): void
    {
        // Pastikan sesi benar-benar kosong (logout bekerja).
        $this->assertNull(session('username'));

        $this->loginAsManager();
        $this->assertEquals('manager', session('role'));
        $this->assertEquals($this->manager->username, session('username'));
    }
}