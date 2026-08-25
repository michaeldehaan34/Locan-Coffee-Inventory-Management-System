<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmbilBahanGudang extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ambil_bahan_gudang';

    /**
     * The table has timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true;
    protected $fillable = ['tanggal', 'shift', 'barista', 'barista_id', 'inventory_type'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AmbilBahanGudangItem::class, 'ambil_bahan_gudang_id');
    }

    public function user()
    {
        return $this->belongsTo(Barista::class, 'barista_id');
    }
}
