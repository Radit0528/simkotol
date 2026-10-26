<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\StockMutation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Manajemen Bahan Baku (US-06, US-07, BR-12 s/d BR-20, BR-40).
 * Menangani operasi CRUD bahan baku, validasi SKU unik, pembatasan perubahan satuan,
 * inisialisasi stok awal ke buku besar mutasi, serta pembaruan HPP produk terkait (BR-19).
 */
class IngredientService
{
    /**
     * [BACKEND] Mengambil daftar bahan baku dengan filter, pencarian, dan pagination (US-06, US-10).
     *
     * @param  array<string, mixed>  $filters
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<Ingredient>|Collection<int, Ingredient>
     */
    public function getIngredients(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Ingredient::query();

        // Scope soft delete
        if (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        // Filter status aktif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Filter satuan dasar (gram/ml/pcs)
        if (! empty($filters['unit'])) {
            $query->where('unit', $filters['unit']);
        }

        // Filter status stok (ok / low / out)
        if (! empty($filters['stock_status'])) {
            match ($filters['stock_status']) {
                'out' => $query->where('stock', '<=', 0),
                'low' => $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock'),
                'ok' => $query->whereColumn('stock', '>', 'min_stock'),
                default => null,
            };
        }

        // Pencarian nama atau SKU
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search);
            });
        }

        // Sertakan jumlah resep terhubung jika diminta
        if (! empty($filters['with_recipes_count'])) {
            $query->withCount('recipes');
        }

        // Pengurutan default
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Membuat bahan baku baru beserta pencatatan mutasi stok awal jika ada (US-06, US-07).
     *
     * @param  array<string, mixed>  $data
     * @param  int|null  $userId
     * @return Ingredient
     *
     * @throws ValidationException
     */
    public function createIngredient(array $data, ?int $userId = null): Ingredient
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:120|unique:ingredients,name',
            'sku' => 'nullable|string|max:40|unique:ingredients,sku',
            'unit' => ['required', Rule::in(['gram', 'ml', 'pcs'])],
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'name.unique' => 'Nama bahan baku sudah terdaftar.',
            'sku.unique' => 'SKU bahan baku sudah digunakan.',
            'unit.required' => 'Satuan dasar bahan baku wajib dipilih.',
            'unit.in' => 'Satuan harus berupa gram, ml, atau pcs.',
            'stock.min' => 'Stok awal tidak boleh bernilai negatif.',
            'min_stock.min' => 'Stok minimum tidak boleh bernilai negatif.',
            'cost_per_unit.min' => 'Harga per satuan tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated, $userId) {
            $sku = ! empty($validated['sku'])
                ? strtoupper(trim($validated['sku']))
                : $this->generateUniqueSku();

            $initialStock = isset($validated['stock']) ? (float) $validated['stock'] : 0.0;
            $minStock = isset($validated['min_stock']) ? (float) $validated['min_stock'] : 0.0;
            $costPerUnit = isset($validated['cost_per_unit']) ? (float) $validated['cost_per_unit'] : 0.0;
            $isActive = $validated['is_active'] ?? true;

            $ingredient = Ingredient::create([
                'name' => trim($validated['name']),
                'sku' => $sku,
                'unit' => $validated['unit'],
                'stock' => $initialStock,
                'min_stock' => $minStock,
                'cost_per_unit' => $costPerUnit,
                'is_active' => $isActive,
            ]);

            // Jika ada stok awal > 0, catat mutasi stok masuk awal (US-07, BR-13)
            if ($initialStock > 0) {
                StockMutation::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'in',
                    'quantity' => $initialStock,
                    'stock_before' => 0,
                    'stock_after' => $initialStock,
                    'unit_cost' => $costPerUnit,
                    'user_id' => $userId,
                    'notes' => 'Stok awal bahan baku',
                    'created_at' => now(),
                ]);
            }

            // Catat audit log (US-31)
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'ingredient.created',
                'auditable_type' => Ingredient::class,
                'auditable_id' => $ingredient->id,
                'old_values' => null,
                'new_values' => [
                    'name' => $ingredient->name,
                    'sku' => $ingredient->sku,
                    'unit' => $ingredient->unit,
                    'stock' => $ingredient->stock,
                    'min_stock' => $ingredient->min_stock,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                ],
                'created_at' => now(),
            ]);

            return $ingredient;
        });
    }

    /**
     * [BACKEND] Memperbarui data bahan baku dengan aturan ketat pergantian satuan (US-06, BR-19).
     *
     * @param  Ingredient  $ingredient
     * @param  array<string, mixed>  $data
     * @param  int|null  $userId
     * @return Ingredient
     *
     * @throws ValidationException
     */
    public function updateIngredient(Ingredient $ingredient, array $data, ?int $userId = null): Ingredient
    {
        $validator = Validator::make($data, [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('ingredients', 'name')->ignore($ingredient->id),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('ingredients', 'sku')->ignore($ingredient->id),
            ],
            'unit' => ['sometimes', 'required', Rule::in(['gram', 'ml', 'pcs'])],
            'min_stock' => 'sometimes|required|numeric|min:0',
            'cost_per_unit' => 'sometimes|required|numeric|min:0',
            'is_active' => 'sometimes|required|boolean',
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'name.unique' => 'Nama bahan baku sudah terdaftar.',
            'sku.unique' => 'SKU bahan baku sudah digunakan.',
            'unit.in' => 'Satuan harus berupa gram, ml, atau pcs.',
            'min_stock.min' => 'Stok minimum tidak boleh bernilai negatif.',
            'cost_per_unit.min' => 'Harga per satuan tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // ATURAN KRUSIAL US-06: Satuan tidak bisa diubah setelah ada resep/mutasi
        if (isset($validated['unit']) && $validated['unit'] !== $ingredient->unit) {
            if (! $ingredient->canChangeUnit()) {
                throw ValidationException::withMessages([
                    'unit' => 'Satuan bahan baku tidak dapat diubah karena sudah memiliki riwayat resep atau mutasi stok (US-06).',
                ]);
            }
        }

        return DB::transaction(function () use ($ingredient, $validated, $userId) {
            $oldValues = $ingredient->only(['name', 'sku', 'unit', 'min_stock', 'cost_per_unit', 'is_active']);
            $costChanged = false;
            $oldCost = (float) $ingredient->cost_per_unit;

            if (isset($validated['sku'])) {
                $validated['sku'] = strtoupper(trim($validated['sku']));
            }

            if (isset($validated['name'])) {
                $validated['name'] = trim($validated['name']);
            }

            if (isset($validated['cost_per_unit'])) {
                $newCost = (float) $validated['cost_per_unit'];
                if ($newCost !== $oldCost) {
                    $costChanged = true;
                }
            }

            $ingredient->update($validated);

            // Audit log jika harga bahan berubah (US-31)
            if ($costChanged) {
                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'ingredient.cost_changed',
                    'auditable_type' => Ingredient::class,
                    'auditable_id' => $ingredient->id,
                    'old_values' => ['cost_per_unit' => $oldCost],
                    'new_values' => ['cost_per_unit' => (float) $ingredient->cost_per_unit],
                    'created_at' => now(),
                ]);

                // Hitung ulang HPP seluruh produk yang menggunakan bahan ini (BR-19)
                $this->recalculateRelatedProductsHpp($ingredient);
            }

            // Audit log pembaruan umum
            $newValues = $ingredient->only(['name', 'sku', 'unit', 'min_stock', 'cost_per_unit', 'is_active']);
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'ingredient.updated',
                'auditable_type' => Ingredient::class,
                'auditable_id' => $ingredient->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => now(),
            ]);

            return $ingredient;
        });
    }

    /**
     * [BACKEND] Menghapus bahan baku dengan proteksi relasi resep & mutasi (US-06, BR-40).
     *
     * @param  Ingredient  $ingredient
     * @param  bool  $force
     * @return bool
     *
     * @throws ValidationException
     */
    public function deleteIngredient(Ingredient $ingredient, bool $force = false): bool
    {
        if ($force) {
            // Tolak hard delete jika pernah ada mutasi, pembelian, atau resep (BR-40)
            if ($ingredient->hasTransactions()) {
                throw ValidationException::withMessages([
                    'ingredient' => 'Bahan baku tidak dapat dihapus permanen karena memiliki relasi transaksi/mutasi/resep (BR-40).',
                ]);
            }

            $id = $ingredient->id;
            $ingredient->forceDelete();

            AuditLog::create([
                'action' => 'ingredient.force_deleted',
                'auditable_type' => Ingredient::class,
                'auditable_id' => $id,
                'created_at' => now(),
            ]);

            return true;
        }

        // Soft delete: Tolak jika bahan masih dipakai di resep menu yang ada
        if ($ingredient->hasRecipes()) {
            throw ValidationException::withMessages([
                'ingredient' => 'Bahan baku tidak dapat dihapus karena masih digunakan dalam resep menu. Hapus bahan dari resep menu terlebih dahulu.',
            ]);
        }

        $ingredient->delete();

        AuditLog::create([
            'action' => 'ingredient.deleted',
            'auditable_type' => Ingredient::class,
            'auditable_id' => $ingredient->id,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * [BACKEND] Memulihkan bahan baku yang telah di-soft delete (US-06).
     *
     * @param  int|string  $id
     * @return Ingredient
     *
     * @throws ValidationException
     */
    public function restoreIngredient(int|string $id): Ingredient
    {
        $ingredient = Ingredient::onlyTrashed()->find($id);

        if (! $ingredient) {
            throw ValidationException::withMessages([
                'ingredient' => 'Bahan baku yang dimaksud tidak ditemukan dalam arsip terhapus.',
            ]);
        }

        $ingredient->restore();

        AuditLog::create([
            'action' => 'ingredient.restored',
            'auditable_type' => Ingredient::class,
            'auditable_id' => $ingredient->id,
            'created_at' => now(),
        ]);

        return $ingredient;
    }

    /**
     * [BACKEND] Mengubah status aktif/nonaktif bahan baku (US-06).
     *
     * @param  Ingredient  $ingredient
     * @return Ingredient
     */
    public function toggleActive(Ingredient $ingredient): Ingredient
    {
        $ingredient->update([
            'is_active' => ! $ingredient->is_active,
        ]);

        return $ingredient;
    }

    /**
     * [BACKEND] Menghitung ulang HPP pada seluruh produk yang resepnya menggunakan bahan ini (BR-19).
     *
     * @param  Ingredient  $ingredient
     * @return void
     */
    public function recalculateRelatedProductsHpp(Ingredient $ingredient): void
    {
        $recipes = Recipe::where('ingredient_id', $ingredient->id)
            ->with('product.recipes.ingredient')
            ->get();

        $processedProductIds = [];

        foreach ($recipes as $recipe) {
            $product = $recipe->product;
            if ($product && ! in_array($product->id, $processedProductIds, true)) {
                $processedProductIds[] = $product->id;

                $totalHpp = 0;
                foreach ($product->recipes as $r) {
                    if ($r->ingredient) {
                        $totalHpp += (float) $r->amount_required * (float) $r->ingredient->cost_per_unit;
                    }
                }

                $product->update([
                    'cost_price' => (int) round($totalHpp),
                ]);
            }
        }
    }

    /**
     * Menghasilkan SKU unik bahan baku secara otomatis jika tidak disediakan (misal: ING-A8B9C2).
     */
    protected function generateUniqueSku(): string
    {
        do {
            $sku = 'ING-' . strtoupper(Str::random(6));
        } while (Ingredient::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}

