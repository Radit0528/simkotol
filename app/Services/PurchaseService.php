<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMutation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Pencatatan Pembelian Bahan Baku & Moving Average Cost (US-07, BR-13, BR-19, BR-20).
 * Menangani transaksi stok masuk dari pembelian, penambahan kuantitas bahan fisik,
 * kalkulasi harga rata-rata bergerak (moving average), pembuatan mutasi stok, serta penomoran PO otomatis.
 */
class PurchaseService
{
    protected IngredientService $ingredientService;

    public function __construct(?IngredientService $ingredientService = null)
    {
        $this->ingredientService = $ingredientService ?? new IngredientService;
    }

    /**
     * [BACKEND] Mengambil riwayat pembelian bahan baku dengan filter tanggal, supplier, dan user (US-07).
     *
     * @param  array<string, mixed>  $filters
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<Purchase>|Collection<int, Purchase>
     */
    public function getPurchases(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Purchase::query()
            ->with(['supplier', 'user', 'items.ingredient']);

        // Filter supplier
        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // Filter user pembuat
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter rentang tanggal pembelian
        if (! empty($filters['start_date'])) {
            $query->whereDate('purchased_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('purchased_at', '<=', $filters['end_date']);
        }

        // Pencarian nomor PO atau catatan
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', $search)
                    ->orWhere('notes', 'like', $search);
            });
        }

        $query->orderByDesc('purchased_at')
            ->orderByDesc('id');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Mengambil detail pembelian berdasarkan ID (US-07).
     *
     * @param  int|string  $id
     * @return Purchase
     *
     * @throws ValidationException
     */
    public function getPurchase(int|string $id): Purchase
    {
        $purchase = Purchase::with(['supplier', 'user', 'items.ingredient', 'stockMutations'])
            ->find($id);

        if (! $purchase) {
            throw ValidationException::withMessages([
                'purchase' => 'Data pembelian tidak ditemukan.',
            ]);
        }

        return $purchase;
    }

    /**
     * [BACKEND] Mencatat pembelian bahan baku baru (Stok Masuk), membuat mutasi stok,
     * serta memperbarui cost_per_unit menggunakan metode Moving Average (US-07, BR-13, BR-19).
     *
     * @param  array<string, mixed>  $data
     * @param  int  $userId
     * @return Purchase
     *
     * @throws ValidationException
     */
    public function createPurchase(array $data, int $userId): Purchase
    {
        // 1. Validasi Input Pembelian
        $validator = Validator::make($data, [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchased_at' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ], [
            'purchased_at.required' => 'Tanggal pembelian wajib diisi.',
            'purchased_at.before_or_equal' => 'Tanggal pembelian tidak boleh di masa depan (US-07).',
            'items.required' => 'Daftar item pembelian minimal memiliki satu bahan baku.',
            'items.*.ingredient_id.required' => 'Bahan baku harus dipilih.',
            'items.*.ingredient_id.exists' => 'Bahan baku tidak valid atau tidak terdaftar.',
            'items.*.quantity.gt' => 'Jumlah pembelian harus lebih besar dari 0.',
            'items.*.unit_cost.min' => 'Harga per satuan tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $purchasedAt = $validated['purchased_at'];

        return DB::transaction(function () use ($validated, $userId, $purchasedAt) {
            // 2. Generate Nomor PO Harian Otomatis dengan DB Row Lock
            $purchaseNumber = $this->generatePurchaseNumber($purchasedAt);

            // 3. Buat Header Pembelian
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'user_id' => $userId,
                'purchased_at' => $purchasedAt,
                'total_cost' => 0,
                'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            ]);

            $totalPurchaseCost = 0;

            // 4. Proses Item Pembelian, Mutasi Stok, dan Moving Average
            foreach ($validated['items'] as $item) {
                $ingredientId = $item['ingredient_id'];
                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $subtotal = (int) round($quantity * $unitCost);

                $totalPurchaseCost += $subtotal;

                // Kunci baris bahan baku untuk mencegah race condition mutasi stok paralel
                $ingredient = Ingredient::lockForUpdate()->find($ingredientId);

                $stockBefore = (float) $ingredient->stock;
                $costBefore = (float) $ingredient->cost_per_unit;

                // Hitung Stok Akhir
                $stockAfter = $stockBefore + $quantity;

                // Hitung Moving Average Cost (BR-19)
                $newCostPerUnit = $this->calculateMovingAverage($stockBefore, $costBefore, $quantity, $unitCost);

                // Buat Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'ingredient_id' => $ingredientId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                // Buat Mutasi Stok Bertipe 'in' (US-07, BR-13)
                StockMutation::create([
                    'ingredient_id' => $ingredientId,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $unitCost,
                    'purchase_id' => $purchase->id,
                    'user_id' => $userId,
                    'notes' => 'Pembelian #' . $purchaseNumber,
                    'created_at' => now(),
                ]);

                // Perbarui Stok Fisik dan Cost Per Unit Bahan Baku
                $ingredientUpdateData = [
                    'stock' => $stockAfter,
                    'cost_per_unit' => $newCostPerUnit,
                ];

                // Jika stok pulih di atas minimum, reset penanda notifikasi stok menipis (BR-20)
                if ($stockAfter > (float) $ingredient->min_stock && $ingredient->low_stock_notified_at !== null) {
                    $ingredientUpdateData['low_stock_notified_at'] = null;
                }

                $ingredient->update($ingredientUpdateData);

                // Jika biaya per unit bahan berubah, hitung ulang HPP menu yang menggunakan bahan ini (BR-19)
                if (abs($newCostPerUnit - $costBefore) > 0.0001) {
                    $this->ingredientService->recalculateRelatedProductsHpp($ingredient);
                }
            }

            // 5. Perbarui Total Biaya Pembelian
            $purchase->update([
                'total_cost' => $totalPurchaseCost,
            ]);

            // 6. Catat ke Audit Log (US-31)
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'purchase.created',
                'auditable_type' => Purchase::class,
                'auditable_id' => $purchase->id,
                'old_values' => null,
                'new_values' => [
                    'purchase_number' => $purchase->purchase_number,
                    'supplier_id' => $purchase->supplier_id,
                    'total_cost' => $totalPurchaseCost,
                    'items_count' => count($validated['items']),
                ],
                'created_at' => now(),
            ]);

            return $purchase->load(['items.ingredient', 'supplier', 'user']);
        });
    }

    /**
     * [BACKEND] Menghitung harga pokok rata-rata bergerak (Moving Average Cost) (BR-19).
     *
     * Rumus:
     * Jika stok sebelum <= 0 (kosong atau minus akibat penjualan non-tunai BR-16):
     *   New Cost = Harga Beli Baru
     * Jika stok sebelum > 0:
     *   New Cost = ((Stok Sebelum * Harga Sebelum) + (Qty Beli * Harga Beli)) / (Stok Sebelum + Qty Beli)
     *
     * @param  float  $stockBefore  Stok bahan fisik sebelum pembelian
     * @param  float  $costBefore  Harga per satuan sebelum pembelian
     * @param  float  $quantityNew  Jumlah bahan yang dibeli
     * @param  float  $costNew  Harga beli per satuan pada transaksi ini
     * @return float Nilai moving average dibulatkan ke 4 angka desimal
     */
    public function calculateMovingAverage(float $stockBefore, float $costBefore, float $quantityNew, float $costNew): float
    {
        if ($quantityNew <= 0) {
            return $costBefore;
        }

        // Jika stok lama kosong atau minus (karena penjualan non-tunai BR-16),
        // harga pokok rata-rata baru murni mengikuti harga beli terbaru.
        if ($stockBefore <= 0) {
            return round($costNew, 4);
        }

        $totalValueBefore = $stockBefore * $costBefore;
        $incomingValue = $quantityNew * $costNew;
        $totalStockAfter = $stockBefore + $quantityNew;

        if ($totalStockAfter <= 0) {
            return round($costNew, 4);
        }

        $movingAverage = ($totalValueBefore + $incomingValue) / $totalStockAfter;

        return round($movingAverage, 4);
    }

    /**
     * [BACKEND] Menghasilkan nomor PO unik harian dengan format PO-YYMMDD-XXXX secara atomik.
     * Menggunakan tabel number_sequences dengan penguncian baris (lockForUpdate).
     *
     * @param  string|null  $date  Format Y-m-d (default: hari ini)
     * @return string Contoh: PO-261026-0001
     */
    public function generatePurchaseNumber(?string $date = null): string
    {
        $targetDate = $date ?: date('Y-m-d');
        $dateCode = date('ymd', strtotime($targetDate));

        $sequence = DB::table('number_sequences')
            ->where('name', 'purchase')
            ->where('seq_date', $targetDate)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            DB::table('number_sequences')->insert([
                'name' => 'purchase',
                'seq_date' => $targetDate,
                'last_number' => 1,
            ]);
            $nextNumber = 1;
        } else {
            $nextNumber = $sequence->last_number + 1;
            DB::table('number_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $nextNumber]);
        }

        return sprintf('PO-%s-%04d', $dateCode, $nextNumber);
    }
}

