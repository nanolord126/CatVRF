<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Token fields
            $table->string('token_hash', 64)->unique()->comment('SHA-256 hash of the token');
            $table->string('token_type')->default('bearer')->comment('Token type: bearer, api_key, jwt');
            $table->string('scope')->default('telemetry:write,commands:read')->comment('Token permissions');
            $table->timestamp('expires_at')->nullable()->comment('Token expiration (null = long-lived)');
            $table->timestamp('last_used_at')->nullable()->comment('Last usage timestamp');
            $table->string('last_used_ip')->nullable()->comment('Last IP address used');
            $table->string('last_used_user_agent')->nullable()->comment('Last user agent');
            $table->boolean('is_revoked')->default(false)->comment('Whether token is revoked');
            $table->timestamp('revoked_at')->nullable()->comment('Revocation timestamp');
            $table->string('revocation_reason')->nullable()->comment('Reason for revocation');
            
            // Security metadata
            $table->string('jti')->nullable()->comment('JWT ID if token is JWT');
            $table->json('metadata')->nullable()->comment('Additional token metadata');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast lookups
            $table->index(['iot_device_id', 'is_revoked']);
            $table->index(['tenant_id', 'is_revoked']);
            $table->index('token_hash');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_device_tokens');
    }
};
