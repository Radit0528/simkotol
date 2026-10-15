<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration order_voids (US-22, BR-32 s/d BR-37).
     */
    public function up(): void
    {
        Schema::create('order_voids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique('uq_void_order')->constrained('orders');
            $table->foreignId('shift_id')->comment('shift saat void dilakukan')->constrained('shifts');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->comment('owner pemilik PIN')->constrained('users');
            $table->string('reason', 255);
            $table->enum('stock_action', ['rollback', 'waste']);
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->enum('refund_method', ['cash', 'manual_transfer', 'none'])->default('none');
            $table->enum('refund_status', ['not_required', 'pending', 'completed'])->default('not_required');
            $table->string('refund_note', 255)->nullable();
            $table->dateTime('voided_at');
            $table->timestamp('created_at')->nullable();

            $table->index('voided_at', 'idx_void_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_voids');
    }
};

