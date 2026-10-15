<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes - SIMKOTOL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Autentikasi (S-01, US-01)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Akan diimplementasikan pada B0.4 / Autentikasi Service
    return redirect()->route('dashboard');
});

Route::post('/logout', function () {
    return redirect()->route('login');
})->name('logout');

// Rute Layar Utama SIMKOTOL (Sesuai Blueprint S-01 s/d S-22)
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/pos', function () {
    return view('dashboard', ['title' => 'Layar Kasir POS']);
})->name('pos.index');

Route::get('/products', function () {
    return view('dashboard', ['title' => 'Menu & Kategori']);
})->name('products.index');

Route::get('/categories', function () {
    return view('dashboard', ['title' => 'Kategori Menu']);
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

Route::get('/orders', function () {
    return view('dashboard', ['title' => 'Riwayat Transaksi']);
})->name('orders.index');

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

Route::get('/shifts/current', function () {
    return view('dashboard', ['title' => 'Shift Kasir']);
})->name('shifts.current');
