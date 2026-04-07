<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class WellnessMembershipService
{
    public function __construct(
        private \App\Services\FraudControlService $fraud,
        private \App\Services\WalletService $walletService,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

        /**
         * Create/Subscribe user to a membership.
         * @throws \RuntimeException
         */
        public function subscribe(
            int $client_id,
            int $center_id,
            int $service_id,
            string $plan_type,
            int $price,
            string $start_at,
            string $end_at,
            array $benefits = [],
            ?string $correlation_id = null
        ): WellnessMembership {
            $correlationId = $correlation_id ?? (string) Str::uuid();

            $this->logger->info('Wellness Membership Signup Init', [
                'client_id' => $client_id,
                'center_id' => $center_id,
                'plan_type' => $plan_type,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use ($client_id, $center_id, $service_id, $plan_type, $price, $start_at, $end_at, $benefits, $correlationId) {
                // 1. Pre-mutation Fraud Check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'wellness_membership_subscribe', amount: 0, correlationId: $correlationId ?? '');

                // 2. Debit client wallet for the first period (B2C Mode)
                if ($price > 0) {
                     $this->walletService->debit([
                         'amount' => $price,
                         'reason' => "Wellness Membership Signup: {$plan_type}",
                         'correlation_id' => $correlationId,
                     ]);
                }

                return $membership;
            });
        }

        /**
         * Deduct session from membership entitlement.
         */
        public function deductSession(WellnessMembership $membership): bool
        {
            return $this->db->transaction(function () use ($membership) {
                $membership->lockForUpdate()->find($membership->id);

                if ($membership->remaining_sessions <= 0) {
                     throw new \RuntimeException("Unauthorized: No remaining sessions on membership.", 403);
                }

                $membership->remaining_sessions -= 1;
                $membership->save();

                $this->logger->info('Wellness Membership Session Deducted', [
                    'membership_uuid' => $membership->uuid,
                    'remaining' => $membership->remaining_sessions,
                    'correlation_id' => $membership->correlation_id,
                ]);

                return true;
            });
        }

        /**
         * Renewal logic for recurring memberships.
         */
        public function renew(WellnessMembership $membership): bool
        {
            return $this->db->transaction(function () use ($membership) {
                $membership->lockForUpdate()->find($membership->id);

                // Attempt to charge the wallet for the next period
                try {
                    $this->walletService->debit([
                        'amount' => $membership->price_per_period,
                        'reason' => "Wellness Membership Renewal: {$membership->uuid}",
                        'membership_uuid' => $membership->uuid,
                        'correlation_id' => $membership->correlation_id,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Wellness renewal payment failed', [
                        'membership_uuid' => $membership->uuid,
                        'error' => $e->getMessage(),
                        'correlation_id' => $membership->correlation_id,
                    ]);
                    return false;
                }
            });
        }
}
