<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\IngredientStockStatus;
use App\Models\ProductAvailability;
use Illuminate\Database\Eloquent\Collection;

/**
 * [BACKEND] Service Notifikasi Stok Menipis & Integrasi SQL Views (US-10, US-14, BR-14, BR-20).
 * Mengintegrasikan SQL View v_ingredient_stock_status dan v_product_availability,
 * mendeteksi bahan kritis (menipis/habis), menyediakan counter badge navbar (S-22),
 * serta mekanisme notifikasi anti-spam (satu notifikasi per bahan sampai stok pulih).
 */
class LowStockAlertService
{
    /**
     * [BACKEND] Mengambil seluruh bahan baku kritis (habis / out dan menipis / low) (US-10, BR-20).
     * Urutan: Bahan habis ('out') terlebih dahulu, kemudian menipis ('low'), diurutkan stok terkecil.
     *
     * @return Collection<int, IngredientStockStatus>
     */
    public function getCriticalIngredients(): Collection
    {
        return IngredientStockStatus::query()
            ->whereIn('stock_status', ['out', 'low'])
            ->orderByRaw("CASE WHEN stock_status = 'out' THEN 1 ELSE 2 END")
            ->orderBy('stock', 'asc')
            ->get();
    }

    /**
     * [BACKEND] Mengambil daftar bahan baku yang sudah habis (stock <= 0) (BR-20).
     *
     * @return Collection<int, IngredientStockStatus>
     */
    public function getOutOfStockIngredients(): Collection
    {
        return IngredientStockStatus::query()
            ->where('stock_status', 'out')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * [BACKEND] Mengambil daftar bahan baku yang menipis (0 < stock <= min_stock) (BR-20).
     *
     * @return Collection<int, IngredientStockStatus>
     */
    public function getLowStockIngredients(): Collection
    {
        return IngredientStockStatus::query()
            ->where('stock_status', 'low')
            ->orderBy('stock', 'asc')
            ->get();
    }

    /**
     * [BACKEND] Mengambil daftar bahan baku dengan status aman (stock > min_stock) (BR-20).
     *
     * @return Collection<int, IngredientStockStatus>
     */
    public function getOkIngredients(): Collection
    {
        return IngredientStockStatus::query()
            ->where('stock_status', 'ok')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * [BACKEND] Menghitung total bahan baku kritis untuk badge merah di Navbar Topbar (US-10, S-22).
     *
     * @return int
     */
    public function getCriticalCount(): int
    {
        return IngredientStockStatus::query()
            ->whereIn('stock_status', ['out', 'low'])
            ->count();
    }

    /**
     * [BACKEND] Mengambil ringkasan metrik inventaris bahan baku (US-10, S-02, S-05).
     *
     * @return array{
     *     total_active: int,
     *     ok_count: int,
     *     low_count: int,
     *     out_count: int,
     *     critical_count: int
     * }
     */
    public function getStockSummary(): array
    {
        $all = IngredientStockStatus::all();

        $okCount = $all->where('stock_status', 'ok')->count();
        $lowCount = $all->where('stock_status', 'low')->count();
        $outCount = $all->where('stock_status', 'out')->count();

        return [
            'total_active' => $all->count(),
            'ok_count' => $okCount,
            'low_count' => $lowCount,
            'out_count' => $outCount,
            'critical_count' => $lowCount + $outCount,
        ];
    }

    /**
     * [BACKEND] Mengambil bahan baku kritis yang belum pernah dinotifikasi ke owner (anti-spam) (US-10).
     *
     * @return Collection<int, Ingredient>
     */
    public function getUnnotifiedCriticalIngredients(): Collection
    {
        return Ingredient::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->whereNull('low_stock_notified_at')
            ->orderBy('stock', 'asc')
            ->get();
    }

    /**
     * [BACKEND] Memeriksa dan menandai notifikasi stok menipis/habis (US-10, BR-20).
     * Menerapkan aturan anti-spam: menandai low_stock_notified_at agar tidak terkirim berulang
     * sampai stok bahan tersebut dipulihkan di atas batas minimum.
     *
     * @return array<int, array{
     *     ingredient_id: int,
     *     name: string,
     *     unit: string,
     *     stock: float,
     *     min_stock: float,
     *     status: string,
     *     message: string
     * }>
     */
    public function checkAndDispatchAlerts(): array
    {
        $unnotified = $this->getUnnotifiedCriticalIngredients();
        $alerts = [];
        $now = now();

        foreach ($unnotified as $ingredient) {
            $stock = (float) $ingredient->stock;
            $minStock = (float) $ingredient->min_stock;
            $unit = $ingredient->unit;
            $name = $ingredient->name;

            if ($stock <= 0) {
                $status = 'out';
                $message = "🚨 Peringatan: Stok Habis — {$name} telah habis ({$stock} {$unit}). Segera lakukan pembelian atau stok masuk.";
            } else {
                $status = 'low';
                $message = "⚠️ Peringatan Stok Menipis — {$name} tersisa {$stock} {$unit}. Minimum stok: {$minStock} {$unit}.";
            }

            // Tandai sudah dinotifikasi untuk anti-spam (US-10)
            $ingredient->update([
                'low_stock_notified_at' => $now,
            ]);

            // Catat ke AuditLog
            AuditLog::create([
                'action' => 'ingredient.low_stock_alert',
                'auditable_type' => Ingredient::class,
                'auditable_id' => $ingredient->id,
                'new_values' => [
                    'status' => $status,
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'message' => $message,
                ],
                'created_at' => $now,
            ]);

            $alerts[] = [
                'ingredient_id' => $ingredient->id,
                'name' => $name,
                'unit' => $unit,
                'stock' => $stock,
                'min_stock' => $minStock,
                'status' => $status,
                'message' => $message,
            ];
        }

        return $alerts;
    }

    /**
     * [BACKEND] Mengambil data ketersediaan menu dari SQL View v_product_availability (US-14, BR-14).
     *
     * @param  bool  $onlyUnavailable  Hanya produk yang tidak tersedia (habis atau max_portions = 0)
     * @return Collection<int, ProductAvailability>
     */
    public function getProductAvailabilities(bool $onlyUnavailable = false): Collection
    {
        $query = ProductAvailability::query();

        if ($onlyUnavailable) {
            $query->where(function ($q) {
                $q->where('is_sold_out', true)
                    ->orWhere('max_portions', '<=', 0);
            });
        }

        return $query->get();
    }
}

