<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Security events table (PostgreSQL mirror of ClickHouse table).
     * ClickHouse table `security_events` is created separately via ClickHouse migration.
     * This table serves as fallback and for Filament admin dashboard queries.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->index()->comment('suspicious_login, rate_limit_exceeded, fraud_attempt, brute_force, 2fa_failed, etc.');
            $table->enum('severity', ['info', 'warning', 'high', 'critical'])->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint')->nullable()->comment('SHA256 hash');
            $table->json('details')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->float('fraud_score')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'event_type', 'created_at']);
            $table->index(['severity', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
