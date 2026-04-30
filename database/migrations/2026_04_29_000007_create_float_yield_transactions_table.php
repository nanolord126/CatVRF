<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create float_yield_transactions table
 *
 * Tracks daily float yield calculations for CatFloat monetization.
 * Calculates platform revenue from locked bonus liquidity and user yield share.
 * Supports different yield strategies and partner fintech integrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('float_yield_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique()->comment('Public UUID');
            $table->uuid('tenant_id')->nullable()->index();
            
            // User and batch association
            $table->uuid('user_id')->index()->comment('User who owns the locked bonuses');
            $table->uuid('locked_batch_id')->index()->comment('Associated locked bonus batch');
            $table->uuid('bonus_wallet_id')->index()->comment('Associated bonus wallet');
            
            // Yield calculation date
            $table->date('yield_date')->index()->comment('Date for which yield was calculated');
            
            // Base amounts (in kopecks)
            $table->integer('locked_balance_start')->comment('Locked balance at start of day');
            $table->integer('locked_balance_end')->comment('Locked balance at end of day');
            $table->integer('average_locked_balance')->comment('Average locked balance for yield calc');
            
            // Yield rates (annual percentage)
            $table->decimal('platform_yield_rate', 5, 4)->default(0.1800)->comment('Platform annual yield (e.g., 18%)');
            $table->decimal('user_yield_rate', 5, 4)->default(0.0025)->comment('User daily yield rate (e.g., 0.25%)');
            $table->decimal('effective_yield_rate', 5, 4)->comment('Effective rate after adjustments');
            
            // Yield amounts (in kopecks)
            $table->integer('platform_yield')->comment('Platform revenue from this batch');
            $table->integer('user_yield')->comment('User share of yield (visible in app)');
            $table->integer('total_yield')->comment('Total yield generated');
            
            // Yield source
            $table->string('yield_source', 50)->default('partner_lending')->comment('partner_lending, treasury, defi');
            $table->string('partner_name', 50)->nullable()->comment('Partner fintech (Tinkoff, Tochka, etc.)');
            $table->uuid('partner_transaction_id')->nullable()->comment('External transaction ID');
            
            // Status
            $table->string('status', 20)->default('calculated')->comment('calculated, claimed, distributed');
            $table->timestamp('calculated_at')->comment('When yield was calculated');
            $table->timestamp('claimed_at')->nullable()->comment('When user claimed their yield');
            $table->timestamp('distributed_at')->nullable()->comment('When yield was distributed to wallet');
            
            // User visibility
            $table->boolean('is_visible_to_user')->default(true)->comment('Show yield to user in app');
            $table->boolean('has_been_viewed')->default(false)->comment('User has viewed this yield');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['locked_batch_id', 'yield_date']);
            $table->index(['user_id', 'yield_date']);
            $table->index(['yield_date', 'status']);
            $table->index(['platform_yield', 'user_yield']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('float_yield_transactions');
    }
};
