<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bonus_transactions')) {
            Schema::create('bonus_transactions', static function (Blueprint $table): void {
                $table->comment('Bonus transactions — referral, turnover, promo, loyalty');

                $table->id();
                $table->uuid('uuid')->unique()->index()->comment('Public UUID for external references');
                $table->unsignedBigInteger('tenant_id')->index()->comment('Tenant scoping');
                $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('Branch scoping');
                $table->unsignedBigInteger('wallet_id')->index()->comment('FK to wallets');
                $table->unsignedBigInteger('user_id')->nullable()->index()->comment('User who receives bonus');
                $table->string('type', 50)->comment('referral, turnover, promo, loyalty, migration');
                $table->unsignedBigInteger('amount')->comment('Amount in kopecks (integer, never float)');
                $table->string('status', 30)->default('pending')->comment('pending, credited, withdrawn, expired');
                $table->string('source_type', 100)->nullable()->comment('Bonus source: order, referral, campaign');
                $table->unsignedBigInteger('source_id')->nullable()->comment('Source entity ID');
                $table->string('correlation_id', 36)->nullable()->index()->comment('Audit correlation ID');
                $table->json('meta')->nullable()->comment('Extra data: referral code, campaign id, etc.');
                $table->json('tags')->nullable()->comment('Tags for analytics and filtering');
                $table->timestamp('credited_at')->nullable()->comment('When bonus was credited');
                $table->timestamp('expires_at')->nullable()->index()->comment('Bonus expiry');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status'], 'idx_tenant_status');
                $table->index(['wallet_id', 'type'], 'idx_wallet_type');
                $table->index(['user_id', 'created_at'], 'idx_user_created');
            });
        }

        if (! Schema::hasTable('promo_uses')) {
            Schema::create('promo_uses', static function (Blueprint $table): void {
                $table->comment('Promo campaign uses — tracks who used which promo code and when');

                $table->id();
                $table->unsignedBigInteger('promo_campaign_id')->index()->comment('FK to promo_campaigns');
                $table->unsignedBigInteger('tenant_id')->index()->comment('Tenant scoping');
                $table->unsignedBigInteger('user_id')->index()->comment('User who applied the promo');
                $table->string('source_type', 100)->nullable()->comment('order, appointment, booking');
                $table->unsignedBigInteger('source_id')->nullable()->comment('Source entity ID');
                $table->unsignedBigInteger('discount_amount')->default(0)->comment('Applied discount in kopecks');
                $table->string('correlation_id', 36)->nullable()->index()->comment('Audit correlation ID');
                $table->timestamp('used_at')->useCurrent()->comment('When promo was applied');
                $table->timestamps();

                $table->index(['promo_campaign_id', 'user_id'], 'idx_campaign_user');
                $table->index(['tenant_id', 'used_at'], 'idx_tenant_used_at');
            });
        }

        if (! Schema::hasTable('promo_audit_logs')) {
            Schema::create('promo_audit_logs', static function (Blueprint $table): void {
                $table->comment('Audit log for all promo campaign actions');

                $table->id();
                $table->unsignedBigInteger('promo_campaign_id')->index();
                $table->string('action', 50)->comment('created, applied, cancelled, budget_exhausted');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->json('details')->nullable();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->timestamps();

                $table->index(['promo_campaign_id', 'action'], 'idx_promo_action');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_audit_logs');
        Schema::dropIfExists('promo_uses');
        Schema::dropIfExists('bonus_transactions');
    }
};
