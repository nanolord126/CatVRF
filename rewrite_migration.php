<?php
// Rewrite the migration with correct columns
$file = __DIR__ . '/database/migrations/2026_03_25_000001_create_wallet_payment_promo_tables.php';
$content = <<<'PHP'
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tenant_wallets - renamed from wallets to avoid conflict with bavix/laravel-wallet
        if (!Schema::hasTable('tenant_wallets')) {
            Schema::create('tenant_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable();
                $table->bigInteger('current_balance')->default(0)->comment('Balance in kopeks');
                $table->bigInteger('hold_amount')->default(0)->comment('Hold amount in kopeks');
                $table->bigInteger('cached_balance')->default(0);
                $table->uuid('uuid')->nullable()->unique();
                $table->string('correlation_id')->nullable()->index();
                $table->json('meta')->nullable();
                $table->json('tags')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'user_id']);
                $table->comment('Tenant wallets for balance management');
            });
        }

        // Balance transactions
        if (!Schema::hasTable('balance_transactions')) {
            Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id');
                $table->enum('type', ['deposit', 'withdrawal', 'commission', 'bonus', 'refund', 'payout']);
                $table->bigInteger('amount')->comment('Amount in kopeks');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
                $table->string('correlation_id')->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['wallet_id', 'type']);
                $table->comment('Balance transaction history');
            });
        }

        // Payment transactions
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->bigInteger('amount')->comment('Amount in kopeks');
                $table->enum('status', ['pending', 'authorized', 'captured', 'refunded', 'failed'])->default('pending');
                $table->string('provider_code')->nullable()->index();
                $table->string('provider_payment_id')->nullable();
                $table->string('idempotency_key')->nullable()->unique();
                $table->boolean('hold')->default(true);
                $table->float('fraud_ml_score')->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Payment transaction ledger');
            });
        }

        // Payment idempotency records
        if (!Schema::hasTable('payment_idempotency_records')) {
            Schema::create('payment_idempotency_records', function (Blueprint $table) {
                $table->id();
                $table->string('operation');
                $table->string('idempotency_key')->unique();
                $table->unsignedBigInteger('merchant_id')->nullable();
                $table->string('payload_hash');
                $table->json('response_data')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();
                $table->index(['idempotency_key', 'expires_at']);
                $table->comment('Prevent duplicate payments');
            });
        }

        // Promo campaigns
        if (!Schema::hasTable('promo_campaigns')) {
            Schema::create('promo_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->unsignedBigInteger('business_group_id')->nullable();
                $table->enum('type', ['discount_percent', 'fixed_amount', 'bundle', 'buy_x_get_y', 'gift_card', 'referral_bonus']);
                $table->string('code')->unique()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->bigInteger('budget')->comment('Budget in kopeks');
                $table->bigInteger('spent_budget')->default(0)->comment('Spent in kopeks');
                $table->integer('max_uses_per_user')->nullable();
                $table->integer('max_uses_total')->nullable();
                $table->bigInteger('min_order_amount')->default(0)->comment('Min order in kopeks');
                $table->json('applicable_verticals')->nullable();
                $table->json('applicable_categories')->nullable();
                $table->enum('status', ['active', 'paused', 'exhausted', 'expired'])->default('active');
                $table->string('correlation_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Promotional campaigns');
            });
        }

        // Promo uses
        if (!Schema::hasTable('promo_uses')) {
            Schema::create('promo_uses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('promo_campaign_id');
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->bigInteger('discount_amount')->comment('Discount in kopeks');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamp('used_at')->useCurrent();
                $table->timestamps();
                $table->index(['promo_campaign_id', 'user_id']);
                $table->comment('Promo usage history');
            });
        }

        // Referrals
        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('referrer_id');
                $table->unsignedBigInteger('referee_id')->nullable();
                $table->string('referral_code')->unique()->index();
                $table->text('referral_link');
                $table->enum('status', ['pending', 'registered', 'qualified', 'rewarded', 'expired'])->default('pending');
                $table->string('source_platform')->nullable()->comment('Migration source: Dikidi, Flowwow, etc');
                $table->timestamp('migrated_at')->nullable();
                $table->bigInteger('turnover_threshold')->default(5000000)->comment('5000 rubles in kopeks');
                $table->bigInteger('spent_threshold')->default(1000000)->comment('1000 rubles in kopeks');
                $table->bigInteger('bonus_amount')->default(20000)->comment('200 rubles in kopeks');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->index(['referrer_id', 'status']);
                $table->comment('Referral program');
            });
        }

        // Referral rewards
        if (!Schema::hasTable('referral_rewards')) {
            Schema::create('referral_rewards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('referral_id');
                $table->unsignedBigInteger('recipient_id');
                $table->enum('recipient_type', ['referrer', 'referee']);
                $table->bigInteger('amount')->comment('Amount in kopeks');
                $table->enum('type', ['referral_bonus', 'turnover_bonus', 'migration_bonus']);
                $table->enum('status', ['pending', 'credited', 'withdrawn'])->default('pending');
                $table->timestamp('credited_at')->nullable();
                $table->timestamp('withdrawn_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->index(['referral_id', 'status']);
                $table->comment('Referral rewards');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('promo_uses');
        Schema::dropIfExists('promo_campaigns');
        Schema::dropIfExists('payment_idempotency_records');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('tenant_wallets');
    }
};
PHP;
file_put_contents($file, $content);
echo "Migration rewritten\n";