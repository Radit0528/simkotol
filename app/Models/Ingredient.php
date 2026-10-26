<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [BACKEND] Model Bahan Baku (US-06, BR-12 s/d BR-20).
 * Menyimpan data inventaris stok fisik, satuan dasar, stok minimum, dan moving average cost.
 */
class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'unit',
        'stock',
        'min_stock',
        'cost_per_unit',
        'is_active',
        'low_stock_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'cost_per_unit' => 'decimal:4',
            'is_active' => 'boolean',
            'low_stock_notified_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke resep produk (BOM).
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'ingredient_id');
    }

    /**
     * Relasi ke mutasi stok.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'ingredient_id');
    }

    /**
     * Status stok: 'out' (<= 0), 'low' (<= min_stock), atau 'ok' (aman).
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out';
        }

        if ($this->stock <= $this->min_stock) {
            return 'low';
        }

        return 'ok';
    }

    /**
     * Total nilai aset stok fisik saat ini.
     */
    public function getTotalCostValueAttribute(): float
    {
        return max(0, (float) $this->stock * (float) $this->cost_per_unit);
    }
}
