<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Foreign key tambahan dari shift_cash_movements ke orders (BR-27, BR-36).
     */
    public function up(): void
    {
        Schema::table('shift_cash_movements', function (Blueprint $table) {
            $table->foreign('order_id', 'fk_scm_order')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_cash_movements', function (Blueprint $table) {
            $table->dropForeign('fk_scm_order');
        });
    }
};

