<?php declare(strict_types=1);

namespace App\Domains\Logistics\Listeners;

use App\Domains\Logistics\Events\ShipmentCreated;
use App\Models\BalanceTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RefundShipmentCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShipmentCreated $event): void
    {
        if ($event->shipment->status !== 'cancelled') {
            return;
        }

        try {
            DB::transaction(function () use ($event) {
                $wallet = \App\Models\Wallet::where('tenant_id', $event->shipment->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception('Wallet not found');
                }

                $commissionAmount = (int) ($event->shipment->commission_amount * 100);
                $wallet->increment('balance', $commissionAmount);

                BalanceTransaction::create([
                    'tenant_id' => $event->shipment->tenant_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'refund',
                    'amount' => $commissionAmount,
                    'shipment_id' => $event->shipment->id,
                    'correlation_id' => $event->correlationId,
                ]);

                Log::channel('audit')->info('Shipment commission refunded', [
                    'shipment_id' => $event->shipment->id,
                    'tenant_id' => $event->shipment->tenant_id,
                    'customer_id' => $event->shipment->customer_id,
                    'commission_amount' => $event->shipment->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to refund shipment commission', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
