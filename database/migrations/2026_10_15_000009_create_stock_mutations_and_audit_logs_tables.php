<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration buku besar stok dan audit log: stock_mutations dan audit_logs (US-07, US-08, US-31, BR-13, BR-18, NFR-12).
     */
    public function up(): void
    {
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->enum('type', ['in', 'out', 'spill', 'expired', 'adjustment', 'sales', 'void_rollback']);
            $table->decimal('quantity', 14, 3)->comment('bertanda: + menambah, - mengurangi');
            $table->decimal('stock_before', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ingredient_id', 'created_at'], 'idx_sm_ingredient_date');
            $table->index(['type', 'created_at'], 'idx_sm_type_date');
            $table->index('order_id', 'idx_sm_order');
            $table->index('order_item_id', 'idx_sm_order_item');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60)->comment('mis. product.price_changed, order.voided');
            $table->string('auditable_type', 80)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at'], 'idx_audit_user_date');
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_target');
            $table->index(['action', 'created_at'], 'idx_audit_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('stock_mutations');
    }
};

