<?php declare(strict_types=1);

namespace App\Domains\Fitness\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Fitness\Events\MembershipCreated;
use App\Domains\Fitness\Models\Gym;
use App\Domains\Fitness\Models\Membership;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class MembershipService
{
    public function createMembership(int $gymId, int $memberId, string $type, float $amount, string $correlationId): Membership
    {


        try {
            $gym = Gym::findOrFail($gymId);
            
            $commissionAmount = round($amount * 0.14, 2);
            
            $startDate = now();
            $expiresAt = match ($type) {
                'monthly' => $startDate->addMonth(),
                'quarterly' => $startDate->addMonths(3),
                'annual' => $startDate->addYear(),
                default => $startDate->addMonth(),
            };

            $membership = $this->db->transaction(function () use ($gym, $memberId, $type, $amount, $commissionAmount, $startDate, $expiresAt, $correlationId) {
                $membership = Membership::create([
                    'tenant_id' => $gym->tenant_id,
                    'gym_id' => $gym->id,
                    'member_id' => $memberId,
                    'type' => $type,
                    'amount' => $amount,
                    'commission_amount' => $commissionAmount,
                    'started_at' => $startDate,
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                    'auto_renewal' => true,
                    'correlation_id' => $correlationId,
                ]);

                MembershipCreated::dispatch($membership, $correlationId);

                $this->log->channel('audit')->info('Membership created', [
                    'membership_id' => $membership->id,
                    'gym_id' => $gym->id,
                    'member_id' => $memberId,
                    'amount' => $amount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $correlationId,
                ]);

                return $membership;
            });

            return $membership;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to create membership', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function cancelMembership(Membership $membership, string $reason, string $correlationId): void
    {


        try {
            $this->db->transaction(function () use ($membership, $reason, $correlationId) {
                $membership->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                MembershipExpired::dispatch($membership, $correlationId);

                $this->log->channel('audit')->info('Membership cancelled', [
                    'membership_id' => $membership->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to cancel membership', [
                'membership_id' => $membership->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
