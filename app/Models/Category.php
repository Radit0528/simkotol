<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [BACKEND] Model Kategori Menu (US-03).
 * Mengelompokkan produk menu di POS dan manajemen katalog.
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relasi ke seluruh produk dalam kategori ini.
     */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Relasi ke produk aktif dalam kategori ini.
     */
    public function activeProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'category_id')->where('is_active', true);
    }

    /**
     * Cek apakah kategori masih memiliki menu/produk yang berstatus aktif (US-03).
     */
    public function hasActiveProducts(): bool
    {
        return $this->activeProducts()->exists();
    }
}

