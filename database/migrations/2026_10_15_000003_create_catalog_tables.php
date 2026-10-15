<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration katalog: categories, products, product_option_groups, product_options (US-03, US-04, US-05, BR-14, BR-40).
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique('uq_categories_name');
            $table->string('slug', 100)->unique('uq_categories_slug');
            $table->string('icon', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('sku', 40)->nullable()->unique('uq_products_sku');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->comment('harga jual (Rp)');
            $table->unsignedBigInteger('cost_price')->default(0)->comment('HPP terhitung dari resep');
            $table->string('image', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sold_out')->default(false)->comment('penanda habis manual');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active'], 'idx_products_cat_active');
            $table->index('name', 'idx_products_name');
        });

        // Fase 2: varian/opsi seperti ukuran, level gula, es
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 60)->comment('mis. Ukuran, Level Gula');
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('min_select')->default(0);
            $table->unsignedTinyInteger('max_select')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->index('product_id', 'idx_pog_product');
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('product_option_groups')->cascadeOnDelete();
            $table->string('name', 60);
            $table->bigInteger('price_delta')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->index('group_id', 'idx_po_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_option_groups');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};

