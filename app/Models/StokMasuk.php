<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class StokMasuk extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stok_masuk';

    /**
     * The legacy schema only stores created_at (no updated_at),
     * so Eloquent timestamp management is disabled.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The transaction tables use one dynamic VARCHAR(12) column per bahan
     * (column name = bahan.kode). Because the set of columns is data-driven
     * (managed via Master Bahan), we allow mass assignment on all columns
     * rather than maintaining a fixed $fillable list.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(Barista::class, 'barista_id');
    }

    /**
     * Relasi ke Master Barang (tabel bahan).
     *
     * Skema legacy menyimpan satu kolom VARCHAR(12) per bahan (nama kolom =
     * bahan.kode), sehingga tidak ada foreign key fisik. Relasi ini
     * mengembalikan seluruh record Master Barang aktif yang memiliki kolom
     * terisi pada transaksi ini, lengkap dengan nama, satuan, kelompok, dan
     * kategori yang diambil langsung dari tabel bahan (Master Barang).
     *
     * Ini memastikan:
     *   1. Hanya barang aktif (is_active = 1) yang diikutkan.
     *   2. Nama barang diambil dari Master Barang.
     *   3. Satuan otomatis mengikuti Master Barang.
     *   4. Kelompok otomatis mengikuti Master Barang.
     *   5. Kategori otomatis mengikuti Master Barang.
     *
     * Tidak ada data hardcode — seluruh nilai berasal dari relasi Eloquent
     * ke model Bahan (homolog master_bahan.get_active_map() di Flask).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Bahan>
     */
    public function bahan()
    {
        // Kumpulkan kode bahan aktif yang memiliki nilai pada transaksi ini.
        $filledCodes = [];
        foreach (Bahan::activeKeys() as $kode) {
            $value = $this->{$kode};
            if ($value !== null && $value !== '') {
                $filledCodes[] = $kode;
            }
        }

        if (empty($filledCodes)) {
            return collect();
        }

        return Bahan::whereIn('kode', $filledCodes)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Item stok masuk yang sudah dipetakan ke Master Barang via relasi
     * Eloquent bahan(): [ 'nama', 'jumlah', 'satuan', 'kelompok', 'kategori' ].
     *
     * @return array<int, array{nama: string, jumlah: mixed, satuan: string, kelompok: string, kategori: string}>
     */
    public function itemsFromMaster(): array
    {
        $items = [];
        foreach ($this->bahan() as $b) {
            $items[] = [
                'nama' => $b->nama,
                'jumlah' => $this->{$b->kode},
                'satuan' => $b->satuan,
                'kelompok' => $b->kelompok ?: 'Lainnya',
                'kategori' => $b->kategori ?: 'Lainnya',
            ];
        }

        return $items;
    }
}
