<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MembershipService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function createMembership(int $gymId, int $memberId, string $type, float $amount, string $correlationId): Membership
        {

            try {
                $gym = Gym::findOrFail($gymId);

                $commissionAmount = round($amount * 0.14, 2);

                $startDate = now();
                $expiresAt = match ($type) {
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

                    $this->logger->info('Membership created', [
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
                $this->logger->error('Failed to create membership', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function cancelMembership(Membership $membership, string $reason, string $correlationId): void
        {

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($membership, $reason, $correlationId) {
                    $membership->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);

                    MembershipExpired::dispatch($membership, $correlationId);

                    $this->logger->info('Membership cancelled', [
                        'membership_id' => $membership->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to cancel membership', [
                    'membership_id' => $membership->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
