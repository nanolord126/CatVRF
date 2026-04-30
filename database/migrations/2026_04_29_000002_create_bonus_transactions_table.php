<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create bonus_transactions table
 * 
 * Records all bonus accruals, holds, spends, and withdrawals.
 * Supports hold period (14 days default) and expiry (1 year default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique()->comment('Public UUID for external references');
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('business_group_id')->nullable()->index();
            
            // Wallet and user
            $table->uuid('wallet_id')->index()->comment('Linked wallet');
            $table->uuid('user_id')->index()->comment('User who owns the bonus');
            
            // Transaction details
            $table->string('type', 50)->index()->comment('award, spend, withdraw, expire, unlock');
            $table->string('rule_code', 100)->nullable()->index()->comment('Rule that generated this bonus');
            $table->integer('amount')->comment('Amount in kopecks/cents');
            $table->integer('balance_after')->nullable()->comment('Bonus balance after this transaction');
            
            // Status workflow
            $table->string('status', 50)->default('pending')->index()->comment('pending, credited, spent, expired, withdrawn');
            
            // Source tracking
            $table->string('source_type')->nullable()->comment('order, referral, ai_constructor, etc.');
            $table->uuid('source_id')->nullable()->index();
            
            // Hold and expiry
            $table->timestamp('hold_until')->nullable()->index()->comment('When pending bonuses become available');
            $table->timestamp('credited_at')->nullable()->comment('When bonus was credited to wallet');
            $table->timestamp('expires_at')->nullable()->index()->comment('When bonus expires');
            
            // Withdrawal (B2B only)
            $table->uuid('withdrawal_request_id')->nullable()->comment('Reference to withdrawal request');
            $table->string('withdrawal_method')->nullable()->comment('bank_transfer, card, etc.');
            $table->string('withdrawal_status')->nullable()->comment('pending, processing, completed, rejected');
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status', 'expires_at']);
            $table->index(['wallet_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['hold_until', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
    }
};
