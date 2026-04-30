<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_encryption_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key_name')->unique();
            $table->text('encrypted_key');
            $table->enum('algorithm', ['aes-256-gcm', 'aes-256-cbc'])->default('aes-256-gcm');
            $table->enum('status', ['active', 'rotated', 'revoked'])->default('active');
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('rotated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_encryption_keys');
    }
};
