<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class RefundMembershipCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(MembershipExpired $event): void
        {
            try {
                if ($event->membership->status !== 'cancelled') {
                    return;
                }

                $gym = $event->membership->gym;
                $commissionAmount = (int) ($event->membership->commission_amount * 100);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($gym, $commissionAmount, $event) {
                    $wallet = Wallet::where('tenant_id', $gym->tenant_id)->lockForUpdate()->first();
                    if ($wallet) {
                        $wallet->increment('balance', $commissionAmount);
                    }

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id ?? null,
                        'tenant_id' => $gym->tenant_id,
                        'type' => 'refund',
                        'amount' => $commissionAmount,
                        'description' => "Membership commission refund #{$event->membership->id}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Membership commission refunded', [
                        'membership_id' => $event->membership->id,
                        'gym_id' => $gym->id,
                        'commission_amount' => $event->membership->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to refund membership commission', [
                    'membership_id' => $event->membership->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
