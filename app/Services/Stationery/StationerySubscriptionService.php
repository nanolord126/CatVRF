<?php

declare(strict_types=1);

namespace App\Services\Stationery;

use App\Models\Stationery\StationerySubscription;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * StationerySubscriptionService.
 * Monthly stationery boxes and B2B recurring supplies.
 * Follows 2026 transaction protocols.
 */
final readonly class StationerySubscriptionService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private string $correlationId = ''
    ) {
        $this->correlationId = $correlationId ?: (string) Str::uuid();
    }

    /**
     * Subscribes a user or office to a stationery box tier.
     */
    public function subscribe(int $userId, string $tier, array $preferences): StationerySubscription
    {
        Log::channel('audit')->info('Attempting to create stationery subscription', [
            'user_id' => $userId,
            'tier' => $tier,
            'correlation_id' => $this->correlationId,
        ]);

        $this->fraud->check([
            'operation' => 'stationery_subscription',
            'user_id' => $userId,
            'tier' => $tier,
            'correlation_id' => $this->correlationId,
        ]);

        $monthlyPrice = $this->resolveTierPrice($tier);

        return DB::transaction(function () use ($userId, $tier, $preferences, $monthlyPrice) {
            $subscription = StationerySubscription::create([
                'user_id' => $userId,
                'tier' => $tier,
                'monthly_price_cents' => $monthlyPrice,
                'preferences' => $preferences,
                'is_active' => true,
                'next_delivery_at' => now()->addMonth(),
                'correlation_id' => $this->correlationId,
            ]);

            // Initial month billing
            $this->wallet->debit($userId, $monthlyPrice, 'Stationery Subscription: ' . $tier, $this->correlationId);

            Log::channel('audit')->info('Stationery subscription active', [
                'subscription_id' => $subscription->id,
                'monthly_price' => $monthlyPrice,
                'correlation_id' => $this->correlationId,
            ]);

            return $subscription;
        });
    }

    /**
     * Cancels a recurring subscription properly.
     */
    public function cancelSubscription(int $subscriptionId): bool
    {
        return DB::transaction(function () use ($subscriptionId) {
            $subscription = StationerySubscription::findOrFail($subscriptionId);
            
            $subscription->update([
                'is_active' => false,
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('Stationery subscription cancelled', [
                'subscription_id' => $subscriptionId,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Resolves subscription box tiers and their pricing.
     */
    private function resolveTierPrice(string $tier): int
    {
        return match ($tier) {
            'Basic' => 250000,   // 2500 RUB
            'Premium' => 500000, // 5000 RUB
            'Office' => 1500000, // 15000 RUB (B2B variant)
            default => 250000,
        };
    }

    /**
     * Lists active subscriptions for automated processing jobs.
     */
    public function getActiveSubscriptions(): \Illuminate\Support\Collection
    {
        return StationerySubscription::where('is_active', true)
            ->where('next_delivery_at', '<=', now())
            ->get();
    }
}
