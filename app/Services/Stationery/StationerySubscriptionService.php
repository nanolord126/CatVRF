<?php declare(strict_types=1);

namespace App\Services\Stationery;





use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Stationery\StationerySubscription;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class StationerySubscriptionService
{

    public function __construct(
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private FraudControlService $fraud,
        private WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

        /**
         * Subscribes a user or office to a stationery box tier.
         */
        public function subscribe(int $userId, string $tier, array $preferences): StationerySubscription
        {
            $correlationId = $this->correlationId();
            $this->logger->channel('audit')->info('Attempting to create stationery subscription', [
                'user_id' => $userId,
                'tier' => $tier,
                'correlation_id' => $correlationId,
            ]);

            $monthlyPrice = $this->resolveTierPrice($tier);
            $actorId = (int) ($this->authManager->id() ?? $userId);

            $this->fraud->check(
                userId: $actorId,
                operationType: 'stationery_subscription',
                amount: $monthlyPrice,
                ipAddress: $this->request->ip(),
                deviceFingerprint: $this->request->header('X-Device-Id'),
                correlationId: $correlationId,
            );

            return $this->db->transaction(function () use ($userId, $tier, $preferences, $monthlyPrice, $correlationId) {
                $subscription = StationerySubscription::create([
                    'user_id' => $userId,
                    'tier' => $tier,
                    'monthly_price_cents' => $monthlyPrice,
                    'preferences' => $preferences,
                    'is_active' => true,
                    'next_delivery_at' => now()->addMonth(),
                    'correlation_id' => $correlationId,
                ]);

                // Initial month billing
                $this->wallet->debit($userId, $monthlyPrice, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $correlationId, null, null, [
                    'subscription_id' => $subscription->id,
                    'monthly_price' => $monthlyPrice,
                    'correlation_id' => $correlationId,
                ]);

                return $subscription;
            });
        }

        /**
         * Cancels a recurring subscription properly.
         */
        public function cancelSubscription(int $subscriptionId): bool
        {
            return $this->db->transaction(function () use ($subscriptionId) {
                $subscription = StationerySubscription::findOrFail($subscriptionId);

                $subscription->update([
                    'is_active' => false,
                    'correlation_id' => $this->correlationId(),
                ]);

                $this->logger->channel('audit')->info('Stationery subscription cancelled', [
                    'subscription_id' => $subscriptionId,
                    'correlation_id' => $this->correlationId(),
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
