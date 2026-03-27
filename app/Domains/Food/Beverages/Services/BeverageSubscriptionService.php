<?php

declare(strict_types=1);

namespace App\Domains\Food\Beverages\Services;

use App\Domains\Food\Beverages\Models\BeverageSubscription;
use App\Domains\Food\Beverages\Models\BeverageShop;
use App\Services\WalletService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

final readonly class BeverageSubscriptionService
{
    /**
     * @param WalletService $walletService
     * @param FraudControlService $fraudService
     */
    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraudService
    ) {}

    /**
     * Subscribe a user to a beverage plan (daily coffee, monthly tea, etc.)
     * 
     * @param array $data
     * @param string|null $correlationId
     * @return BeverageSubscription
     * @throws Exception
     */
    public function subscribe(array $data, ?string $correlationId = null): BeverageSubscription
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        
        Log::channel('audit')->info('Initializing subscription purchase', [
            'correlation_id' => $correlationId,
            'user_id' => $data['user_id'],
            'plan_type' => $data['plan_type'],
        ]);

        return DB::transaction(function () use ($data, $correlationId) {
            // 1. Fraud Check
            $this->fraudService->check('beverage_subscription_purchase', [
                'user_id' => $data['user_id'],
                'tenant_id' => $data['tenant_id'],
                'plan_type' => $data['plan_type'],
                'correlation_id' => $correlationId,
            ]);

            // 2. Process payment (from user wallet)
            $this->walletService->debit([
                'user_id' => $data['user_id'],
                'amount' => $data['price'],
                'type' => 'subscription',
                'reason' => "Purchase of '{$data['plan_type']}' beverage plan",
                'correlation_id' => $correlationId,
            ]);

            // 3. Create the subscription
            $subscription = BeverageSubscription::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'shop_id' => $data['shop_id'],
                'plan_type' => $data['plan_type'],
                'price' => $data['price'],
                'limit_count' => $data['limit_count'],
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays($data['validity_days'] ?? 30),
                'auto_renew' => $data['auto_renew'] ?? true,
                'status' => 'active',
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Beverage subscription successfully activated', [
                'subscription_id' => $subscription->id,
                'uuid' => $subscription->uuid,
                'expires_at' => $subscription->expires_at->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            return $subscription;
        });
    }

    /**
     * Use one item from a subscription.
     */
    public function redeemItem(int $subscriptionId, ?string $correlationId = null): void
    {
        DB::transaction(function () use ($subscriptionId, $correlationId) {
            $subscription = BeverageSubscription::lockForUpdate()->findOrFail($subscriptionId);

            if ($subscription->status !== 'active' || $subscription->expires_at->isPast()) {
                throw new Exception("Subscription is no longer active.");
            }

            if (!$subscription->hasLimitLeft()) {
                throw new Exception("Limit for this subscription reached.");
            }

            $subscription->incrementUsage();

            Log::channel('audit')->info('Redeemed item from beverage subscription', [
                'subscription_id' => $subscriptionId,
                'used_count' => $subscription->fresh()->used_count,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renew(int $subscriptionId, ?string $correlationId = null): BeverageSubscription
    {
        $correlationId = $correlationId ?? (string) Str::uuid();

        return DB::transaction(function () use ($subscriptionId, $correlationId) {
            $oldSub = BeverageSubscription::findOrFail($subscriptionId);

            if ($oldSub->status !== 'active') {
                throw new Exception("Cannot renew non-active subscription.");
            }

            // Debit user wallet again
            $this->walletService->debit([
                'user_id' => $oldSub->user_id,
                'amount' => $oldSub->price,
                'type' => 'subscription_renewal',
                'reason' => "Renewal of '{$oldSub->plan_type}' beverage plan",
                'correlation_id' => $correlationId,
            ]);

            $oldSub->update([
                'used_count' => 0,
                'expires_at' => $oldSub->expires_at->addDays(30),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Beverage subscription renewed', [
                'subscription_id' => $subscriptionId,
                'new_expiry' => $oldSub->expires_at->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            return $oldSub;
        });
    }
}
