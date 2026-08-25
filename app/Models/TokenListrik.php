<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenListrik extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'token_listrik';

    /**
     * The legacy schema only stores created_at (no updated_at),
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
        'tanggal',
        'shift',
        'barista_id',
        'barista',
        'inventory_type',
        'token_r17',
        'token_r18',
        'token_mesin',
        'catatan',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token_r17' => 'decimal:2',
            'token_r18' => 'decimal:2',
            'token_mesin' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The user who submitted this token reading.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Barista::class, 'barista_id');
    }
}