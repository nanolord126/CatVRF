<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create audit_logs table for centralized audit logging
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * 
 * This migration creates the main audit log table that stores all mutations
 * across the platform in compliance with 152-FZ and FZ-323 requirements.
 * 
 * Features:
 * - Immutable audit trail with correlation IDs
 * - Tenant and business group isolation
 * - Device fingerprinting for fraud detection
 * - Optimized indexes for query performance
 * - JSON storage for old/new values
 * 
 * @see \App\Models\AuditLog
 * @see \App\Services\AuditService
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // UUID for external references and API responses
            $table->uuid('uuid')->unique();
            
            // Multi-tenancy support
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->onDelete('set null')
                ->index();
            
            // Business group for B2B scenarios
            $table->foreignId('business_group_id')
                ->nullable()
                ->constrained('business_groups')
                ->onDelete('set null')
                ->index();
            
            // User who performed the action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->index();
            
            // Action performed (e.g., 'created', 'updated', 'deleted', 'payment_init')
            $table->string('action', 100)->index();
            
            // Entity type being audited (fully qualified class name)
            $table->string('subject_type', 255)->index();
            
            // Entity ID
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            
            // Composite index for subject queries
            $table->index(['subject_type', 'subject_id'], 'audit_subject_composite');
            
            // Old values before mutation (JSON)
            $table->json('old_values')->nullable();
            
            // New values after mutation (JSON)
            $table->json('new_values')->nullable();
            
            // IP address for security tracking
            $table->string('ip_address', 45)->nullable()->index();
            
            // Device fingerprint for fraud detection (SHA256 hash)
            $table->string('device_fingerprint', 64)->nullable()->index();
            
            // Correlation ID for distributed tracing
            $table->string('correlation_id', 36)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            
            // Composite indexes for common query patterns
            $table->index(['tenant_id', 'created_at'], 'audit_tenant_time');
            $table->index(['user_id', 'created_at'], 'audit_user_time');
            $table->index(['action', 'created_at'], 'audit_action_time');
            
            // Index for correlation ID queries (distributed tracing)
            $table->index('correlation_id');
        });
        
        // Add comment for documentation
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->comment('Centralized audit log for all platform mutations - 152-FZ compliant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
