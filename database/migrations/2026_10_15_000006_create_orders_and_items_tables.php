<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration pesanan: orders, order_items, order_item_options (US-14 s/d US-21, BR-03 s/d BR-07, BR-18).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique('uq_orders_number')->comment('SKT-YYMMDD-0001');
            $table->date('queue_date');
            $table->unsignedSmallInteger('queue_number');
            $table->foreignId('shift_id')->constrained('shifts');
            $table->foreignId('cashier_id')->constrained('users');
            $table->enum('order_type', ['dine_in', 'take_away'])->default('dine_in');
            $table->string('customer_name', 80)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('voucher_code', 30)->nullable()->comment('snapshot kode');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('service_amount')->default(0);
            $table->bigInteger('rounding_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->enum('status', ['draft', 'pending', 'paid', 'expired', 'cancelled', 'voided'])->default('pending');
            $table->string('notes', 255)->nullable();
            $table->dateTime('ordered_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();

            $table->unique(['queue_date', 'queue_number'], 'uq_orders_queue');
            $table->index(['status', 'ordered_at'], 'idx_orders_status_date');
            $table->index(['shift_id', 'status'], 'idx_orders_shift_status');
            $table->index(['cashier_id', 'ordered_at'], 'idx_orders_cashier');
            $table->index('paid_at', 'idx_orders_paid_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 120)->comment('snapshot');
            $table->unsignedBigInteger('unit_price')->comment('snapshot harga');
            $table->unsignedSmallInteger('quantity');
            $table->bigInteger('options_total')->default(0)->comment('total price_delta opsi per unit');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('cost_price_snapshot')->default(0)->comment('HPP per unit saat transaksi');
            $table->string('note', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_oi_order');
            $table->index('product_id', 'idx_oi_product');
        });

        // Fase 2: Snapshot opsi yang dipilih
        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_option_id')->nullable()->constrained('product_options')->nullOnDelete();
            $table->string('group_name', 60);
            $table->string('option_name', 60);
            $table->bigInteger('price_delta')->default(0);

            $table->index('order_item_id', 'idx_oio_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_options');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

