<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\Logistics\Models\Shipment;
use App\Domains\Logistics\Models\ShipmentTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class UpdateShipmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $shipmentId,
        private readonly string $status,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $shipment = Shipment::find($this->shipmentId);
            if (!$shipment) {
                $this->logger->warning('Shipment not found for status update', [
                    'shipment_id' => $this->shipmentId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            $shipment->update(['status' => $this->status, 'correlation_id' => $this->correlationId]);

            ShipmentTracking::create([
                'tenant_id' => $shipment->tenant_id,
                'shipment_id' => $shipment->id,
                'event_type' => match($this->status) {
                    'in_transit' => 'in_transit',
                    'delivered' => 'delivered',
                    default => 'in_transit',
                },
                'event_time' => now(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->info('Shipment status updated via job', [
                'shipment_id' => $this->shipmentId,
                'new_status' => $this->status,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to update shipment status via job', [
                'shipment_id' => $this->shipmentId,
                'status' => $this->status,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
