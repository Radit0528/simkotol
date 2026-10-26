<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [BACKEND] Model Produk Menu (US-04, US-05, BR-14, BR-18).
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'is_sold_out' => false,
        'cost_price' => 0,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'cost_price',
        'image',
        'is_active',
        'is_sold_out',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'is_active' => 'boolean',
            'is_sold_out' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'product_id');
    }

    /**
     * Hitung total HPP resep saat ini.
     */
    public function calculateHpp(): float
    {
        $this->loadMissing('recipes.ingredient');
        $total = 0;
        foreach ($this->recipes as $recipe) {
            $costPerUnit = (float) ($recipe->ingredient->cost_per_unit ?? 0);
            $total += (float) $recipe->amount_required * $costPerUnit;
        }

        return $total;
    }

    /**
     * Margin keuntungan (%) berdasarkan HPP dan harga jual.
     */
    public function getMarginPercentAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }

        $hpp = $this->cost_price > 0 ? $this->cost_price : $this->calculateHpp();

        return round((($this->price - $hpp) / $this->price) * 100, 1);
    }

    /**
     * URL publik foto menu.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
    }

    /**
     * Cek apakah produk menu sudah memiliki riwayat transaksi di order_items (BR-40).
     */
    public function hasTransactions(): bool
    {
        return \Illuminate\Support\Facades\DB::table('order_items')
            ->where('product_id', $this->id)
            ->exists();
    }
}
