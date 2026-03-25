<?php declare(strict_types=1);

namespace App\Domains\Fashion\Listeners;

use App\Domains\Fashion\Events\OrderPlaced;
use App\Models\BalanceTransaction;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeductOrderCommissionListener implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $wallet = Wallet::where('tenant_id', $event->order->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw new \Exception('Wallet not found');
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

                $this->log->channel('audit')->info('Order commission deducted', [
                    'order_id' => $event->order->id,
                    'fashion_store_id' => $event->order->fashion_store_id,
                    'customer_id' => $event->order->customer_id,
                    'commission_amount' => $event->order->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to deduct order commission', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
