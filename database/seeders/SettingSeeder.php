<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * [BACKEND] Seeder Pengaturan Awal Toko & Sistem (US-32, BR-04, BR-05, BR-22).
 * Berdasarkan DDL database_simkotol.sql bagian 11.
 */
class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Store
            [
                'group' => 'store',
                'key' => 'store_name',
                'value' => 'Kopi Titik Nol',
                'type' => 'string',
            ],
            [
                'group' => 'store',
                'key' => 'store_address',
                'value' => 'Jl. Kaliurang KM 5, Yogyakarta',
                'type' => 'string',
            ],
            [
                'group' => 'store',
                'key' => 'store_phone',
                'value' => '081234567890',
                'type' => 'string',
            ],
            // Receipt
            [
                'group' => 'receipt',
                'key' => 'receipt_footer',
                'value' => 'Terima kasih, sampai jumpa lagi!',
                'type' => 'string',
            ],
            [
                'group' => 'receipt',
                'key' => 'paper_size',
                'value' => '80',
                'type' => 'int',
            ],
            // Tax & Charges (Default 0% sesuai BR-04)
            [
                'group' => 'tax',
                'key' => 'tax_percent',
                'value' => '0',
                'type' => 'string',
            ],
            [
                'group' => 'tax',
                'key' => 'service_percent',
                'value' => '0',
                'type' => 'string',
            ],
            [
                'group' => 'tax',
                'key' => 'tax_inclusive',
                'value' => '0',
                'type' => 'bool',
            ],
            [
                'group' => 'tax',
                'key' => 'cash_rounding',
                'value' => '100',
                'type' => 'int',
            ],
            // Payment
            [
                'group' => 'payment',
                'key' => 'qris_expiry_minutes',
                'value' => '15',
                'type' => 'int',
            ],
            [
                'group' => 'payment',
                'key' => 'payment_provider',
                'value' => 'midtrans',
                'type' => 'string',
            ],
            // Stock Policy (BR-16)
            [
                'group' => 'stock',
                'key' => 'allow_negative_stock_on_paid',
                'value' => '1',
                'type' => 'bool',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

