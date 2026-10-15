<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * [BACKEND] Seeder Akun Pengguna Awal (US-01, US-02, BR-38).
 * Didesain aman untuk repositori publik (Public Repo Safe):
 * - Di lingkungan lokal (APP_ENV=local), menggunakan kredensial pengujian standar.
 * - Di lingkungan produksi (APP_ENV=production), mewajibkan kredensial dari .env
 *   atau otomatis meng-generate password & PIN acak yang aman.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isLocal = app()->isLocal() || app()->runningUnitTests();

        // 1. Kredensial Owner
        $ownerName = env('SEED_OWNER_NAME', 'Owner Kopi Titik Nol');
        $ownerEmail = env('SEED_OWNER_EMAIL', 'owner@simkotol.com');
        $ownerPhone = env('SEED_OWNER_PHONE', '081234567890');

        $rawPassword = env('SEED_OWNER_PASSWORD');
        $rawPin = env('SEED_OWNER_PIN');

        if (empty($rawPassword)) {
            $rawPassword = $isLocal ? 'password123' : Str::password(16);
            if (! $isLocal) {
                $this->command?->warn(" [PROD NOTICE] Generated secure Owner password: {$rawPassword}");
            }
        }

        if (empty($rawPin)) {
            $rawPin = $isLocal ? '123456' : (string) random_int(100000, 999999);
            if (! $isLocal) {
                $this->command?->warn(" [PROD NOTICE] Generated secure Owner 6-digit PIN: {$rawPin}");
            }
        }

        User::updateOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $ownerName,
                'password' => Hash::make($rawPassword),
                'role' => 'owner',
                'phone' => $ownerPhone,
                'pin_code' => Hash::make($rawPin),
                'pin_failed_attempts' => 0,
                'pin_locked_until' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($isLocal) {
            $this->command?->info(" Akun Owner dibuat ({$ownerEmail} / {$rawPassword} / PIN: {$rawPin})");
        }

        // 2. Akun Kasir Contoh (Hanya dibuat pada mode lokal atau jika SEED_KASIR_EMAIL diisi)
        $seedKasir = $isLocal || env('SEED_KASIR_EMAIL');

        if ($seedKasir) {
            $kasirName = env('SEED_KASIR_NAME', 'Kasir Titik Nol');
            $kasirEmail = env('SEED_KASIR_EMAIL', 'kasir@simkotol.com');
            $kasirPhone = env('SEED_KASIR_PHONE', '081234567891');
            $kasirPassword = env('SEED_KASIR_PASSWORD', $isLocal ? 'password123' : Str::password(16));

            User::updateOrCreate(
                ['email' => $kasirEmail],
                [
                    'name' => $kasirName,
                    'password' => Hash::make($kasirPassword),
                    'role' => 'kasir',
                    'phone' => $kasirPhone,
                    'pin_code' => null,
                    'pin_failed_attempts' => 0,
                    'pin_locked_until' => null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($isLocal) {
                $this->command?->info(" Akun Kasir dibuat ({$kasirEmail} / {$kasirPassword})");
            }
        }
    }
}
