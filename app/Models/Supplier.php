<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [BACKEND] Model Pemasok / Supplier Bahan Baku (US-06, US-07).
 */
class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke transaksi pembelian bahan baku.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    /**
     * Memeriksa apakah supplier memiliki riwayat transaksi pembelian (BR-40).
     */
    public function hasPurchases(): bool
    {
        return $this->purchases()->exists();
    }
}
