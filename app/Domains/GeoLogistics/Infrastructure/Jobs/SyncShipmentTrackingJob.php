<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Infrastructure\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Domain\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
/**
 * Class SyncShipmentTrackingJob
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\GeoLogistics\Infrastructure\Jobs
 */
final class SyncShipmentTrackingJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly int $shipmentId,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {}

    public function handle(): void
    {
        $this->logger->info('SyncShipmentTrackingJob запущен', [
            'correlation_id' => $this->correlationId,
            'shipment_id' => $this->shipmentId,
        ]);

        $shipment = Shipment::find($this->shipmentId);
        
        if (!$shipment) {
            $this->logger->warning('Shipment not found for tracking', [
                'shipment_id' => $this->shipmentId,
                'correlation_id' => $this->correlationId
            ]);
            return;
        }

        // Логика интеграции с WebSockets (Pusher/Reverb) для Live-Tracking карты клиента
        // event(new CourierLocationUpdatedEvent(...));

        $this->logger->info('SyncShipmentTrackingJob завершён', [
            'correlation_id' => $this->correlationId,
            'status' => $shipment->status->value
        ]);
    }
}

