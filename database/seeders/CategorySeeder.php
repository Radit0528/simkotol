<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * [BACKEND] Seeder Kategori Menu Awal (US-03).
 * Menyediakan data awal kategori menu untuk POS dan manajemen katalog.
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kopi',
                'slug' => 'kopi',
                'icon' => 'coffee',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Non-Kopi',
                'slug' => 'non-kopi',
                'icon' => 'cup-soda',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Makanan Ringan',
                'slug' => 'makanan-ringan',
                'icon' => 'croissant',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Makanan Berat',
                'slug' => 'makanan-berat',
                'icon' => 'utensils',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

