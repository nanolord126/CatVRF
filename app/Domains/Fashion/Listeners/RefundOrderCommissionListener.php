<?php declare(strict_types=1);

namespace App\Domains\Fashion\Listeners;

use App\Domains\Fashion\Events\ReturnRequested;
use App\Models\BalanceTransaction;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RefundOrderCommissionListener implements ShouldQueue
{
    public function handle(ReturnRequested $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $order = $event->return->order;

                $wallet = Wallet::where('tenant_id', $event->return->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw new \Exception('Wallet not found');
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

                $this->log->channel('audit')->info('Order return refund credited', [
                    'return_id' => $event->return->id,
                    'order_id' => $order->id,
                    'customer_id' => $event->return->customer_id,
                    'refund_amount' => $event->return->refund_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to refund order return', [
                'return_id' => $event->return->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
