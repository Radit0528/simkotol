<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * [BACKEND] Database Seeder Utama SIMKOTOL.
 * Menjalankan seeder akun pengguna, pengaturan default, dan kategori menu awal.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
