<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Shift Kasir (US-11, US-12, US-13, BR-01, BR-02, BR-26 s/d BR-31).
 * Mengelola sesi kerja kasir, modal awal kas, rekonsiliasi kas, dan cash variance.
 */
class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_code',
        'user_id',
        'cash_float_start',
        'cash_expected_end',
        'cash_actual_end',
        'cash_variance',
        'total_orders',
        'total_cash_sales',
        'total_noncash_sales',
        'total_void_amount',
        'status',
        'opened_at',
        'closed_at',
        'closed_by',
        'close_notes',
    ];

    protected function casts(): array
    {
        return [
            'cash_float_start' => 'integer',
            'cash_expected_end' => 'integer',
            'cash_actual_end' => 'integer',
            'cash_variance' => 'integer',
            'total_orders' => 'integer',
            'total_cash_sales' => 'integer',
            'total_noncash_sales' => 'integer',
            'total_void_amount' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Kasir pemilik shift.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User yang menutup shift (bisa owner jika tutup paksa).
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Cek apakah shift masih open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}

