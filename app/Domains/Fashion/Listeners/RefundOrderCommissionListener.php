<?php declare(strict_types=1);

namespace App\Domains\Fashion\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class RefundOrderCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(ReturnRequested $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $order = $event->return->order;

                    $wallet = Wallet::where('tenant_id', $event->return->tenant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $wallet) {
                        throw new \RuntimeException('Wallet not found');
                    }

                    $refundAmount = intval($event->return->refund_amount * 100);

                    $wallet->increment('current_balance', $refundAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $event->return->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $refundAmount,
                        'description' => 'Fashion order return refund',
                        'reference_id' => $event->return->id,
                        'reference_type' => 'FashionReturn',
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Order return refund credited', [
                        'return_id' => $event->return->id,
                        'order_id' => $order->id,
                        'customer_id' => $event->return->customer_id,
                        'refund_amount' => $event->return->refund_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to refund order return', [
                    'return_id' => $event->return->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
