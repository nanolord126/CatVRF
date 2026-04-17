<?php declare(strict_types=1);

namespace App\Domains\Logistics\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class DeductShipmentCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}



        public function handle(ShipmentCreated $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $wallet = \App\Models\Wallet::where('tenant_id', $event->shipment->tenant_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$wallet) {
                        throw new \RuntimeException('Wallet not found');
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

                    $this->logger->info('Shipment commission deducted', [
                        'shipment_id' => $event->shipment->id,
                        'tenant_id' => $event->shipment->tenant_id,
                        'customer_id' => $event->shipment->customer_id,
                        'commission_amount' => $event->shipment->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to deduct shipment commission', [
                    'shipment_id' => $event->shipment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            }
        }
}

