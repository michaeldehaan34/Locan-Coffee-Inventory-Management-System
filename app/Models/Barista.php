<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barista extends Model
{
    /**
     * The table associated with the model.
     *
     * Explicitly set to keep compatibility with the legacy
     * MySQL table name used by the original Flask project.
     *
     * @var string
     */
    protected $table = 'barista';

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
        'username',
        'password',
        'nama_lengkap',
        'no_telp',
        'role',
        'created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Token listrik submissions by this barista.
     */
    public function tokenListrik(): HasMany
    {
        return $this->hasMany(TokenListrik::class, 'barista_id');
    }

    /**
     * Daily clean submissions by this barista.
     */
    public function dailyCleans(): HasMany
    {
        return $this->hasMany(DailyClean::class, 'barista_id');
    }
}