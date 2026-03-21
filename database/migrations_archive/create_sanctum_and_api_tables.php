<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personal Access Tokens для Sanctum
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->comment('Sanctum Personal Access Tokens для API аутентификации');
        });

        // API Keys для внешних интеграций
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tenant_id')->index(); // No foreign key for now
            $table->uuid('key_id')->unique()->index();
            $table->string('name');
            $table->string('key_hash', 64)->unique()->index();
            $table->string('key_preview', 20)->comment('First 10 chars for display');
            $table->json('permissions')->nullable()->comment('Allowed endpoints/resources');
            $table->json('ip_whitelist')->nullable()->comment('IP addresses that can use this key');
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamps();

            $table->comment('API Keys для платформных интеграций');
            $table->index(['tenant_id', 'status']);
        });

        // API Key Audit Log
        Schema::create('api_key_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('api_key_id')->index(); // No foreign key
            $table->enum('action', ['created', 'used', 'revoked', 'expired', 'rotated']);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();

            $table->comment('Лог использования API ключей');
            $table->index(['api_key_id', 'action']);
        });

        // Rate Limit Records (для sliding window алгоритма)
        Schema::create('rate_limit_records', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tenant_id')->index(); // No foreign key
            $table->string('user_id', 36)->nullable();
            $table->string('ip_address', 45);
            $table->string('endpoint', 255)->index();
            $table->integer('request_count')->default(1);
            $table->timestamp('first_request_at');
            $table->timestamp('last_request_at')->useCurrentOnUpdate();
            $table->timestamp('window_reset_at');
            $table->string('correlation_id', 36)->nullable();

            $table->comment('Sliding window rate limiting records');
            $table->index(['tenant_id', 'endpoint', 'window_reset_at']);
            $table->index(['ip_address', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_audit_logs');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('rate_limit_records');
        Schema::dropIfExists('personal_access_tokens');
    }
};
