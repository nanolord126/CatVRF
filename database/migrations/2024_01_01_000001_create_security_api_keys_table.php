<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('key_id', 36)->unique();
            $table->string('name');
            $table->string('key_hash');
            $table->string('key_preview', 10);
            $table->json('permissions')->nullable();
            $table->json('ip_whitelist')->nullable();
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index('key_hash');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_api_keys');
    }
};
