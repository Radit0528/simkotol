<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * [BACKEND] Migration SQL Views bantu: v_ingredient_stock_status, v_product_availability, v_daily_sales (US-10, US-14, US-26, BR-14, BR-20).
     */
    public function up(): void
    {
        // 1. Status stok bahan: ok | low | out
        DB::statement("
            CREATE OR REPLACE VIEW v_ingredient_stock_status AS
            SELECT
              i.id, i.name, i.unit, i.stock, i.min_stock,
              CASE
                WHEN i.stock <= 0 THEN 'out'
                WHEN i.stock <= i.min_stock THEN 'low'
                ELSE 'ok'
              END AS stock_status
            FROM ingredients i
            WHERE i.deleted_at IS NULL AND i.is_active = 1;
        ");

        // 2. Ketersediaan menu (jumlah porsi maksimal dari stok bahan); NULL = tanpa resep
        DB::statement("
            CREATE OR REPLACE VIEW v_product_availability AS
            SELECT
              p.id AS product_id,
              p.name,
              p.is_sold_out,
              CASE WHEN COUNT(r.id) = 0 THEN NULL
                   ELSE GREATEST(0, MIN(FLOOR(i.stock / r.amount_required)))
              END AS max_portions
            FROM products p
            LEFT JOIN recipes r ON r.product_id = p.id
            LEFT JOIN ingredients i ON i.id = r.ingredient_id
            WHERE p.deleted_at IS NULL AND p.is_active = 1
            GROUP BY p.id, p.name, p.is_sold_out;
        ");

        // 3. Penjualan harian (hanya PAID)
        DB::statement("
            CREATE OR REPLACE VIEW v_daily_sales AS
            SELECT
              DATE(o.paid_at) AS sales_date,
              COUNT(*)        AS total_orders,
              SUM(o.grand_total) AS total_sales,
              SUM(o.discount_amount) AS total_discount
            FROM orders o
            WHERE o.status = 'paid'
            GROUP BY DATE(o.paid_at);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_daily_sales;');
        DB::statement('DROP VIEW IF EXISTS v_product_availability;');
        DB::statement('DROP VIEW IF EXISTS v_ingredient_stock_status;');
    }
};

