<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration bahan baku, supplier, pembelian, dan resep/BOM (US-06 s/d US-10, BR-12 s/d BR-20).
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->nullable()->unique('uq_ingredients_sku');
            $table->string('name', 120)->unique('uq_ingredients_name');
            $table->enum('unit', ['gram', 'ml', 'pcs']);
            $table->decimal('stock', 14, 3)->default(0)->comment('boleh negatif (BR-16)');
            $table->decimal('min_stock', 14, 3)->default(0);
            $table->decimal('cost_per_unit', 14, 4)->default(0)->comment('moving average');
            $table->boolean('is_active')->default(true);
            $table->dateTime('low_stock_notified_at')->nullable()->comment('anti-spam notifikasi');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stock', 'min_stock'], 'idx_ingredients_stock');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 30)->unique('uq_purchase_number');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('purchased_at');
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('purchased_at', 'idx_purchases_date');
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 4)->comment('harga per satuan dasar (gram/ml/pcs)');
            $table->unsignedBigInteger('subtotal');

            $table->index('purchase_id', 'idx_pi_purchase');
            $table->index('ingredient_id', 'idx_pi_ingredient');
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->decimal('amount_required', 14, 3);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id'], 'uq_recipe_product_ingredient');
            $table->index('ingredient_id', 'idx_recipes_ingredient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('ingredients');
    }
};

