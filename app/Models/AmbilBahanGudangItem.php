<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbilBahanGudangItem extends Model
{
    protected $table = 'ambil_bahan_gudang_items';
    
    public $timestamps = true;

    protected $fillable = ['ambil_bahan_gudang_id', 'bahan_id', 'jumlah'];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(AmbilBahanGudang::class, 'ambil_bahan_gudang_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
