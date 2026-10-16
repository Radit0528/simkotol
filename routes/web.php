<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Carbon\Carbon;
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
            return view('dashboard', ['title' => 'Bahan Baku & Resep']);
        })->name('ingredients.index');

        Route::get('/recipes', function () {
            return view('dashboard', ['title' => 'Resep (BOM)']);
        })->name('recipes.index');

        Route::get('/stock', function () {
            return view('dashboard', ['title' => 'Mutasi & Stok Masuk']);
        })->name('stock.index');

        Route::get('/purchases', function () {
            return view('dashboard', ['title' => 'Pembelian Stok']);
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
            return view('dashboard', ['title' => 'Pengaturan Toko']);
        })->name('settings.index');

        Route::get('/audit-logs', function () {
            return view('dashboard', ['title' => 'Audit Log']);
        })->name('audit-logs.index');
    });
});
