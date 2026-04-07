<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductMembershipCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(MembershipCreated $event): void
        {
            try {
                $gym = $event->membership->gym;
                $commissionAmount = (int) ($event->membership->commission_amount * 100);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($gym, $commissionAmount, $event) {
                    $wallet = Wallet::where('tenant_id', $gym->tenant_id)->lockForUpdate()->first();
                    if ($wallet) {
                        $wallet->decrement('balance', $commissionAmount);
                    }

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id ?? null,
                        'tenant_id' => $gym->tenant_id,
                        'type' => 'commission',
                        'amount' => -$commissionAmount,
                        'description' => "Membership commission #{$event->membership->id}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Membership commission deducted', [
                        'membership_id' => $event->membership->id,
                        'gym_id' => $gym->id,
                        'commission_amount' => $event->membership->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct membership commission', [
                    'membership_id' => $event->membership->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
