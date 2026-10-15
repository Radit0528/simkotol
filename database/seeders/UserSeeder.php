<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * [BACKEND] Seeder Akun Owner & Kasir Awal (US-01, US-02, BR-38).
 * Password dan PIN 6-digit di-hash secara aman menggunakan Hash::make.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Owner Utama
        User::updateOrCreate(
            ['email' => 'owner@simkotol.com'],
            [
                'name' => 'Owner Kopi Titik Nol',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'phone' => '081234567890',
                'pin_code' => Hash::make('123456'), // PIN 6 digit approval void
                'pin_failed_attempts' => 0,
                'pin_locked_until' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Kasir Contoh (untuk operasional kasir & testing shift)
        User::updateOrCreate(
            ['email' => 'kasir@simkotol.com'],
            [
                'name' => 'Kasir Titik Nol',
                'password' => Hash::make('password123'),
                'role' => 'kasir',
                'phone' => '081234567891',
                'pin_code' => null,
                'pin_failed_attempts' => 0,
                'pin_locked_until' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

