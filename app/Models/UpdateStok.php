<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateStok extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'update_stok';

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
}