<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Buku Besar Mutasi Stok Bahan Baku (US-07, US-08, BR-13, BR-18).
 */
class StockMutation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'order_id',
        'order_item_id',
        'purchase_id',
        'user_id',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
