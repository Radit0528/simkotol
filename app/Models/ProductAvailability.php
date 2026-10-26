<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Read-Only SQL View v_product_availability (US-14, BR-14).
 * Menghubungkan view agregat ketersediaan porsi produk menu berdasarkan stok bahan.
 *
 * @property int $product_id
 * @property string $name
 * @property bool $is_sold_out
 * @property int|null $max_portions
 */
class ProductAvailability extends Model
{
    protected $table = 'v_product_availability';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'product_id';

    protected function casts(): array
    {
        return [
            'is_sold_out' => 'boolean',
            'max_portions' => 'integer',
        ];
    }

    /**
     * Relasi ke model produk menu.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

