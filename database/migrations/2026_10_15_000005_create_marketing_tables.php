<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration marketing: vouchers dan banners (US-23, US-24, BR-08 s/d BR-11).
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique('uq_vouchers_code');
            $table->string('name', 120);
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedBigInteger('value')->comment('persen (1-100) atau nominal Rp');
            $table->unsignedBigInteger('max_discount')->nullable()->comment('plafon diskon untuk tipe percent');
            $table->unsignedBigInteger('min_purchase')->default(0);
            $table->unsignedInteger('quota')->nullable()->comment('NULL = tanpa batas');
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'idx_vouchers_period');
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('image', 255);
            $table->string('link_url', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'idx_banners_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('vouchers');
    }
};

