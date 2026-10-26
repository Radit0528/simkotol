<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Pengaturan Toko, Pajak, Struk & Pembayaran (US-32, US-31, BR-03, BR-04, BR-05, BR-16).
 * Mengelola konfigurasi global sistem dengan caching, audit logging, penanganan logo struk,
 * serta kalkulasi kalkulator pesanan (tax, service charge, cash rounding).
 */
class SettingService
{
    /**
     * Cache key untuk menyimpan seluruh settings di memori.
     */
    public const CACHE_KEY = 'simkotol.settings.all';

    /**
     * Definisi default setting sistem (US-32, BR-04, BR-05, BR-16).
     *
     * @var array<string, array{group: string, type: string, default: mixed}>
     */
    public const DEFAULTS = [
        'store_name' => [
            'group' => 'store',
            'type' => 'string',
            'default' => 'Kopi Titik Nol',
        ],
        'store_address' => [
            'group' => 'store',
            'type' => 'string',
            'default' => 'Jl. Kaliurang KM 5, Yogyakarta',
        ],
        'store_phone' => [
            'group' => 'store',
            'type' => 'string',
            'default' => '081234567890',
        ],
        'store_logo' => [
            'group' => 'store',
            'type' => 'string',
            'default' => null,
        ],
        'receipt_footer' => [
            'group' => 'receipt',
            'type' => 'string',
            'default' => 'Terima kasih, sampai jumpa lagi!',
        ],
        'paper_size' => [
            'group' => 'receipt',
            'type' => 'int',
            'default' => 80,
        ],
        'tax_percent' => [
            'group' => 'tax',
            'type' => 'string',
            'default' => '0',
        ],
        'service_percent' => [
            'group' => 'tax',
            'type' => 'string',
            'default' => '0',
        ],
        'tax_inclusive' => [
            'group' => 'tax',
            'type' => 'bool',
            'default' => false,
        ],
        'cash_rounding' => [
            'group' => 'tax',
            'type' => 'int',
            'default' => 100,
        ],
        'qris_expiry_minutes' => [
            'group' => 'payment',
            'type' => 'int',
            'default' => 15,
        ],
        'payment_provider' => [
            'group' => 'payment',
            'type' => 'string',
            'default' => 'midtrans',
        ],
        'allow_negative_stock_on_paid' => [
            'group' => 'stock',
            'type' => 'bool',
            'default' => true,
        ],
    ];

    /**
     * [BACKEND] Mengambil seluruh konfigurasi sistem yang digabungkan dengan nilai default (US-32).
     * Hasil pencarian disimpan di cache untuk performa tinggi pada checkout/POS.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $dbSettings = Setting::all()->keyBy('key');
            $result = [];

            foreach (self::DEFAULTS as $key => $definition) {
                if ($dbSettings->has($key)) {
                    $setting = $dbSettings->get($key);
                    $result[$key] = $this->castValue($setting->value, $setting->type);
                } else {
                    $result[$key] = $definition['default'];
                }
            }

            // Tambahkan setting custom yang tidak ada di DEFAULTS jika ada di DB
            foreach ($dbSettings as $key => $setting) {
                if (! array_key_exists($key, $result)) {
                    $result[$key] = $this->castValue($setting->value, $setting->type);
                }
            }

            return $result;
        });
    }

    /**
     * [BACKEND] Mengambil konfigurasi berdasarkan grup (store, receipt, tax, payment, stock) (US-32).
     *
     * @param  string  $group
     * @return array<string, mixed>
     */
    public function getByGroup(string $group): array
    {
        $all = $this->all();
        $filtered = [];

        foreach ($all as $key => $value) {
            $groupName = self::DEFAULTS[$key]['group'] ?? 'general';
            if ($groupName === $group) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * [BACKEND] Mengambil satu nilai konfigurasi berdasarkan key (US-32).
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        if (isset(self::DEFAULTS[$key])) {
            return self::DEFAULTS[$key]['default'];
        }

        return $default;
    }

    /**
     * [BACKEND] Mengubah atau membuat satu nilai setting (US-32, US-31).
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $group
     * @param  int|null  $userId
     * @return Setting
     */
    public function set(string $key, mixed $value, ?string $group = null, ?int $userId = null): Setting
    {
        $oldValue = $this->get($key);
        $definition = self::DEFAULTS[$key] ?? null;
        $resolvedGroup = $group ?? ($definition['group'] ?? 'general');
        $resolvedType = $definition['type'] ?? $this->detectType($value);

        $stringValue = $this->formatForStorage($value, $resolvedType);

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $resolvedGroup,
                'value' => $stringValue,
                'type' => $resolvedType,
                'updated_by' => $userId,
            ]
        );

