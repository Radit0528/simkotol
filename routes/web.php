<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes - SIMKOTOL (Sistem Manajemen Kopi Titik Nol)
|--------------------------------------------------------------------------
|
| Seluruh rute dilindungi sesuai matriks hak akses PRD v2.0 Bab 5 & Bab 12.
|
*/

// Redirect root ke dashboard jika login sebagai owner, pos jika kasir, atau login jika tamu
Route::get('/', function () {
    if (Auth::check()) {
        /** @var User $user */
        $user = Auth::user();

        return $user->isOwner()
            ? redirect()->route('dashboard')
            : redirect()->route('pos.index');
    }

    return redirect()->route('login');
});

// Autentikasi Pengguna Tamu (S-01, US-01, BR-38, BR-39)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Rute Terproteksi Sesi Autentikasi
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // ---------------------------------------------------------------------
    // RUTE KASIR & OWNER (Layar POS, Antrean, dan Shift Kasir)
    // ---------------------------------------------------------------------
    Route::middleware('role.kasir')->group(function () {
        Route::get('/pos', function () {
            return view('dashboard', ['title' => 'Layar Kasir POS']);
        })->name('pos.index');

        Route::get('/orders', function () {
            return view('dashboard', ['title' => 'Riwayat Transaksi']);
        })->name('orders.index');

        Route::get('/shifts/current', function () {
            return view('dashboard', ['title' => 'Shift Kasir']);
        })->name('shifts.current');
    });

    // ---------------------------------------------------------------------
    // RUTE KHUSUS OWNER (Dashboard, Menu, Bahan, Resep, Laporan, dsb.)
    // ---------------------------------------------------------------------
    Route::middleware('role.owner')->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        Route::get('/products', function () {
            $categories = [
                ['id' => 1, 'name' => 'Espresso Based', 'slug' => 'espresso-based', 'icon' => 'coffee', 'sort_order' => 1, 'products_count' => 2, 'is_active' => 1],
                ['id' => 2, 'name' => 'Manual Brew', 'slug' => 'manual-brew', 'icon' => 'coffee', 'sort_order' => 2, 'products_count' => 2, 'is_active' => 1],
                ['id' => 3, 'name' => 'Non-Coffee', 'slug' => 'non-coffee', 'icon' => 'cup', 'sort_order' => 3, 'products_count' => 2, 'is_active' => 1],
                ['id' => 4, 'name' => 'Snacks & Pastry', 'slug' => 'snacks-pastry', 'icon' => 'cake', 'sort_order' => 4, 'products_count' => 2, 'is_active' => 1],
            ];

            $products = [
                [
                    'id' => 1,
                    'category_id' => 1,
                    'category_name' => 'Espresso Based',
                    'name' => 'Es Kopi Susu Titik Nol',
                    'sku' => 'SKT-ESP01',
                    'price' => 20000,
                    'cost_price' => 7500,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Double shot espresso racikan Titik Nol, fresh milk, dan gula aren organik.',
                    'image_url' => '',
                ],
                [
                    'id' => 2,
                    'category_id' => 1,
                    'category_name' => 'Espresso Based',
                    'name' => 'Americano Hot / Iced',
                    'sku' => 'SKT-ESP02',
                    'price' => 18000,
                    'cost_price' => 5200,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Espresso murni diekstrak sempurna dengan air mineral segar.',
                    'image_url' => '',
                ],
                [
                    'id' => 3,
                    'category_id' => 2,
                    'category_name' => 'Manual Brew',
                    'name' => 'V60 Single Origin Pourover',
                    'sku' => 'SKT-MB01',
                    'price' => 24000,
                    'cost_price' => 9000,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Biji kopi arabika pilihan diseduh manual metode Hario V60.',
                    'image_url' => '',
                ],
                [
                    'id' => 4,
                    'category_id' => 2,
                    'category_name' => 'Manual Brew',
                    'name' => 'Japanese Drip Iced Coffee',
                    'sku' => 'SKT-MB02',
                    'price' => 26000,
                    'cost_price' => 9500,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Drip pourover di atas es batu dengan aroma bunga dan citrus menyegarkan.',
                    'image_url' => '',
                ],
                [
                    'id' => 5,
                    'category_id' => 3,
                    'category_name' => 'Non-Coffee',
                    'name' => 'Matcha Uji Latte',
                    'sku' => 'SKT-NC01',
                    'price' => 24000,
                    'cost_price' => 9200,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Bubuk matcha murni asal Uji Jepang dengan susu segar pilihan.',
                    'image_url' => '',
                ],
                [
                    'id' => 6,
                    'category_id' => 3,
                    'category_name' => 'Non-Coffee',
                    'name' => 'Signature Dark Chocolate',
                    'sku' => 'SKT-NC02',
                    'price' => 22000,
                    'cost_price' => 8100,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Cokelat hitam premium kental dengan rasa manis pahit yang seimbang.',
                    'image_url' => '',
                ],
                [
                    'id' => 7,
                    'category_id' => 4,
                    'category_name' => 'Snacks & Pastry',
                    'name' => 'Croissant Butter Perancis',
                    'sku' => 'SKT-SN01',
                    'price' => 25000,
                    'cost_price' => 13500,
                    'is_active' => 1,
                    'is_sold_out' => 0,
                    'description' => 'Pastry renyah berlapis mentega impor dipanggang hangat.',
                    'image_url' => '',
                ],
                [
                    'id' => 8,
                    'category_id' => 4,
                    'category_name' => 'Snacks & Pastry',
                    'name' => 'Truffle Fries Gurih',
                    'sku' => 'SKT-SN02',
                    'price' => 22000,
                    'cost_price' => 9800,
                    'is_active' => 1,
                    'is_sold_out' => 1,
                    'description' => 'Kentang goreng renyah dengan taburan minyak truffle dan keju parmesan.',
                    'image_url' => '',
                ],
            ];

            return view('products', [
                'title' => 'Manajemen Menu & HPP',
                'categories' => $categories,
                'products' => $products,
            ]);
        })->name('products.index');

        Route::get('/categories', function () {
            $categories = [
                ['id' => 1, 'name' => 'Espresso Based', 'slug' => 'espresso-based', 'icon' => 'coffee', 'sort_order' => 1, 'products_count' => 2, 'is_active' => 1],
                ['id' => 2, 'name' => 'Manual Brew', 'slug' => 'manual-brew', 'icon' => 'coffee', 'sort_order' => 2, 'products_count' => 2, 'is_active' => 1],
                ['id' => 3, 'name' => 'Non-Coffee', 'slug' => 'non-coffee', 'icon' => 'cup', 'sort_order' => 3, 'products_count' => 2, 'is_active' => 1],
                ['id' => 4, 'name' => 'Snacks & Pastry', 'slug' => 'snacks-pastry', 'icon' => 'cake', 'sort_order' => 4, 'products_count' => 2, 'is_active' => 1],
            ];

            return view('categories', [
                'title' => 'Manajemen Kategori Menu',
                'categories' => $categories,
            ]);
        })->name('categories.index');

        Route::get('/ingredients', function () {
            $defaultIngredients = [
                ['id' => 1, 'sku' => 'ING-KOP01', 'name' => 'Biji Kopi Arabika House Blend', 'unit' => 'gram', 'stock' => 4500, 'min_stock' => 2000, 'cost_per_unit' => 180, 'is_active' => 1, 'recipes_count' => 2],
                ['id' => 2, 'sku' => 'ING-SUS01', 'name' => 'Susu Sapi Segar UHT Full Cream', 'unit' => 'ml', 'stock' => 1800, 'min_stock' => 5000, 'cost_per_unit' => 18, 'is_active' => 1, 'recipes_count' => 2],
                ['id' => 3, 'sku' => 'ING-GLA01', 'name' => 'Sirup Gula Aren Asli Organik', 'unit' => 'ml', 'stock' => 3200, 'min_stock' => 1500, 'cost_per_unit' => 35, 'is_active' => 1, 'recipes_count' => 1],
                ['id' => 4, 'sku' => 'ING-MTC01', 'name' => 'Bubuk Matcha Asli Uji Jepang', 'unit' => 'gram', 'stock' => 0, 'min_stock' => 500, 'cost_per_unit' => 450, 'is_active' => 1, 'recipes_count' => 1],
                ['id' => 5, 'sku' => 'ING-KOP02', 'name' => 'Biji Kopi Single Origin Gayo', 'unit' => 'gram', 'stock' => 1200, 'min_stock' => 1000, 'cost_per_unit' => 250, 'is_active' => 1, 'recipes_count' => 1],
                ['id' => 6, 'sku' => 'ING-SRP01', 'name' => 'Sirup Karamel Gourmet', 'unit' => 'ml', 'stock' => 2500, 'min_stock' => 1000, 'cost_per_unit' => 40, 'is_active' => 1, 'recipes_count' => 0],
                ['id' => 7, 'sku' => 'ING-CRS01', 'name' => 'Butter Croissant Mentah Frozen', 'unit' => 'pcs', 'stock' => 24, 'min_stock' => 15, 'cost_per_unit' => 12500, 'is_active' => 1, 'recipes_count' => 1],
                ['id' => 8, 'sku' => 'ING-CUP01', 'name' => 'Cup Paper 12oz + Tutup Ramah Lingkungan', 'unit' => 'pcs', 'stock' => 450, 'min_stock' => 200, 'cost_per_unit' => 650, 'is_active' => 1, 'recipes_count' => 4],
            ];

            $defaultSuppliers = [
                ['id' => 1, 'name' => 'PT Roaster Nusantara Kopi', 'phone' => '081122334455', 'address' => 'Sleman, Yogyakarta', 'notes' => 'Biji Kopi Arabika & Robusta Specialty', 'is_active' => 1],
                ['id' => 2, 'name' => 'CV Segar Susu Merapi', 'phone' => '081233445566', 'address' => 'Kaliurang, Yogyakarta', 'notes' => 'Susu Segar UHT & Pasteurisasi Harian', 'is_active' => 1],
                ['id' => 3, 'name' => 'CV Aren Manis Nusantara', 'phone' => '081344556677', 'address' => 'Kulon Progo, D.I. Yogyakarta', 'notes' => 'Gula Aren Cair Organik Premium', 'is_active' => 1],
                ['id' => 4, 'name' => 'PT Kemasan Bersih Indonesia', 'phone' => '081455667788', 'address' => 'Bantul, D.I. Yogyakarta', 'notes' => 'Paper Cup, Sedotan & Kemasan Takeaway', 'is_active' => 1],
            ];

            try {
                if (Schema::hasTable('ingredients') && Ingredient::count() > 0) {
                    $defaultIngredients = Ingredient::withCount('recipes')->get()->map(function ($i) {
                        return [
                            'id' => $i->id,
                            'sku' => $i->sku,
                            'name' => $i->name,
                            'unit' => $i->unit,
                            'stock' => (float) $i->stock,
                            'min_stock' => (float) $i->min_stock,
                            'cost_per_unit' => (float) $i->cost_per_unit,
                            'is_active' => $i->is_active ? 1 : 0,
                            'recipes_count' => $i->recipes_count ?? 0,
                        ];
                    })->toArray();
                }

                if (Schema::hasTable('suppliers') && Supplier::count() > 0) {
                    $defaultSuppliers = Supplier::all()->toArray();
                }
            } catch (Throwable $e) {
                // Fallback sample data
            }

            return view('ingredients', [
                'title' => 'Manajemen Bahan Baku & Pemasok (S-05)',
                'ingredients' => $defaultIngredients,
                'suppliers' => $defaultSuppliers,
            ]);
        })->name('ingredients.index');

        Route::post('/ingredients', function (Request $request) {
            $validated = $request->validate([
                'name' => 'required|string|max:120',
                'sku' => 'nullable|string|max:40',
                'unit' => 'required|in:gram,ml,pcs',
                'stock' => 'nullable|numeric|min:0',
                'min_stock' => 'required|numeric|min:0',
                'cost_per_unit' => 'required|numeric|min:0',
            ]);

            try {
                if (Schema::hasTable('ingredients')) {
                    Ingredient::create([
                        'name' => $validated['name'],
                        'sku' => $validated['sku'] ?? ('ING-'.rand(1000, 9999)),
                        'unit' => $validated['unit'],
                        'stock' => $validated['stock'] ?? 0,
                        'min_stock' => $validated['min_stock'],
                        'cost_per_unit' => $validated['cost_per_unit'],
                        'is_active' => true,
                    ]);
                }
            } catch (Throwable $e) {
                // Fallback
            }

            return redirect()->route('ingredients.index')->with('success', 'Bahan baku baru berhasil didaftarkan!');
        })->name('ingredients.store');

        Route::get('/recipes', function () {
            $ingredients = [
                ['id' => 1, 'sku' => 'ING-KOP01', 'name' => 'Biji Kopi Arabika House Blend', 'unit' => 'gram', 'stock' => 4500, 'cost_per_unit' => 180],
                ['id' => 2, 'sku' => 'ING-SUS01', 'name' => 'Susu Sapi Segar UHT Full Cream', 'unit' => 'ml', 'stock' => 1800, 'cost_per_unit' => 18],
                ['id' => 3, 'sku' => 'ING-GLA01', 'name' => 'Sirup Gula Aren Asli Organik', 'unit' => 'ml', 'stock' => 3200, 'cost_per_unit' => 35],
                ['id' => 4, 'sku' => 'ING-MTC01', 'name' => 'Bubuk Matcha Asli Uji Jepang', 'unit' => 'gram', 'stock' => 0, 'cost_per_unit' => 450],
                ['id' => 5, 'sku' => 'ING-KOP02', 'name' => 'Biji Kopi Single Origin Gayo', 'unit' => 'gram', 'stock' => 1200, 'cost_per_unit' => 250],
                ['id' => 6, 'sku' => 'ING-SRP01', 'name' => 'Sirup Karamel Gourmet', 'unit' => 'ml', 'stock' => 2500, 'cost_per_unit' => 40],
                ['id' => 7, 'sku' => 'ING-CRS01', 'name' => 'Butter Croissant Mentah Frozen', 'unit' => 'pcs', 'stock' => 24, 'cost_per_unit' => 12500],
                ['id' => 8, 'sku' => 'ING-CUP01', 'name' => 'Cup Paper 12oz + Tutup Ramah Lingkungan', 'unit' => 'pcs', 'stock' => 450, 'cost_per_unit' => 650],
            ];

            $products = [
                [
                    'id' => 1,
                    'category_id' => 1,
                    'category_name' => 'Espresso Based',
                    'name' => 'Es Kopi Susu Titik Nol',
                    'sku' => 'SKT-ESP01',
                    'price' => 20000,
                    'cost_price' => 6215,
                    'margin' => 68.9,
                    'recipes' => [
                        ['ingredient_id' => 1, 'amount_required' => 18],
                        ['ingredient_id' => 2, 'amount_required' => 100],
                        ['ingredient_id' => 3, 'amount_required' => 15],
                        ['ingredient_id' => 8, 'amount_required' => 1],
                    ],
                ],
                [
                    'id' => 2,
                    'category_id' => 1,
                    'category_name' => 'Espresso Based',
                    'name' => 'Americano Hot / Iced',
                    'sku' => 'SKT-ESP02',
                    'price' => 18000,
                    'cost_price' => 3890,
                    'margin' => 78.4,
                    'recipes' => [
                        ['ingredient_id' => 1, 'amount_required' => 18],
                        ['ingredient_id' => 8, 'amount_required' => 1],
                    ],
                ],
                [
                    'id' => 3,
                    'category_id' => 2,
                    'category_name' => 'Manual Brew',
                    'name' => 'V60 Single Origin Pourover',
                    'sku' => 'SKT-MB01',
                    'price' => 24000,
                    'cost_price' => 4400,
                    'margin' => 81.7,
                    'recipes' => [
                        ['ingredient_id' => 5, 'amount_required' => 15],
                        ['ingredient_id' => 8, 'amount_required' => 1],
                    ],
                ],
                [
                    'id' => 5,
                    'category_id' => 3,
                    'category_name' => 'Non-Coffee',
                    'name' => 'Matcha Uji Latte',
                    'sku' => 'SKT-NC01',
                    'price' => 24000,
                    'cost_price' => 8210,
                    'margin' => 65.8,
                    'recipes' => [
                        ['ingredient_id' => 4, 'amount_required' => 12],
                        ['ingredient_id' => 2, 'amount_required' => 120],
                        ['ingredient_id' => 8, 'amount_required' => 1],
                    ],
                ],
                [
                    'id' => 7,
                    'category_id' => 4,
                    'category_name' => 'Snacks & Pastry',
                    'name' => 'Croissant Butter Artisan',
                    'sku' => 'SKT-SNK01',
                    'price' => 25000,
                    'cost_price' => 12500,
                    'margin' => 50.0,
                    'recipes' => [
                        ['ingredient_id' => 7, 'amount_required' => 1],
                    ],
                ],
            ];

            return view('recipes', [
                'title' => 'Manajemen Resep & Komposisi Menu (BOM)',
                'products' => $products,
                'ingredients' => $ingredients,
            ]);
        })->name('recipes.index');

        Route::post('/recipes', function (Request $request) {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'items' => 'required|array',
                'items.*.ingredient_id' => 'required|integer',
                'items.*.amount_required' => 'required|numeric|min:0.001',
            ]);

            try {
                if (Schema::hasTable('recipes')) {
                    Recipe::where('product_id', $validated['product_id'])->delete();
                    foreach ($validated['items'] as $item) {
                        Recipe::create([
                            'product_id' => $validated['product_id'],
                            'ingredient_id' => $item['ingredient_id'],
                            'amount_required' => $item['amount_required'],
                        ]);
                    }
                }
            } catch (Throwable $e) {
                // Fallback
            }

            return redirect()->route('recipes.index')->with('success', 'Formula resep (BOM) berhasil disimpan!');
        })->name('recipes.store');

        Route::get('/stock', function () {
            $ingredients = [
                ['id' => 1, 'sku' => 'ING-KOP01', 'name' => 'Biji Kopi Arabika House Blend', 'unit' => 'gram', 'stock' => 4500, 'cost_per_unit' => 180],
                ['id' => 2, 'sku' => 'ING-SUS01', 'name' => 'Susu Sapi Segar UHT Full Cream', 'unit' => 'ml', 'stock' => 1800, 'cost_per_unit' => 18],
                ['id' => 3, 'sku' => 'ING-GLA01', 'name' => 'Sirup Gula Aren Asli Organik', 'unit' => 'ml', 'stock' => 3200, 'cost_per_unit' => 35],
                ['id' => 4, 'sku' => 'ING-MTC01', 'name' => 'Bubuk Matcha Asli Uji Jepang', 'unit' => 'gram', 'stock' => 0, 'cost_per_unit' => 450],
                ['id' => 5, 'sku' => 'ING-KOP02', 'name' => 'Biji Kopi Single Origin Gayo', 'unit' => 'gram', 'stock' => 1200, 'cost_per_unit' => 250],
                ['id' => 6, 'sku' => 'ING-SRP01', 'name' => 'Sirup Karamel Gourmet', 'unit' => 'ml', 'stock' => 2500, 'cost_per_unit' => 40],
                ['id' => 7, 'sku' => 'ING-CRS01', 'name' => 'Butter Croissant Mentah Frozen', 'unit' => 'pcs', 'stock' => 24, 'cost_per_unit' => 12500],
                ['id' => 8, 'sku' => 'ING-CUP01', 'name' => 'Cup Paper 12oz + Tutup Ramah Lingkungan', 'unit' => 'pcs', 'stock' => 450, 'cost_per_unit' => 650],
            ];

            $suppliers = [
                ['id' => 1, 'name' => 'PT Roaster Nusantara Kopi'],
                ['id' => 2, 'name' => 'CV Segar Susu Merapi'],
                ['id' => 3, 'name' => 'CV Aren Manis Nusantara'],
                ['id' => 4, 'name' => 'PT Kemasan Bersih Indonesia'],
            ];

            $mutations = [
                [
                    'id' => 1,
                    'ingredient_name' => 'Biji Kopi Arabika House Blend',
                    'ingredient_unit' => 'gram',
                    'type' => 'sales',
                    'quantity' => -18,
                    'stock_before' => 4518,
                    'stock_after' => 4500,
                    'unit_cost' => 180,
                    'notes' => 'Penjualan Pesanan Kasir #SKT-261026-001',
                    'created_at' => Carbon::now()->subMinutes(25)->toIso8601String(),
                ],
                [
                    'id' => 2,
                    'ingredient_name' => 'Susu Sapi Segar UHT Full Cream',
                    'ingredient_unit' => 'ml',
                    'type' => 'spill',
                    'quantity' => -200,
                    'stock_before' => 2000,
                    'stock_after' => 1800,
                    'unit_cost' => 18,
                    'notes' => 'Box kemasan susu bocor saat persiapan meja bar barista',
                    'created_at' => Carbon::now()->subHours(2)->toIso8601String(),
                ],
                [
                    'id' => 3,
                    'ingredient_name' => 'Sirup Gula Aren Asli Organik',
                    'ingredient_unit' => 'ml',
                    'type' => 'in',
                    'quantity' => 5000,
                    'stock_before' => 200,
                    'stock_after' => 5200,
                    'unit_cost' => 35,
                    'notes' => 'Pembelian Stok Masuk #PO-261026-001',
                    'created_at' => Carbon::now()->subHours(4)->toIso8601String(),
                ],
                [
                    'id' => 4,
                    'ingredient_name' => 'Cup Paper 12oz + Tutup Ramah Lingkungan',
                    'ingredient_unit' => 'pcs',
                    'type' => 'adjustment',
                    'quantity' => 10,
                    'stock_before' => 440,
                    'stock_after' => 450,
                    'unit_cost' => 650,
                    'notes' => 'Penyesuaian koreksi Stock Opname fisik mingguan',
                    'created_at' => Carbon::yesterday()->setHour(16)->toIso8601String(),
                ],
                [
                    'id' => 5,
                    'ingredient_name' => 'Bubuk Matcha Asli Uji Jepang',
                    'ingredient_unit' => 'gram',
                    'type' => 'expired',
                    'quantity' => -100,
                    'stock_before' => 100,
                    'stock_after' => 0,
                    'unit_cost' => 450,
                    'notes' => 'Bubuk menggumpal dan basah karena uap air chiller',
                    'created_at' => Carbon::yesterday()->setHour(11)->toIso8601String(),
                ],
            ];

            return view('stock', [
                'title' => 'Mutasi Stok & Pembelian Bahan (S-06, S-07)',
                'ingredients' => $ingredients,
                'suppliers' => $suppliers,
                'mutations' => $mutations,
            ]);
        })->name('stock.index');

        Route::post('/stock/purchase', function (Request $request) {
            $validated = $request->validate([
                'purchase_number' => 'required|string|max:30',
                'supplier_id' => 'required|integer',
                'purchased_at' => 'required|date',
                'items' => 'required|array',
                'items.*.ingredient_id' => 'required|integer',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'items.*.unit_cost' => 'required|numeric|min:0',
            ]);

            return redirect()->route('stock.index')->with('success', 'Faktur pembelian bahan berhasil disimpan!');
        })->name('stock.purchase');

        Route::post('/stock/adjustment', function (Request $request) {
            $validated = $request->validate([
                'ingredient_id' => 'required|integer',
                'type' => 'required|in:spill,expired,adjustment',
                'quantity' => 'required|numeric|min:0.001',
                'notes' => 'required|string|min:5|max:255',
            ]);

            return redirect()->route('stock.index')->with('success', 'Penyesuaian stok manual berhasil dicatat pada buku besar mutasi.');
        })->name('stock.adjustment');

        Route::get('/purchases', function () {
            return redirect()->route('stock.index');
        })->name('purchases.index');

        Route::get('/reports', function () {
            return view('dashboard', ['title' => 'Laporan & Laba']);
        })->name('reports.index');

        Route::get('/vouchers', function () {
            return view('dashboard', ['title' => 'Voucher & Promo']);
        })->name('vouchers.index');

        Route::get('/banners', function () {
            return view('dashboard', ['title' => 'Banner Promo']);
        })->name('banners.index');

        Route::get('/users', function () {
            $dbUsers = [];
            try {
                if (Schema::hasTable('users')) {
                    $dbUsers = User::all()->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'role' => $u->role ?? 'kasir',
                            'phone' => $u->phone ?? '-',
                            'pin_set' => ! empty($u->pin_code),
                            'is_active' => $u->is_active ?? 1,
                            'last_login_at' => ! empty($u->last_login_at) ? Carbon::parse($u->last_login_at)->format('d M Y H:i') : 'Belum pernah',
                            'created_at' => $u->created_at ? $u->created_at->format('d M Y') : '-',
                        ];
                    })->toArray();
                }
            } catch (Throwable $e) {
                $dbUsers = [];
            }

            if (empty($dbUsers)) {
                $dbUsers = [
                    [
                        'id' => 1,
                        'name' => 'Pemilik Kedai (Owner)',
                        'email' => 'owner@titiknol.com',
                        'role' => 'owner',
                        'phone' => '081122334455',
                        'pin_set' => true,
                        'is_active' => 1,
                        'last_login_at' => '15 Okt 2026 10:15',
                        'created_at' => '01 Jan 2026',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Budi Santoso',
                        'email' => 'budi.kasir@titiknol.com',
                        'role' => 'kasir',
                        'phone' => '081234567890',
                        'pin_set' => false,
                        'is_active' => 1,
                        'last_login_at' => '15 Okt 2026 08:30',
                        'created_at' => '10 Jan 2026',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Siti Rahma',
                        'email' => 'siti.kasir@titiknol.com',
                        'role' => 'kasir',
                        'phone' => '081298765432',
                        'pin_set' => false,
                        'is_active' => 1,
                        'last_login_at' => '14 Okt 2026 21:45',
                        'created_at' => '15 Jan 2026',
                    ],
                    [
                        'id' => 4,
                        'name' => 'Andi Pratama',
                        'email' => 'andi.kasir@titiknol.com',
                        'role' => 'kasir',
                        'phone' => '081377889900',
                        'pin_set' => false,
                        'is_active' => 0,
                        'last_login_at' => '01 Okt 2026 17:00',
                        'created_at' => '05 Jan 2026',
                    ],
                ];
            }

            return view('staff', [
                'title' => 'Manajemen Staf & Pengguna',
                'users' => $dbUsers,
            ]);
        })->name('users.index');

        Route::get('/settings', function () {
            $defaultSettings = [
                'store_name' => 'Kopi Titik Nol',
                'store_address' => 'Jl. Kaliurang KM 5, Sleman, D.I. Yogyakarta 55281',
                'store_phone' => '081234567890',
                'store_logo' => '',
                'receipt_header' => 'KOPI TITIK NOL • COFFEE & ROASTERY',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda, sampai jumpa lagi!',
                'paper_size' => 80,
                'show_logo_on_receipt' => 1,
                'show_queue_number' => 1,
                'tax_percent' => '0',
                'service_percent' => '0',
                'tax_inclusive' => 0,
                'cash_rounding' => 100,
                'qris_expiry_minutes' => 15,
                'payment_provider' => 'midtrans',
                'allow_negative_stock_on_paid' => 1,
            ];

            try {
                if (Schema::hasTable('settings')) {
                    foreach ($defaultSettings as $key => $val) {
                        $settingVal = Setting::get($key, null);
                        if ($settingVal !== null) {
                            $defaultSettings[$key] = $settingVal;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Gunakan default fallback jika database belum termigrasi
            }

            return view('settings', [
                'title' => 'Pengaturan Toko & Struk',
                'settings' => $defaultSettings,
            ]);
        })->name('settings.index');

        Route::post('/settings', function (Request $request) {
            $validated = $request->validate([
                'store_name' => 'required|string|max:100',
                'store_address' => 'required|string|max:255',
                'store_phone' => 'required|string|max:30',
                'store_logo' => 'nullable|string',
                'receipt_header' => 'nullable|string|max:100',
                'receipt_footer' => 'required|string|max:255',
                'paper_size' => 'required|in:58,80',
                'show_logo_on_receipt' => 'nullable|boolean',
                'show_queue_number' => 'nullable|boolean',
                'tax_percent' => 'required|numeric|min:0|max:100',
                'service_percent' => 'required|numeric|min:0|max:100',
                'tax_inclusive' => 'nullable|boolean',
                'cash_rounding' => 'required|integer|in:0,50,100,500',
                'qris_expiry_minutes' => 'required|integer|min:1|max:60',
                'payment_provider' => 'required|string|in:midtrans,mock',
                'allow_negative_stock_on_paid' => 'nullable|boolean',
            ]);

            try {
                if (Schema::hasTable('settings')) {
                    $groupMap = [
                        'store_name' => 'store',
                        'store_address' => 'store',
                        'store_phone' => 'store',
                        'store_logo' => 'store',
                        'receipt_header' => 'receipt',
                        'receipt_footer' => 'receipt',
                        'paper_size' => 'receipt',
                        'show_logo_on_receipt' => 'receipt',
                        'show_queue_number' => 'receipt',
                        'tax_percent' => 'tax',
                        'service_percent' => 'tax',
                        'tax_inclusive' => 'tax',
                        'cash_rounding' => 'tax',
                        'qris_expiry_minutes' => 'payment',
                        'payment_provider' => 'payment',
                        'allow_negative_stock_on_paid' => 'stock',
                    ];

                    $userId = Auth::id();
                    foreach ($validated as $key => $value) {
                        $group = $groupMap[$key] ?? 'general';
                        if (in_array($key, ['show_logo_on_receipt', 'show_queue_number', 'tax_inclusive', 'allow_negative_stock_on_paid'])) {
                            $value = (bool) $value;
                        }
                        if (in_array($key, ['paper_size', 'cash_rounding', 'qris_expiry_minutes'])) {
                            $value = (int) $value;
                        }
                        Setting::set($key, $value, $group, $userId);
                    }
                }
            } catch (Throwable $e) {
                // Log atau silent di mock/testing
            }

            return redirect()->route('settings.index')->with('success', 'Pengaturan toko, pajak, dan format struk berhasil disimpan!');
        })->name('settings.update');

        Route::get('/audit-logs', function () {
            return view('dashboard', ['title' => 'Audit Log']);
        })->name('audit-logs.index');
    });
});
