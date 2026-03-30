<?php declare(strict_types=1);

namespace App\Domains\Logistics\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductShipmentCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(ShipmentCreated $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::where('tenant_id', $event->shipment->tenant_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$wallet) {
                        throw new \Exception('Wallet not found');
                    }

                    $commissionAmount = (int) ($event->shipment->commission_amount * 100);
                    $wallet->decrement('balance', $commissionAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $event->shipment->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionAmount,
                        'shipment_id' => $event->shipment->id,
                        'correlation_id' => $event->correlationId,
                    ]);

                    Log::channel('audit')->info('Shipment commission deducted', [
                        'shipment_id' => $event->shipment->id,
                        'tenant_id' => $event->shipment->tenant_id,
                        'customer_id' => $event->shipment->customer_id,
                        'commission_amount' => $event->shipment->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to deduct shipment commission', [
                    'shipment_id' => $event->shipment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
}
