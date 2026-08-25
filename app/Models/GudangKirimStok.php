<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudangKirimStok extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gudang_kirim_stok';

    /**
     * The table has timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = ['tanggal', 'manager', 'barista_id', 'status', 'tujuan', 'received_at', 'received_by'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GudangKirimStokItem::class, 'gudang_kirim_stok_id');
    }

    public function user()
    {
        return $this->belongsTo(Barista::class, 'barista_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Barista::class, 'received_by');
    }
}
