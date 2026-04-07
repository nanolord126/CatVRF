<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Services;



use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VeganSubscriptionService
{


    public function __construct(private readonly FraudControlService $fraud,
            private readonly VeganProductService $productService,
            private readonly WalletService $walletService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Subscribe user to a monthly or weekly curated vegan box.
         * Layer: Domain Service (3/9 extension)
         */
        public function subscribe(int $userId, int $boxId, string $planType, ?string $correlationId = null): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-3: New Vegan Subscription Request', [
                'user' => $userId,
                'box' => $boxId,
                'plan' => $planType,
                'correlation_id' => $correlationId,
            ]);

            // 1. Validation Logic
            $box = VeganSubscriptionBox::where('id', $boxId)->where('is_active', true)->firstOrFail();

            // 2. Fraud Check (check if user is creating too many subs)
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vegan_subscription_create', amount: 0, correlationId: $correlationId ?? '');

            // 3. Persist via transaction
            $this->db->transaction(function () use ($userId, $box, $planType, $correlationId) {
                // Check if active subscription already exists
                $existing = $this->db->table('vegan_subscriptions')
                    ->where('user_id', $userId)
                    ->where('vegan_subscription_box_id', $box->id)
                    ->where('status', 'active')
                    ->exists();

                if ($existing) {
                    $this->logger->error('LAYER-3: Duplicate Subscription Error', [
                        'user' => $userId,
                        'box' => $box->id,
                        'correlation_id' => $correlationId,
                    ]);
                    throw new \LogicException("Active subscription for box #{$box->id} already exists.");
                }

                // Create record
                $this->db->table('vegan_subscriptions')->insert([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'user_id' => $userId,
                    'vegan_subscription_box_id' => $box->id,
                    'plan_type' => $planType,
                    'status' => 'active',
                    'amount_monthly' => $box->price_monthly,
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->info('LAYER-3: Vegan Subscription CREATED', [
                    'user' => $userId,
                    'box' => $box->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Renew all active subscriptions and trigger warehouse tasks.
         * Typically called via Cron or Periodic Job.
         */
        public function renewBatch(string $correlationId = ''): int
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            $this->logger->info('LAYER-3: Renewal Batch START', ['correlation_id' => $correlationId]);

            $activeSubs = $this->db->table('vegan_subscriptions')
                ->where('status', 'active')
                ->whereDate('next_delivery_at', '<=', now())
                ->get();

            $count = 0;
            foreach ($activeSubs as $sub) {
                try {
                    $this->renewSingle($sub, $correlationId);
                    $count++;
                } catch (\Throwable $e) {
                    $this->logger->error("LAYER-3: Renewal FAILED for sub #{$sub->id}", [
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            return $count;
        }

        /**
         * Renew a single subscription with payment check and stock reservation.
         */
        private function renewSingle($sub, string $correlationId): void
        {
            $this->db->transaction(function () use ($sub, $correlationId) {
                $box = VeganSubscriptionBox::findOrFail($sub->vegan_subscription_box_id);

                // 1. Charge wallet for subscription renewal
                $this->walletService->debit(
                    walletId: (int) $sub->user_id,
                    amount: $box->price_monthly,
                    type: \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL,
                    correlationId: $correlationId,
                );

                // 2. Reservation of products inside the box
                foreach ($box->included_product_ids as $productId) {
                    $this->productService->adjustStock(
                        productId: (int) $productId,
                        delta: -1,
                        reason: "Subscription renewal #{$sub->id}",
                        correlationId: $correlationId
                    );
                }

                // 3. Update subscription next date
                $this->db->table('vegan_subscriptions')
                    ->where('id', $sub->id)
                    ->update([
                        'last_delivery_at' => now(),
                        'next_delivery_at' => now()->addMonth(),
                        'correlation_id' => $correlationId,
                    ]);
            });
        }
}
