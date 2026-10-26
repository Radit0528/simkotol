<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [BACKEND] Model Pembelian Bahan Baku (US-07, BR-19).
 */
class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'user_id',
        'purchased_at',
        'total_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'total_cost' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /**
     * Relasi ke mutasi stok yang dihasilkan oleh pembelian ini.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'purchase_id');
    }
}
