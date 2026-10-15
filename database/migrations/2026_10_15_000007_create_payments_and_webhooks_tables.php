<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration pembayaran & webhook: payments dan payment_webhook_logs (US-17, US-18, BR-21 s/d BR-25).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('method', ['cash', 'qris', 'ewallet', 'virtual_account', 'bank_transfer']);
            $table->string('provider', 30)->nullable()->comment('midtrans | xendit | pakasir');
            $table->string('external_id', 80)->unique('uq_payments_external')->comment('ID unik dari SIMKOTOL yang dikirim ke gateway');
            $table->string('provider_reference', 120)->nullable()->comment('ID transaksi di gateway');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('cash_received')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->enum('status', ['pending', 'paid', 'expired', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->text('qr_string')->nullable();
            $table->string('qr_url', 255)->nullable();
            $table->string('va_number', 40)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->boolean('needs_review')->default(false)->comment('bayar terlambat / nominal tidak cocok');
            $table->string('review_note', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('order_id', 'idx_payments_order');
            $table->index(['status', 'expires_at'], 'idx_payments_status_exp');
            $table->index(['provider', 'provider_reference'], 'idx_payments_provider_ref');
            $table->index('needs_review', 'idx_payments_review');
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 120)->nullable()->comment('ID event unik dari gateway (idempotensi)');
            $table->string('external_id', 80)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->enum('status', ['received', 'processed', 'ignored', 'failed'])->default('received');
            $table->string('error_message', 255)->nullable();
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();

            $table->unique(['provider', 'event_id'], 'uq_webhook_event');
            $table->index('external_id', 'idx_webhook_external');
            $table->index(['status', 'received_at'], 'idx_webhook_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('payments');
    }
};

