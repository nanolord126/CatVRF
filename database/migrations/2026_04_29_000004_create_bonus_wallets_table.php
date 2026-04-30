<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create bonus_wallets table
 * 
 * Tracks bonus balance per user separately from main wallet.
 * Provides clear separation of bonus funds from real money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique()->comment('Public UUID');
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('business_group_id')->nullable()->index();
            
            // User association
            $table->uuid('user_id')->unique()->index()->comment('User who owns this bonus wallet');
            $table->uuid('main_wallet_id')->nullable()->index()->comment('Link to main wallet');
            
            // Balances
            $table->integer('available_balance')->default(0)->comment('Available bonus balance in kopecks');
            $table->integer('pending_balance')->default(0)->comment('Pending (held) bonus balance');
            $table->integer('total_earned')->default(0)->comment('Total bonuses ever earned');
            $table->integer('total_spent')->default(0)->comment('Total bonuses spent');
            $table->integer('total_withdrawn')->default(0)->comment('Total bonuses withdrawn (B2B only)');
            
            // User type and permissions
            $table->string('user_type', 20)->default('b2c')->comment('b2c or b2b');
            $table->string('tier', 20)->nullable()->comment('gold, platinum for B2B');
            $table->boolean('can_withdraw')->default(false)->comment('Can withdraw bonuses to real money');
            
            // Limits
            $table->integer('max_balance')->default(1000000)->comment('Maximum bonus balance allowed');
            $table->integer('max_withdrawal_percentage')->default(100)->comment('Max % of bonuses that can be withdrawn');
            
            // Statistics
            $table->integer('transaction_count')->default(0)->comment('Total transaction count');
            $table->timestamp('last_transaction_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'user_type', 'can_withdraw']);
            $table->index(['available_balance', 'pending_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_wallets');
    }
};
