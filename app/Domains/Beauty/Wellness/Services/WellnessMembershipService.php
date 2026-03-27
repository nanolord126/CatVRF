<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Services;

use App\Domains\Beauty\Wellness\Models\WellnessCenter;
use App\Domains\Beauty\Wellness\Models\WellnessMembership;
use App\Domains\Beauty\Wellness\Models\WellnessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WellnessMembershipService - B2B/B2C logic for recurring wellness subscriptions.
 */
final readonly class WellnessMembershipService
{
    public function __construct(
        private readonly \App\Services\FraudControlService $fraudControl,
        private readonly \App\Services\WalletService $walletService,
    ) {}

    /**
     * Create/Subscribe user to a membership.
     * @throws \Exception
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

        Log::channel('audit')->info('Wellness Membership Signup Init', [
            'client_id' => $client_id,
            'center_id' => $center_id,
            'plan_type' => $plan_type,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($client_id, $center_id, $service_id, $plan_type, $price, $start_at, $end_at, $benefits, $correlationId) {
            // 1. Pre-mutation Fraud Check
            $this->fraudControl->check([
                'user_id' => $client_id,
                'operation' => 'wellness_membership_subscribe',
                'amount' => $price,
                'correlation_id' => $correlationId,
            ]);

            // 2. Debit client wallet for the first period (B2C Mode)
            if ($price > 0) {
                 $this->walletService->debit([
                     'amount' => $price,
                     'reason' => "Wellness Membership Signup: {$plan_type}",
                     'user_id' => $client_id,
                     'correlation_id' => $correlationId,
                 ]);
            }

            // 3. Create Membership Record
            $membership = WellnessMembership::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'center_id' => $center_id,
                'client_id' => $client_id,
                'service_id' => $service_id,
                'plan_type' => $plan_type,
                'start_at' => $start_at,
                'end_at' => $end_at,
                'is_active' => true,
                'price_per_period' => $price,
                'remaining_sessions' => match($plan_type) {
                     'annual' => 120,
                     'monthly' => 10,
                     'bundle' => 5,
                     default => 0,
                },
                'benefits_json' => $benefits,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Wellness Membership Active', [
                'membership_uuid' => $membership->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $membership;
        });
    }

    /**
     * Deduct session from membership entitlement.
     */
    public function deductSession(WellnessMembership $membership): bool
    {
        return DB::transaction(function () use ($membership) {
            $membership->lockForUpdate()->find($membership->id);

            if ($membership->remaining_sessions <= 0) {
                 throw new \Exception("Unauthorized: No remaining sessions on membership.", 403);
            }

            $membership->remaining_sessions -= 1;
            $membership->save();

            Log::channel('audit')->info('Wellness Membership Session Deducted', [
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
        return DB::transaction(function () use ($membership) {
            $membership->lockForUpdate()->find($membership->id);

            // Attempt to charge the wallet for the next period
            try {
                $this->walletService->debit([
                    'amount' => $membership->price_per_period,
                    'reason' => "Wellness Membership Renewal: {$membership->uuid}",
                    'user_id' => $membership->client_id,
                    'correlation_id' => $membership->correlation_id,
                ]);

                // Update timeframe
                $membership->start_at = now();
                $membership->end_at = now()->addMonth(); // Standard monthly renewal
                $membership->remaining_sessions += 10;
                $membership->save();

                return true;
            } catch (\Exception $e) {
                $membership->is_active = false;
                $membership->save();
                Log::channel('audit')->error('Wellness Membership Renewal Failed: Insufficient Balance', [
                     'membership_uuid' => $membership->uuid,
                     'error' => $e->getMessage(),
                ]);
                return false;
            }
        });
    }
}
