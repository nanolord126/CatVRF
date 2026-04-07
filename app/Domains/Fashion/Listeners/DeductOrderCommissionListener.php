<?php declare(strict_types=1);

namespace App\Domains\Fashion\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductOrderCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(OrderPlaced $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $wallet = Wallet::where('tenant_id', $event->order->tenant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $wallet) {
                        throw new \RuntimeException('Wallet not found');
                    }

                    $commissionAmount = intval($event->order->commission_amount * 100);

                    $wallet->decrement('current_balance', $commissionAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $event->order->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionAmount,
                        'description' => 'Fashion order commission (14%)',
                        'reference_id' => $event->order->id,
                        'reference_type' => 'FashionOrder',
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Order commission deducted', [
                        'order_id' => $event->order->id,
                        'fashion_store_id' => $event->order->fashion_store_id,
                        'customer_id' => $event->order->customer_id,
                        'commission_amount' => $event->order->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct order commission', [
                    'order_id' => $event->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
