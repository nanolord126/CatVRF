<?php declare(strict_types=1);

namespace App\Domains\Sports\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductPurchaseCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(PurchaseCreated $event): void
        {
            try {
                $this->logger->info('Processing purchase commission deduction', [
                    'purchase_id' => $event->purchase->id,
                    'commission_amount' => $event->purchase->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $this->db->transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::lockForUpdate()
                        ->where('tenant_id', $event->purchase->tenant_id)
                        ->firstOrFail();

                    $wallet->decrement('balance', intval($event->purchase->commission_amount * 100));

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => intval($event->purchase->commission_amount * 100),
                        'status' => 'completed',
                        'correlation_id' => $event->correlationId,
                        'metadata' => [
                            'purchase_id' => $event->purchase->id,
                            'studio_id' => $event->purchase->studio_id,
                        ],
                    ]);
                });

                $this->logger->info('Purchase commission deducted successfully', [
                    'purchase_id' => $event->purchase->id,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct purchase commission', [
                    'purchase_id' => $event->purchase->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
