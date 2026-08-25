<?php

namespace Tests\Feature;

use App\Models\Barista;
use App\Models\Bahan;
use App\Models\Manager;
use App\Models\UpdateStok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Pengujian modul Update Stok (Barista).
 *
 * Memastikan:
 *  1. Field wajib (tanggal, shift, barista, dan SEMUA item bahan) terisi.
 *  2. Validasi identik dengan project Flask (modules/update_stok.py):
 *     - tanggal wajib
 *     - shift harus valid (ada di daftar shift)
 *     - barista wajib
 *     - SEMUA item wajib diisi (kosong -> ditolak), "0" dianggap valid
 *     - nilai harus berupa angka
 *  3. Submit berhasil (redirect + flash success).
 *  4. Histori update stok tersimpan di tabel update_stok.
 */
class UpdateStokTest extends TestCase
{
    use RefreshDatabase;

    private Barista $barista;
    private Manager $manager;

        protected function setUp(): void
    {
        parent::setUp();

        // Jalankan migrasi (sqlite in-memory saat testing).
        $this->artisan('migrate');

        // Buat barista uji (password = 6 digit terakhir no_telp, ala Flask).
                $this->barista = Barista::create([
            'username' => 'tester_update_stok',
            'nama_lengkap' => 'Tester Update Stok',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);
        $this->manager = Manager::create([
            'username' => 'manager_update_stok',
            'no_telp' => '081298765432',
        ]);

        // Pastikan ada minimal satu bahan aktif agar form punya item.

        if (Bahan::active()->count() === 0) {
            Bahan::create([
                'kode' => 'arabica',
                'nama' => 'Arabica',
                'kategori' => 'Bahan Baku Bar',
                'kelompok' => 'Kopi',
                'satuan' => 'kg',
                'urutan' => 1,
                'is_active' => 1,
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

        private function loginAsManager(): void
    {
        $this->withSession([
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manager',
            'name' => $this->manager->username,
        ]);
    }

        private function validPayload(array $overrides = []): array
    {
        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'shift' => 'Sekolah',
            'barista' => $this->barista->nama_lengkap,
        ];

        foreach (Bahan::activeKeys() as $kode) {
            $payload[$kode] = '5';
        }

        return array_merge($payload, $overrides);
    }

    public function test_halaman_update_stok_dapat_diakses(): void
    {
        $this->login();

        $response = $this->get(route('barista.update-stok'));

        $response->assertStatus(200);
        $response->assertSee('Update Stok');
    }

        public function test_manager_tidak_dapat_membuka_form_update_stok(): void
    {
        $this->loginAsManager();

        $response = $this->get(route('barista.update-stok'));

        $response->assertStatus(403);
    }

    public function test_manager_tidak_dapat_submit(): void
    {
        $this->loginAsManager();

        $response = $this->post(route('barista.update-stok.store'), [
            'tanggal' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

        public function test_submit_berhasil_dan_histori_tersimpan(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload();

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect(route('barista.update-stok'));
        $response->assertSessionHas('__flash');

        // Histori tersimpan di tabel update_stok.
        $this->assertDatabaseHas('update_stok', [
            'shift' => 'Sekolah',
            'barista' => $this->barista->nama_lengkap,
        ]);
        $this->assertSame($payload['tanggal'], UpdateStok::where('barista', $this->barista->nama_lengkap)->first()->tanggal->format('Y-m-d'));

        $record = UpdateStok::where('barista', $this->barista->nama_lengkap)->first();
        $this->assertNotNull($record);
        foreach (Bahan::activeKeys() as $kode) {
            $this->assertEquals('5', (string) $record->{$kode});
        }
    }

    public function test_semua_item_wajib_diisi_kosong_ditolak(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload();
        // Kosongkan satu item bahan (seperti Flask: field kosong -> ditolak).
        $firstKey = Bahan::activeKeys()[0];
        $payload[$firstKey] = '';

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('__flash');
        $this->assertDatabaseMissing('update_stok', [
            'barista' => $this->barista->nama_lengkap,
        ]);
    }

    public function test_nilai_nol_diizinkan_seperti_flask(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload();
        $firstKey = Bahan::activeKeys()[0];
        $payload[$firstKey] = '0';

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect(route('barista.update-stok'));
        $this->assertDatabaseHas('update_stok', [
            'barista' => $this->barista->nama_lengkap,
        ]);
    }

    public function test_nilai_bukan_angka_ditolak(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload();
        $firstKey = Bahan::activeKeys()[0];
        $payload[$firstKey] = 'abc';

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('update_stok', [
            'barista' => $this->barista->nama_lengkap,
        ]);
    }

    public function test_active_keys_menggunakan_sort_order(): void
    {
        Bahan::query()->delete();

        Bahan::create([
            'kode' => 'zeta',
            'nama' => 'Zeta',
            'kategori' => 'Bahan Baku Bar',
            'kelompok' => 'Test',
            'satuan' => 'pcs',
            'urutan' => 2,
            'sort_order' => 20,
            'is_active' => 1,
        ]);

        Bahan::create([
            'kode' => 'alpha',
            'nama' => 'Alpha',
            'kategori' => 'Bahan Baku Bar',
            'kelompok' => 'Test',
            'satuan' => 'pcs',
            'urutan' => 1,
            'sort_order' => 10,
            'is_active' => 1,
        ]);

        $this->assertSame(['alpha', 'zeta'], Bahan::activeKeys());
    }

    public function test_shift_tidak_valid_ditolak(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload(['shift' => '']);

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('update_stok', [
            'barista' => $this->barista->nama_lengkap,
        ]);
    }

    public function test_tanggal_kosong_ditolak(): void
    {
        $this->login();
        $this->withoutMiddleware();

        $payload = $this->validPayload(['tanggal' => '']);

        $response = $this->post(route('barista.update-stok.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('update_stok', [
            'barista' => $this->barista->nama_lengkap,
        ]);
    }
}