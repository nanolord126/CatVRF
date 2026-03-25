<?php declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use App\Domains\Sports\Events\PurchaseRefunded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RefundPurchaseCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PurchaseRefunded $event): void
    {
        try {
            $this->log->channel('audit')->info('Processing purchase refund commission', [
                'purchase_id' => $event->purchase->id,
                'commission_amount' => $event->purchase->commission_amount,
                'reason' => $event->reason,
                'correlation_id' => $event->correlationId,
            ]);

            $this->db->transaction(function () use ($event) {
                $wallet = \App\Models\Wallet::lockForUpdate()
                    ->where('tenant_id', $event->purchase->tenant_id)
                    ->firstOrFail();

                $wallet->increment('balance', intval($event->purchase->commission_amount * 100));

                \App\Models\BalanceTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'refund',
                    'amount' => intval($event->purchase->commission_amount * 100),
                    'status' => 'completed',
                    'correlation_id' => $event->correlationId,
                    'metadata' => [
                        'purchase_id' => $event->purchase->id,
                        'reason' => $event->reason,
                    ],
                ]);
            });

            $this->log->channel('audit')->info('Purchase refund commission processed', [
                'purchase_id' => $event->purchase->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to process purchase refund commission', [
                'purchase_id' => $event->purchase->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
