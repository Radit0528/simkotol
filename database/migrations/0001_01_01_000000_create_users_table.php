<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration tabel users, password_reset_tokens, dan sessions (US-01, US-02, BR-38, BR-39).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->unique('uq_users_email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'kasir'])->default('kasir');
            $table->string('phone', 20)->nullable();
            $table->string('pin_code')->nullable()->comment('hash PIN 6 digit (untuk approval owner)');
            $table->unsignedTinyInteger('pin_failed_attempts')->default(0);
            $table->dateTime('pin_locked_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role', 'is_active'], 'idx_users_role_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
