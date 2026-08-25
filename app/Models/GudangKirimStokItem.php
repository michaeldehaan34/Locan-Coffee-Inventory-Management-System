<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudangKirimStokItem extends Model
{
    protected $table = 'gudang_kirim_stok_items';
    
    public $timestamps = true;

    protected $fillable = ['gudang_kirim_stok_id', 'bahan_id', 'jumlah'];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(GudangKirimStok::class, 'gudang_kirim_stok_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
