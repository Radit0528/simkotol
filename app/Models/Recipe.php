<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Resep / Bill of Materials (BOM) (US-09, BR-14, BR-18).
 * Menghubungkan produk menu dengan bahan baku dan takaran yang dibutuhkan.
 */
class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'amount_required',
    ];

    protected function casts(): array
    {
        return [
            'amount_required' => 'decimal:3',
        ];
    }

    /**
     * Relasi ke menu produk.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relasi ke bahan baku.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    /**
     * Estimasi HPP bahan pada takaran ini.
     */
    public function getEstimatedCostAttribute(): float
    {
        $costPerUnit = (float) ($this->ingredient->cost_per_unit ?? 0);

        return (float) $this->amount_required * $costPerUnit;
    }
}
