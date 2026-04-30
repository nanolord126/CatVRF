<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create ad_campaigns table for Advertising vertical
 *
 * Stores advertising campaigns with budget tracking, targeting criteria,
 * and pricing models. Tenant-scoped with correlation_id tracing.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft')->index();
            $table->unsignedBigInteger('budget')->default(0)->comment('Budget in cents');
            $table->unsignedBigInteger('spent')->default(0)->comment('Spent amount in cents');
            $table->enum('pricing_model', ['cpm', 'cpc', 'cpa', 'flat'])->default('cpm');
            $table->json('targeting_criteria')->nullable()->comment('Targeting rules and filters');
            $table->timestamp('start_at')->nullable()->index();
            $table->timestamp('end_at')->nullable()->index();
            $table->json('tags')->nullable()->comment('Campaign tags for filtering');
            $table->json('metadata')->nullable()->comment('Additional campaign metadata');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('business_group_id')
                ->references('id')
                ->on('business_groups')
                ->onDelete('set null');

            // Composite indexes for common queries
            $table->index(['tenant_id', 'status'], 'ad_campaigns_tenant_status_idx');
            $table->index(['tenant_id', 'start_at', 'end_at'], 'ad_campaigns_tenant_dates_idx');
            $table->index(['status', 'start_at', 'end_at'], 'ad_campaigns_status_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