        // Audit log jika nilai berubah (US-31)
        $newValue = $this->castValue($stringValue, $resolvedType);
        if ($oldValue !== $newValue) {
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'setting.updated',
                'auditable_type' => Setting::class,
                'auditable_id' => $setting->id,
                'old_values' => [$key => $oldValue],
                'new_values' => [$key => $newValue],
                'created_at' => now(),
            ]);
        }

        $this->clearCache();

        return $setting;
    }

    /**
     * [BACKEND] Memperbarui banyak konfigurasi sekaligus dengan validasi lengkap & audit log (US-32, US-31).
     *
     * @param  array<string, mixed>  $data
     * @param  int|null  $userId
     * @param  UploadedFile|null  $logo
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function updateSettings(array $data, ?int $userId = null, ?UploadedFile $logo = null): array
    {
        // Jika ada logo di dalam array data, ambil
        if (! $logo && isset($data['store_logo']) && $data['store_logo'] instanceof UploadedFile) {
            $logo = $data['store_logo'];
            unset($data['store_logo']);
        }

        // 1. Validasi input
        $validator = Validator::make($data, [
            'store_name' => 'sometimes|required|string|max:100',
            'store_address' => 'nullable|string|max:255',
            'store_phone' => 'nullable|string|max:30',
            'receipt_footer' => 'nullable|string|max:255',
            'paper_size' => 'sometimes|required|in:58,80',
            'tax_percent' => 'sometimes|required|numeric|min:0|max:100',
            'service_percent' => 'sometimes|required|numeric|min:0|max:100',
            'tax_inclusive' => 'sometimes|required|boolean',
            'cash_rounding' => 'sometimes|required|integer|min:0|max:10000',
            'qris_expiry_minutes' => 'sometimes|required|integer|min:1|max:1440',
            'payment_provider' => 'sometimes|required|string|max:50',
            'allow_negative_stock_on_paid' => 'sometimes|required|boolean',
        ], [
            'store_name.required' => 'Nama toko wajib diisi.',
            'paper_size.in' => 'Ukuran kertas printer struk harus 58mm atau 80mm.',
            'tax_percent.min' => 'Persentase pajak tidak boleh bernilai negatif.',
            'tax_percent.max' => 'Persentase pajak maksimal 100%.',
            'service_percent.min' => 'Persentase service charge tidak boleh negatif.',
            'service_percent.max' => 'Persentase service charge maksimal 100%.',
            'cash_rounding.min' => 'Nilai pembulatan tunai tidak boleh negatif.',
            'qris_expiry_minutes.min' => 'Masa berlaku QRIS minimal 1 menit.',
            'qris_expiry_minutes.max' => 'Masa berlaku QRIS maksimal 1440 menit (24 jam).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // 2. Validasi & Simpan Logo Toko jika ada
        if ($logo) {
            $logoValidator = Validator::make(['logo' => $logo], [
                'logo' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], [
                'logo.image' => 'File logo toko harus berupa gambar valid.',
                'logo.mimes' => 'Format logo toko harus JPEG, PNG, JPG, atau WebP.',
                'logo.max' => 'Ukuran file logo maksimal 2MB.',
            ]);

            if ($logoValidator->fails()) {
                throw new ValidationException($logoValidator);
            }

            $oldLogo = $this->get('store_logo');
            $logoPath = $this->storeLogoFile($logo);
            $validated['store_logo'] = $logoPath;

            // Hapus file logo lama dari disk jika ada
            if ($oldLogo) {
                $this->deleteLogoFile($oldLogo);
            }
        }

        // Ambil nilai lama sebelum diubah
        $current = $this->all();
        $oldValues = [];
        $newValues = [];

        DB::transaction(function () use ($validated, $userId, $current, &$oldValues, &$newValues) {
            foreach ($validated as $key => $val) {
                $definition = self::DEFAULTS[$key] ?? null;
                $group = $definition['group'] ?? 'general';
                $type = $definition['type'] ?? $this->detectType($val);

                $stringValue = $this->formatForStorage($val, $type);
                $typedValue = $this->castValue($stringValue, $type);

                $oldVal = $current[$key] ?? null;
                if ($oldVal !== $typedValue) {
                    $oldValues[$key] = $oldVal;
                    $newValues[$key] = $typedValue;
                }

                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'group' => $group,
                        'value' => $stringValue,
                        'type' => $type,
                        'updated_by' => $userId,
                    ]
                );
            }

            // Catat perubahan ke AuditLog jika ada field yang berbeda (US-31)
            if (! empty($newValues)) {
                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'setting.updated',
                    'auditable_type' => Setting::class,
                    'auditable_id' => null,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'created_at' => now(),
                ]);
            }
        });

        $this->clearCache();

        return $this->all();
    }

    /**
     * [BACKEND] Menghapus logo toko dan mereset nilai setting ke null (US-32, US-31).
     *
     * @param  int|null  $userId
     * @return bool
     */
    public function deleteLogo(?int $userId = null): bool
    {
        $currentLogo = $this->get('store_logo');

        if (! $currentLogo) {
            return false;
        }

        $this->deleteLogoFile($currentLogo);

        Setting::updateOrCreate(
            ['key' => 'store_logo'],
            [
                'group' => 'store',
                'value' => null,
                'type' => 'string',
                'updated_by' => $userId,
            ]
        );

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'setting.updated',
            'auditable_type' => Setting::class,
            'auditable_id' => null,
            'old_values' => ['store_logo' => $currentLogo],
            'new_values' => ['store_logo' => null],
            'created_at' => now(),
        ]);

        $this->clearCache();

        return true;
    }

    /**
     * [BACKEND] Menghitung rincian total pesanan kasir berdasarkan urutan aturan bisnis (US-15, BR-03, BR-04, BR-05).
     * Urutan: Subtotal -> Diskon Voucher (BR-09) -> Pajak (BR-04) -> Service Charge -> Pembulatan Tunai (BR-05).
     *
     * @param  int  $subtotal  Subtotal sebelum diskon (Rp)
     * @param  int  $discountAmount  Diskon voucher/promo (Rp)
     * @param  string  $paymentMethod  'cash', 'qris', 'transfer', dll.
     * @return array{
     *     subtotal: int,
     *     discount_amount: int,
     *     discounted_subtotal: int,
     *     tax_percent: float,
     *     tax_amount: int,
     *     service_percent: float,
     *     service_charge: int,
     *     tax_inclusive: bool,
     *     rounding_amount: int,
     *     grand_total: int
     * }
     */
    public function calculateOrderTotals(int $subtotal, int $discountAmount = 0, string $paymentMethod = 'cash'): array
    {
        // 1. Subtotal setelah diskon
        $discountedSubtotal = max(0, $subtotal - max(0, $discountAmount));

        $taxPercent = $this->getTaxPercent();
        $servicePercent = $this->getServicePercent();
        $isTaxInclusive = $this->isTaxInclusive();

        $taxAmount = 0;
        $serviceCharge = 0;

        // 2. Pajak & Service Charge (BR-04)
        if ($isTaxInclusive) {
            // Jika harga menu sudah termasuk pajak & service (tax inclusive)
            // Rumus: Base Net = Discounted Subtotal / (1 + (Tax + Service) / 100)
            $totalRate = $taxPercent + $servicePercent;
            if ($totalRate > 0 && $discountedSubtotal > 0) {
                $baseNet = (int) round($discountedSubtotal / (1 + ($totalRate / 100)));
                $taxAmount = (int) round($baseNet * ($taxPercent / 100));
                $serviceCharge = (int) round($baseNet * ($servicePercent / 100));
            }
            $totalBeforeRounding = $discountedSubtotal;
        } else {
            // Pajak & Service Charge ditambahkan di atas subtotal (tax exclusive)
            if ($taxPercent > 0 && $discountedSubtotal > 0) {
                $taxAmount = (int) round($discountedSubtotal * ($taxPercent / 100));
            }
            if ($servicePercent > 0 && $discountedSubtotal > 0) {
                $serviceCharge = (int) round($discountedSubtotal * ($servicePercent / 100));
            }
            $totalBeforeRounding = $discountedSubtotal + $taxAmount + $serviceCharge;
        }

        // 3. Pembulatan Tunai ke kelipatan terdekat ke bawah (BR-05)
        // Hanya berlaku untuk metode pembayaran tunai ('cash')
        $roundingAmount = 0;
        $cashRounding = $this->getCashRounding();

        if (strtolower($paymentMethod) === 'cash' && $cashRounding > 0 && $totalBeforeRounding > 0) {
            $roundedTotal = (int) (floor($totalBeforeRounding / $cashRounding) * $cashRounding);
            $roundingAmount = $roundedTotal - $totalBeforeRounding; // Bernilai negatif atau 0
            $grandTotal = $roundedTotal;
        } else {
            $grandTotal = $totalBeforeRounding;
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discounted_subtotal' => $discountedSubtotal,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'service_percent' => $servicePercent,
            'service_charge' => $serviceCharge,
            'tax_inclusive' => $isTaxInclusive,
            'rounding_amount' => $roundingAmount,
            'grand_total' => max(0, $grandTotal),
        ];
    }

    // --- TYPED GETTERS (US-32, BR-04, BR-05, BR-16) ---

    public function getStoreName(): string
    {
        return (string) $this->get('store_name', 'Kopi Titik Nol');
    }

    public function getStoreAddress(): ?string
    {
        $val = $this->get('store_address');

        return $val !== null ? (string) $val : null;
    }

    public function getStorePhone(): ?string
    {
        $val = $this->get('store_phone');

        return $val !== null ? (string) $val : null;
    }

    public function getStoreLogo(): ?string
    {
        $val = $this->get('store_logo');

        return $val !== null ? (string) $val : null;
    }

    public function getStoreLogoUrl(): ?string
    {
        $logo = $this->getStoreLogo();

        if (! $logo) {
            return null;
        }

        return Storage::disk('public')->url($logo);
    }

    public function getReceiptFooter(): string
    {
        return (string) $this->get('receipt_footer', 'Terima kasih, sampai jumpa lagi!');
    }

    public function getReceiptPaperSize(): int
    {
        return (int) $this->get('paper_size', 80);
    }

    public function getTaxPercent(): float
    {
        return (float) $this->get('tax_percent', 0);
    }

    public function getServicePercent(): float
    {
        return (float) $this->get('service_percent', 0);
    }

    public function isTaxInclusive(): bool
    {
        return (bool) $this->get('tax_inclusive', false);
    }

    public function getCashRounding(): int
    {
        return (int) $this->get('cash_rounding', 100);
    }

    public function getQrisExpiryMinutes(): int
    {
        return (int) $this->get('qris_expiry_minutes', 15);
    }

    public function getPaymentProvider(): string
    {
        return (string) $this->get('payment_provider', 'midtrans');
    }

    public function isAllowNegativeStock(): bool
    {
        return (bool) $this->get('allow_negative_stock_on_paid', true);
    }

    /**
     * [BACKEND] Mengambil konfigurasi lengkap untuk cetak struk kasir (US-32, BR-04, BR-05).
     *
     * @return array<string, mixed>
     */
    public function getReceiptConfig(): array
    {
        return [
            'store_name' => $this->getStoreName(),
            'store_address' => $this->getStoreAddress(),
            'store_phone' => $this->getStorePhone(),
            'store_logo_url' => $this->getStoreLogoUrl(),
            'receipt_footer' => $this->getReceiptFooter(),
            'paper_size' => $this->getReceiptPaperSize(),
        ];
    }

    /**
     * [BACKEND] Mengosongkan cache setting agar data kembali sinkron dengan database.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // --- HELPER INTERNAL ---

    /**
     * Menyimpan file logo toko ke disk public.
     */
    protected function storeLogoFile(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'webp';
        $filename = 'logo_' . time() . '_' . Str::random(8) . '.' . $extension;

        return $file->storeAs('store', $filename, 'public');
    }

    /**
     * Menghapus file logo toko dari disk.
     */
    protected function deleteLogoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Konversi nilai string dari database sesuai tipe data setting.
     */
    protected function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Format nilai input sebelum disimpan ke kolom string/text database.
     */
    protected function formatForStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => is_array($value) ? json_encode($value) : (string) $value,
            default => (string) $value,
        };
    }

    /**
     * Deteksi tipe data secara otomatis berdasarkan tipe variabel PHP.
     */
    protected function detectType(mixed $value): string
    {
        if (is_int($value)) {
            return 'int';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }
}

