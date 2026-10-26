<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [BACKEND] Model Pengaturan Toko & Sistem (US-32, BR-04, BR-05).
 * Menyimpan konfigurasi key-value seperti pajak, struk, dan pembayaran.
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'updated_by',
    ];

    /**
     * The "booted" method of the model.
     * Mengosongkan cache setting secara otomatis setiap ada perubahan.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('simkotol.settings.all');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('simkotol.settings.all');
        });
    }

    /**
     * Relasi ke user yang terakhir memperbarui setting.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ambil nilai setting berdasarkan key dengan fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'int' => (int) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set nilai setting berdasarkan key.
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?int $userId = null): self
    {
        $type = 'string';

        if (is_int($value)) {
            $type = 'int';
            $stringValue = (string) $value;
        } elseif (is_bool($value)) {
            $type = 'bool';
            $stringValue = $value ? '1' : '0';
        } elseif (is_array($value)) {
            $type = 'json';
            $stringValue = json_encode($value);
        } else {
            $stringValue = (string) $value;
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'group' => $group ?? 'general',
                'updated_by' => $userId,
            ]
        );
    }
}

