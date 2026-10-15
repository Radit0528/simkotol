<?php

use Illuminate\Support\Facades\Route;

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
    return view('dashboard', ['title' => 'Kelola Staf & Pengguna']);
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
