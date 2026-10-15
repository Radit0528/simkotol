<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [BACKEND] Migration tabel settings dan number_sequences (US-32, BR-06).
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->default('general');
            $table->string('key', 80)->unique('uq_settings_key');
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'int', 'bool', 'json'])->default('string');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->comment('order | queue | purchase | shift');
            $table->date('seq_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['name', 'seq_date'], 'uq_seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('settings');
    }
};

