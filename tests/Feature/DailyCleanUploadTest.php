<?php

namespace Tests\Feature;

use App\Models\Barista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyCleanUploadTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateBarista(): Barista
    {
        $barista = Barista::create([
            'username' => 'barista_upload',
            'nama_lengkap' => 'Barista Upload',
            'no_telp' => '081234567890',
            'role' => 'barista',
        ]);

        $this->withSession([
            'user_id' => $barista->id,
            'username' => $barista->username,
            'name' => $barista->nama_lengkap,
            'role' => 'barista',
        ]);

        return $barista;
    }

    public function test_daily_clean_menyimpan_foto_gambar_yang_valid(): void
    {
        Storage::fake('public');
        $barista = $this->authenticateBarista();
        $photos = collect(range(1, 4))
            ->map(fn (int $index) => UploadedFile::fake()->image("daily-clean-{$index}.jpg"))
            ->all();

        $response = $this->post(route('barista.daily-clean.store'), [
            'tanggal' => '2026-08-03',
            'shift' => 'Sekolah',
            'foto' => $photos,
        ]);

        $response->assertRedirect(route('barista.daily-clean'));
        $this->assertDatabaseHas('daily_clean', [
            'barista_id' => $barista->id,
            'tanggal' => '2026-08-03 00:00:00',
        ]);
        $this->assertDatabaseCount('daily_clean_photo', 4);
    }

    public function test_daily_clean_menolak_file_yang_bukan_gambar(): void
    {
        $this->authenticateBarista();

        $response = $this->from(route('barista.daily-clean.store'))
            ->post(route('barista.daily-clean.store'), [
                'tanggal' => '2026-08-03',
                'shift' => 'Sekolah',
                'foto' => [
                    UploadedFile::fake()->create('bukan-gambar.txt', 20, 'text/plain'),
                    UploadedFile::fake()->image('valid-1.jpg'),
                    UploadedFile::fake()->image('valid-2.jpg'),
                    UploadedFile::fake()->image('valid-3.jpg'),
                ],
            ]);

        $response->assertRedirect(route('barista.daily-clean.store'));
        $response->assertSessionHasErrors('foto.0');
        $this->assertDatabaseCount('daily_clean', 0);
    }
}
