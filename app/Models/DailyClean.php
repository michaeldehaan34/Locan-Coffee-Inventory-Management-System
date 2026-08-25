<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyClean extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daily_clean';

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
            'created_at' => 'datetime',
        ];
    }

    /**
     * The user who submitted this daily clean.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Barista::class, 'barista_id');
    }

    /**
     * The photos attached to this daily clean submission.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(DailyCleanPhoto::class, 'daily_clean_id');
    }
}