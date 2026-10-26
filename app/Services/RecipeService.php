<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Manajemen Resep / BOM & Kalkulasi HPP (US-09, BR-14, BR-18, BR-19).
 * Menghubungkan menu produk dengan bahan baku & takaran, validasi bahan tunggal per menu,
 * kalkulasi otomatis HPP, margin keuntungan, serta ketersediaan porsi menu berdasarkan stok fisik.
 */
class RecipeService
{
    /**
     * [BACKEND] Mengambil seluruh daftar resep bahan baku untuk produk tertentu (US-09).
     *
     * @param  Product  $product
     * @return Collection<int, Recipe>
     */
    public function getRecipe(Product $product): Collection
    {
        return $product->recipes()->with('ingredient')->get();
    }

    /**
     * [BACKEND] Mengambil rincian kalkulasi resep, HPP, margin laba, dan ketersediaan porsi menu (US-09, BR-14).
     *
     * @param  Product  $product
     * @return array{
     *     product_id: int,
     *     product_name: string,
     *     price: int,
     *     cost_price: int,
     *     gross_profit: int,
     *     margin_percent: float,
     *     available_servings: int|null,
     *     items: array<int, array{
     *         recipe_id: int,
     *         ingredient_id: int,
     *         ingredient_name: string,
     *         ingredient_sku: string|null,
     *         unit: string,
     *         current_stock: float,
     *         amount_required: float,
     *         cost_per_unit: float,
     *         subtotal_cost: float,
     *         max_servings: int
     *     }>
     * }
     */
    public function getRecipeDetails(Product $product): array
    {
        $recipes = $this->getRecipe($product);

        $totalHpp = 0.0;
        $items = [];
        $servingsList = [];

        foreach ($recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            $stock = $ingredient ? (float) $ingredient->stock : 0.0;
            $costPerUnit = $ingredient ? (float) $ingredient->cost_per_unit : 0.0;
            $amountRequired = (float) $recipe->amount_required;

            $subtotalCost = $amountRequired * $costPerUnit;
            $totalHpp += $subtotalCost;

            // Ketersediaan porsi per bahan: floor(stok / takaran) (BR-14)
            $maxServings = $amountRequired > 0
                ? (int) floor(max(0, $stock) / $amountRequired)
                : 0;

            $servingsList[] = $maxServings;

            $items[] = [
                'recipe_id' => $recipe->id,
                'ingredient_id' => $recipe->ingredient_id,
                'ingredient_name' => $ingredient->name ?? '-',
                'ingredient_sku' => $ingredient->sku ?? null,
                'unit' => $ingredient->unit ?? 'gram',
                'current_stock' => $stock,
                'amount_required' => $amountRequired,
                'cost_per_unit' => $costPerUnit,
                'subtotal_cost' => round($subtotalCost, 2),
                'max_servings' => $maxServings,
            ];
        }

        $costPrice = (int) round($totalHpp);
        $price = (int) $product->price;
        $grossProfit = $price - $costPrice;

        // Kalkulasi Margin (%): (Harga Jual - HPP) / Harga Jual * 100
        $marginPercent = $price > 0
            ? round(($grossProfit / $price) * 100, 2)
            : 0.0;

        // BR-14: Ketersediaan menu = min( floor(stok bahan / takaran) ). Menu tanpa resep = null (selalu tersedia)
        $availableServings = count($servingsList) > 0 ? min($servingsList) : null;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $price,
            'cost_price' => $costPrice,
            'gross_profit' => $grossProfit,
            'margin_percent' => $marginPercent,
            'available_servings' => $availableServings,
            'items' => $items,
        ];
    }

    /**
     * [BACKEND] Menyimpan/mengganti seluruh resep bahan baku sebuah menu sekaligus (Batch Sync) (US-09).
     *
     * @param  Product  $product
     * @param  array<int, array{ingredient_id: int, amount_required: float|numeric}>  $items
     * @param  int|null  $userId
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function syncRecipe(Product $product, array $items, ?int $userId = null): array
    {
        $validator = Validator::make(['items' => $items], [
            'items' => 'present|array',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.amount_required' => 'required|numeric|gt:0',
        ], [
            'items.*.ingredient_id.required' => 'Bahan baku wajib dipilih.',
            'items.*.ingredient_id.exists' => 'Bahan baku yang dipilih tidak terdaftar.',
            'items.*.amount_required.required' => 'Takaran bahan baku wajib diisi.',
            'items.*.amount_required.gt' => 'Takaran bahan baku harus lebih besar dari 0 (US-09).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validasi aturan satu bahan hanya sekali per menu (US-09)
        $ingredientIds = array_column($items, 'ingredient_id');
        if (count($ingredientIds) !== count(array_unique($ingredientIds))) {
            throw ValidationException::withMessages([
                'items' => 'Satu bahan baku hanya boleh dimasukkan satu kali per resep menu (US-09).',
            ]);
        }

        return DB::transaction(function () use ($product, $items, $userId) {
            $oldHpp = (int) $product->cost_price;

            // Hapus resep lama
            $product->recipes()->delete();

            // Masukkan resep baru
            foreach ($items as $item) {
                Recipe::create([
                    'product_id' => $product->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'amount_required' => round((float) $item['amount_required'], 3),
                ]);
            }

            // Hitung ulang HPP dan simpan snapshot ke produk (BR-18, BR-19)
            $newHpp = $this->recalculateProductCostPrice($product);

            // Audit log perubahan resep (US-31)
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'recipe.synced',
                'auditable_type' => Product::class,
                'auditable_id' => $product->id,
                'old_values' => ['cost_price' => $oldHpp],
                'new_values' => [
                    'cost_price' => $newHpp,
                    'ingredients_count' => count($items),
                ],
                'created_at' => now(),
            ]);

            return $this->getRecipeDetails($product);
        });
    }

    /**
     * [BACKEND] Menambahkan satu bahan baku ke dalam resep menu (US-09).
     *
     * @param  Product  $product
     * @param  int  $ingredientId
     * @param  float  $amountRequired
     * @param  int|null  $userId
     * @return Recipe
     *
     * @throws ValidationException
     */
    public function addIngredient(Product $product, int $ingredientId, float $amountRequired, ?int $userId = null): Recipe
    {
        $validator = Validator::make([
            'ingredient_id' => $ingredientId,
            'amount_required' => $amountRequired,
        ], [
            'ingredient_id' => 'required|exists:ingredients,id',
            'amount_required' => 'required|numeric|gt:0',
        ], [
            'ingredient_id.exists' => 'Bahan baku tidak ditemukan.',
            'amount_required.gt' => 'Takaran bahan baku harus lebih besar dari 0 (US-09).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Cek apakah bahan sudah ada di resep menu ini
        $exists = $product->recipes()->where('ingredient_id', $ingredientId)->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'ingredient_id' => 'Bahan baku sudah ada dalam resep produk ini (US-09). Gunakan ubah takaran untuk memperbarui.',
            ]);
        }

        return DB::transaction(function () use ($product, $ingredientId, $amountRequired, $userId) {
            $recipe = Recipe::create([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
                'amount_required' => round($amountRequired, 3),
            ]);

            $this->recalculateProductCostPrice($product);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'recipe.item_added',
                'auditable_type' => Recipe::class,
                'auditable_id' => $recipe->id,
                'new_values' => [
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredientId,
                    'amount_required' => $amountRequired,
                ],
                'created_at' => now(),
            ]);

            return $recipe->load('ingredient');
        });
    }

    /**
     * [BACKEND] Memperbarui takaran bahan baku dalam resep (US-09).
     *
     * @param  Recipe  $recipe
     * @param  float  $amountRequired
     * @param  int|null  $userId
     * @return Recipe
     *
     * @throws ValidationException
     */
    public function updateIngredientAmount(Recipe $recipe, float $amountRequired, ?int $userId = null): Recipe
    {
        if ($amountRequired <= 0) {
            throw ValidationException::withMessages([
                'amount_required' => 'Takaran bahan baku harus lebih besar dari 0 (US-09).',
            ]);
        }

        return DB::transaction(function () use ($recipe, $amountRequired, $userId) {
            $oldAmount = (float) $recipe->amount_required;

            $recipe->update([
                'amount_required' => round($amountRequired, 3),
            ]);

            $product = $recipe->product;
            if ($product) {
                $this->recalculateProductCostPrice($product);
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'recipe.item_updated',
                'auditable_type' => Recipe::class,
                'auditable_id' => $recipe->id,
                'old_values' => ['amount_required' => $oldAmount],
                'new_values' => ['amount_required' => $amountRequired],
                'created_at' => now(),
            ]);

            return $recipe->load('ingredient');
        });
    }

    /**
     * [BACKEND] Menghapus bahan baku dari resep menu (US-09).
     *
     * @param  Recipe  $recipe
     * @param  int|null  $userId
     * @return bool
     */
    public function removeIngredient(Recipe $recipe, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($recipe, $userId) {
            $product = $recipe->product;
            $recipeId = $recipe->id;
            $ingredientId = $recipe->ingredient_id;

            $recipe->delete();

            if ($product) {
                $this->recalculateProductCostPrice($product);
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'recipe.item_removed',
                'auditable_type' => Recipe::class,
                'auditable_id' => $recipeId,
                'old_values' => [
                    'ingredient_id' => $ingredientId,
                ],
                'created_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * [BACKEND] Menghitung ketersediaan porsi menu berdasarkan stok bahan baku fisik (BR-14).
     * Rumus: min( floor(stok bahan / takaran) ) atas seluruh bahan di resepnya.
     * Menu tanpa resep mengembalikan null (selalu tersedia).
     *
     * @param  Product  $product
     * @return int|null Jumlah porsi yang dapat dibuat, atau null jika menu tanpa resep
     */
    public function calculateAvailability(Product $product): ?int
    {
        $recipes = $product->recipes()->with('ingredient')->get();

        if ($recipes->isEmpty()) {
            return null; // Menu tanpa resep dianggap selalu tersedia (BR-14)
        }

        $servings = [];
        foreach ($recipes as $recipe) {
            $stock = $recipe->ingredient ? (float) $recipe->ingredient->stock : 0.0;
            $amountRequired = (float) $recipe->amount_required;

            if ($amountRequired <= 0) {
                continue;
            }

            $servings[] = (int) floor(max(0, $stock) / $amountRequired);
        }

        return count($servings) > 0 ? min($servings) : null;
    }

    /**
     * [BACKEND] Menghitung ulang HPP menu berdasarkan seluruh bahan resep saat ini dan menyimpan snapshot ke Product (BR-18, BR-19).
     *
     * @param  Product  $product
     * @return int Nilai HPP baru (Rp)
     */
    public function recalculateProductCostPrice(Product $product): int
    {
        $recipes = $product->recipes()->with('ingredient')->get();
        $totalHpp = 0.0;

        foreach ($recipes as $recipe) {
            if ($recipe->ingredient) {
                $totalHpp += (float) $recipe->amount_required * (float) $recipe->ingredient->cost_per_unit;
            }
        }

        $costPrice = (int) round($totalHpp);
        $product->update(['cost_price' => $costPrice]);

        return $costPrice;
    }
}

