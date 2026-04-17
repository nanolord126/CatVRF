<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create quota_limits table
 * 
 * Production 2026 CANON - Tenant Quota Configuration
 * 
 * Stores per-tenant quota limits for different resource types.
 * Supports:
 * - Per-tenant custom limits
 * - Per-vertical limits
 * - Time-based limits (hourly, daily, monthly)
 * - Plan-based default limits
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quota_limits', function (Blueprint $table) {
            $table->id();
            
            // Tenant identification
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            
            // Resource type and scope
            $table->string('resource_type', 50)->index(); // ai_tokens, llm_requests, slot_holds, etc.
            $table->string('vertical_code', 50)->nullable()->index(); // medical, beauty, etc.
            
            // Time period for the limit
            $table->enum('period', ['hourly', 'daily', 'monthly'])->default('hourly')->index();
            
            // Limit configuration
            $table->unsignedBigInteger('limit')->default(0);
            $table->unsignedBigInteger('soft_limit')->nullable(); // Warning threshold (e.g., 85%)
            $table->boolean('is_hard_limit')->default(true); // Block on exceed or just warn
            
            // Plan and metadata
            $table->string('plan_type', 50)->nullable()->index(); // free, starter, pro, enterprise
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'resource_type', 'period']);
            $table->index(['business_group_id', 'resource_type', 'period']);
            $table->unique(['tenant_id', 'resource_type', 'period', 'vertical_code'], 'unique_tenant_limit');
            $table->unique(['business_group_id', 'resource_type', 'period', 'vertical_code'], 'unique_group_limit');
        });

        // Insert default limits for common resource types
        DB::table('quota_limits')->insert([
            [
                'tenant_id' => null, // Default for all tenants
                'business_group_id' => null,
                'resource_type' => 'ai_tokens',
                'vertical_code' => null,
                'period' => 'hourly',
                'limit' => 100000,
                'soft_limit' => 85000, // 85%
                'is_hard_limit' => true,
                'plan_type' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => null,
                'business_group_id' => null,
                'resource_type' => 'llm_requests',
                'vertical_code' => null,
                'period' => 'hourly',
                'limit' => 1000,
                'soft_limit' => 850,
                'is_hard_limit' => true,
                'plan_type' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => null,
                'business_group_id' => null,
                'resource_type' => 'slot_holds',
                'vertical_code' => null,
                'period' => 'hourly',
                'limit' => 500,
                'soft_limit' => 425,
                'is_hard_limit' => true,
                'plan_type' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => null,
                'business_group_id' => null,
                'resource_type' => 'geo_queries',
                'vertical_code' => null,
                'period' => 'hourly',
                'limit' => 10000,
                'soft_limit' => 8500,
                'is_hard_limit' => true,
                'plan_type' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => null,
                'business_group_id' => null,
                'resource_type' => 'payment_attempts',
                'vertical_code' => null,
                'period' => 'hourly',
                'limit' => 50,
                'soft_limit' => 42,
                'is_hard_limit' => true,
                'plan_type' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_limits');
    }
};
