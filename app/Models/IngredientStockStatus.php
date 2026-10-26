<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Read-Only SQL View v_ingredient_stock_status (US-10, BR-20).
 * Menghubungkan view agregat status inventaris bahan baku: ok | low | out.
 *
 * @property int $id
 * @property string $name
 * @property string $unit
 * @property float $stock
 * @property float $min_stock
 * @property string $stock_status
 */
class IngredientStockStatus extends Model
{
    protected $table = 'v_ingredient_stock_status';

    public $timestamps = false;

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
        ];
    }

    /**
     * Relasi ke model fisik bahan baku.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'id');
    }
}

