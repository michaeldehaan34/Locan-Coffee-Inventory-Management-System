<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bahan extends Model
{
    /**
     * The table associated with the model.
     *
     * Explicitly set to keep compatibility with the legacy
     * MySQL table name used by the original Flask project.
     *
     * @var string
     */
    protected $table = 'bahan';

    /**
     * The legacy schema only stores created_at-less rows (no timestamps),
     * so Eloquent timestamp management is disabled.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'kelompok',
        'satuan',
        'urutan',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The stock limit settings for this bahan (1:1).
     */
    public function limit(): HasOne
    {
        return $this->hasOne(BahanLimit::class, 'bahan_id');
    }

    /**
     * Filter by inventory type (Coffeeshop or Kitchen)
     */
    public function scopeForInventory($query, $inventoryType)
    {
        if ($inventoryType === 'coffee_shop') {
            return $query->whereIn('kategori', ['Bahan Baku Bar', 'Equipment']);
        } elseif ($inventoryType === 'kitchen') {
            return $query->whereIn('kategori', ['Bahan Baku Kitchen', 'Equipment']);
        }
        return $query;
    }

    /**
     * Active bahan only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Daftar (id, nama) bahan aktif — untuk autocomplete search box.
     *
     * @return array<int, array{id: int, nama: string}>
     */
    public static function activeItems($inventoryType = null): array
    {
        $query = self::active();
        if ($inventoryType) {
            $query->forInventory($inventoryType);
        }

        return $query
            ->orderByRaw("CASE kategori WHEN 'Bahan Baku Bar' THEN 1 WHEN 'Bahan Baku Kitchen' THEN 2 WHEN 'Equipment' THEN 3 ELSE 4 END")
            ->orderBy('kelompok', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'kode', 'kategori', 'kelompok', 'satuan'])
            ->map(fn ($b) => ['id' => $b->id, 'nama' => $b->nama, 'kode' => $b->kode, 'kategori' => $b->kategori, 'kelompok' => $b->kelompok, 'satuan' => $b->satuan])
            ->all();
    }

    /**
     * Kode bahan aktif (homolog master_bahan.get_active_keys()).
     *
     * @return array<int, string>
     */
    public static function activeKeys($inventoryType = null): array
    {
        $query = self::active();
        if ($inventoryType) {
            $query->forInventory($inventoryType);
        }

        return $query
            ->orderByRaw("CASE kategori WHEN 'Bahan Baku Bar' THEN 1 WHEN 'Bahan Baku Kitchen' THEN 2 WHEN 'Equipment' THEN 3 ELSE 4 END")
            ->orderBy('kelompok', 'asc')
            ->orderBy('nama', 'asc')
            ->pluck('kode')
            ->all();
    }

    /**
     * Bangun hierarki 3 level Kategori -> Kelompok -> Barang (hanya aktif).
     *
     * @return array<int, array{kategori: string, kelompok_list: array<int, array{kelompok: string, items: array<int, array{kode: string, nama: string, satuan: string}>}>}>
     */
    public static function groupedActiveTree($inventoryType = null): array
    {
        $query = self::active();
        if ($inventoryType) {
            $query->forInventory($inventoryType);
        }

        $rows = $query
            ->orderByRaw("CASE kategori WHEN 'Bahan Baku Bar' THEN 1 WHEN 'Bahan Baku Kitchen' THEN 2 WHEN 'Equipment' THEN 3 ELSE 4 END")
            ->orderBy('kelompok', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['kategori', 'kelompok', 'kode', 'nama', 'satuan'])
            ->all();

        $tree = [];
        foreach ($rows as $row) {
            $kat = $row->kategori ?: 'Lainnya';
            $grp = $row->kelompok ?: 'Lainnya';

            if (! isset($tree[$kat])) {
                $tree[$kat] = [];
            }
            if (! isset($tree[$kat][$grp])) {
                $tree[$kat][$grp] = [];
            }
            $tree[$kat][$grp][] = [
                'kode' => $row->kode,
                'nama' => $row->nama,
                'satuan' => $row->satuan,
            ];
        }

        $result = [];
        foreach ($tree as $kategori => $groups) {
            $kelompokList = [];
            foreach ($groups as $kelompok => $items) {
                $kelompokList[] = [
                    'kelompok' => $kelompok,
                    'items' => $items,
                ];
            }
            $result[] = [
                'kategori' => $kategori,
                'kelompok_list' => $kelompokList,
            ];
        }

        return $result;
    }
}
