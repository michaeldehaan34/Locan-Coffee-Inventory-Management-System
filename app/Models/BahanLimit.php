<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BahanLimit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bahan_limit';

    /**
     * The legacy schema only stores created_at-less rows (no timestamps),
     * so Eloquent timestamp management is disabled.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key is the bahan_id (no auto-increment id).
     * Since we now use a composite key (bahan_id, inventory_type),
     * Eloquent's default save() might have issues, so updates should
     * be done via query builder or updateOrCreate.
     *
     * @var string
     */
    // protected $primaryKey = 'bahan_id'; // Removed to avoid composite key issues

    /**
     * The primary key is not an incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bahan_id',
        'inventory_type',
        'limit_habis',
        'limit_tipis',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit_habis' => 'float',
            'limit_tipis' => 'float',
        ];
    }

    /**
     * The bahan this limit belongs to.
     */
    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('bahan_id', $this->getAttribute('bahan_id'))
                     ->where('inventory_type', $this->getAttribute('inventory_type'));
    }
}