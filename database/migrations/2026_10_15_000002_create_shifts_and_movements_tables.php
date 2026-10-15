<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration tabel shifts dan shift_cash_movements (US-11, US-12, US-13, BR-01, BR-02, BR-26 s/d BR-31).
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_code', 30)->unique('uq_shift_code');
            $table->foreignId('user_id')->comment('kasir pemilik shift')->constrained('users');
            $table->unsignedBigInteger('cash_float_start')->default(0);
            $table->bigInteger('cash_expected_end')->nullable()->comment('dihitung sistem saat tutup');
            $table->unsignedBigInteger('cash_actual_end')->nullable()->comment('uang fisik');
            $table->bigInteger('cash_variance')->nullable()->comment('fisik - expected');
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedBigInteger('total_cash_sales')->default(0);
            $table->unsignedBigInteger('total_noncash_sales')->default(0);
            $table->unsignedBigInteger('total_void_amount')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->comment('beda dengan user_id jika ditutup paksa owner')->constrained('users')->nullOnDelete();
            $table->string('close_notes', 255)->nullable();
            // Menjamin 1 kasir hanya punya 1 shift open (NULL tidak dianggap duplikat)
            $table->unsignedBigInteger('open_lock')->storedAs("IF(status = 'open', user_id, NULL)")->nullable();
            $table->timestamps();

            $table->unique('open_lock', 'uq_shift_one_open_per_user');
            $table->index(['user_id', 'status'], 'idx_shifts_user_status');
            $table->index('opened_at', 'idx_shifts_opened');
        });

        Schema::create('shift_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['in', 'out', 'refund'])->comment('refund = pengembalian tunai akibat void');
            $table->unsignedBigInteger('amount');
            $table->string('reason', 255);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('shift_id', 'idx_scm_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_cash_movements');
        Schema::dropIfExists('shifts');
    }
};

