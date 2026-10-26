<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Penyesuaian Stok Manual & Pengeluaran Bahan (US-08, US-31, BR-13, BR-20).
 * Menangani pencatatan pengeluaran manual bahan baku (spill, expired, out, adjustment),
 * validasi catatan/alasan wajib, proteksi batas stok negatif non-owner, dan pencatatan audit log.
 */
class StockAdjustmentService
{
    /**
     * [BACKEND] Melakukan penyesuaian stok manual (spill, expired, out, adjustment) (US-08).
     *
     * @param  array<string, mixed>  $data
     * @param  int  $userId
     * @return StockMutation
     *
     * @throws ValidationException
     */
    public function adjustStock(array $data, int $userId): StockMutation
    {
        // 1. Validasi Input Dasar
        $validator = Validator::make($data, [
            'ingredient_id' => 'required|exists:ingredients,id',
            'type' => ['required', Rule::in(['spill', 'expired', 'adjustment', 'out'])],
            'quantity' => 'required|numeric',
            'notes' => 'required|string|min:3|max:255',
        ], [
            'ingredient_id.required' => 'Bahan baku harus dipilih.',
            'ingredient_id.exists' => 'Bahan baku tidak ditemukan.',
            'type.required' => 'Jenis penyesuaian stok wajib dipilih.',
            'type.in' => 'Jenis penyesuaian harus berupa spill, expired, adjustment, atau out.',
            'quantity.required' => 'Jumlah penyesuaian stok wajib diisi.',
            'quantity.numeric' => 'Jumlah penyesuaian harus berupa angka numerik.',
            'notes.required' => 'Alasan/catatan penyesuaian stok wajib diisi (US-08).',
            'notes.min' => 'Alasan/catatan minimal 3 karakter.',
            'notes.max' => 'Alasan/catatan maksimal 255 karakter.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $type = $validated['type'];
        $rawQuantity = (float) $validated['quantity'];
        $notes = trim($validated['notes']);

        // 2. Validasi Kuantitas per Jenis
        if (in_array($type, ['spill', 'expired', 'out'], true)) {
            if ($rawQuantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Jumlah bahan untuk jenis '{$type}' harus lebih besar dari 0.",
                ]);
            }
            $signedQuantity = -abs($rawQuantity); // Selalu bernilai negatif
        } else {
            // Type 'adjustment'
            if (abs($rawQuantity) < 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'Nilai penyesuaian stok tidak boleh nol.',
                ]);
            }
            $signedQuantity = $rawQuantity; // Bisa bertanda positif atau negatif
        }

        return DB::transaction(function () use ($validated, $type, $signedQuantity, $notes, $userId) {
            // 3. Kunci Baris Bahan Baku dengan SELECT ... FOR UPDATE
            $ingredient = Ingredient::lockForUpdate()->find($validated['ingredient_id']);

            $stockBefore = (float) $ingredient->stock;
            $stockAfter = $stockBefore + $signedQuantity;

            // 4. Validasi Aturan Stok Negatif (US-08)
            // "Stok akhir tidak boleh negatif kecuali adjustment disetujui owner"
            if ($stockAfter < 0) {
                $user = User::find($userId);
                $isOwner = $user && $user->role === 'owner';

                if ($type !== 'adjustment') {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok akhir tidak boleh bernilai negatif untuk jenis pengeluaran '{$type}' (US-08). Stok tersedia: {$stockBefore} {$ingredient->unit}.",
                    ]);
                }

                if (! $isOwner) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok akhir tidak boleh bernilai negatif kecuali penyesuaian dilakukan/disetujui oleh Owner (US-08).',
                    ]);
                }
            }

            // 5. Buat Catatan di Buku Besar Mutasi Stok (BR-13)
            $mutation = StockMutation::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'quantity' => $signedQuantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $ingredient->cost_per_unit,
                'user_id' => $userId,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // 6. Perbarui Stok Fisik Bahan Baku
            $updateData = ['stock' => $stockAfter];

            // Reset penanda notifikasi jika stok pulih di atas minimum (BR-20)
            if ($stockAfter > (float) $ingredient->min_stock && $ingredient->low_stock_notified_at !== null) {
                $updateData['low_stock_notified_at'] = null;
            }

            $ingredient->update($updateData);

            // 7. Catat ke Audit Log (US-31)
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'stock.' . $type,
                'auditable_type' => Ingredient::class,
                'auditable_id' => $ingredient->id,
                'old_values' => [
                    'stock' => $stockBefore,
                ],
                'new_values' => [
                    'stock' => $stockAfter,
                    'mutation_id' => $mutation->id,
                    'type' => $type,
                    'quantity' => $signedQuantity,
                    'notes' => $notes,
                ],
                'created_at' => now(),
            ]);

            return $mutation->load(['ingredient', 'user']);
        });
    }

    /**
     * [BACKEND] Menyesuaikan stok bahan baku berdasarkan hasil hitung fisik (Stock Opname) (US-08).
     * Menghitung otomatis selisih (+/-) antara stok fisik nyata dengan stok sistem saat ini.
     *
     * @param  int  $ingredientId
     * @param  float  $physicalCount
     * @param  string  $notes
     * @param  int  $userId
     * @return StockMutation
     *
     * @throws ValidationException
     */
    public function adjustToPhysicalCount(int $ingredientId, float $physicalCount, string $notes, int $userId): StockMutation
    {
        $ingredient = Ingredient::find($ingredientId);

        if (! $ingredient) {
            throw ValidationException::withMessages([
                'ingredient_id' => 'Bahan baku tidak ditemukan.',
            ]);
        }

        $stockBefore = (float) $ingredient->stock;
        $difference = $physicalCount - $stockBefore;

        if (abs($difference) < 0.0001) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok fisik sama dengan stok sistem, tidak diperlukan penyesuaian.',
            ]);
        }

        $fullNotes = trim($notes) . " (Opname: fisik={$physicalCount}, sistem={$stockBefore})";

        return $this->adjustStock([
            'ingredient_id' => $ingredientId,
            'type' => 'adjustment',
            'quantity' => $difference,
            'notes' => $fullNotes,
        ], $userId);
    }

    /**
     * [BACKEND] Mengambil riwayat mutasi stok bahan baku dengan filter lengkap (US-08, US-28, S-07).
     *
     * @param  array<string, mixed>  $filters
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<StockMutation>|Collection<int, StockMutation>
     */
    public function getMutations(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = StockMutation::query()
            ->with(['ingredient', 'user', 'purchase']);

        if (! empty($filters['ingredient_id'])) {
            $query->where('ingredient_id', $filters['ingredient_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', $search)
                    ->orWhereHas('ingredient', function ($iq) use ($search) {
                        $iq->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    });
            });
        }

        $query->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Menghitung total nilai waste (spill + expired) dalam periode tertentu (US-29).
     * Digunakan untuk kalkulasi laporan Laba Kotor (Pendapatan bersih - HPP - waste).
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array{
     *     spill_total_cost: int,
     *     expired_total_cost: int,
     *     waste_total_cost: int,
     *     items_count: int
     * }
     */
    public function getWasteSummary(string $startDate, string $endDate): array
    {
        $mutations = StockMutation::whereIn('type', ['spill', 'expired'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

        $spillCost = 0;
        $expiredCost = 0;

        foreach ($mutations as $mutation) {
            $cost = (int) round(abs((float) $mutation->quantity) * (float) $mutation->unit_cost);
            if ($mutation->type === 'spill') {
                $spillCost += $cost;
            } elseif ($mutation->type === 'expired') {
                $expiredCost += $cost;
            }
        }

        return [
            'spill_total_cost' => $spillCost,
            'expired_total_cost' => $expiredCost,
            'waste_total_cost' => $spillCost + $expiredCost,
            'items_count' => $mutations->count(),
        ];
    }
}

