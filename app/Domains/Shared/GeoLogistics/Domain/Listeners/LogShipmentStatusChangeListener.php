<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Domain\Listeners;

use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Domain\Events\ShipmentStatusChangedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;

/**
 * Class LogShipmentStatusChangeListener
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class LogShipmentStatusChangeListener implements ShouldQueue
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(ShipmentStatusChangedEvent $event): void
    {
        $this->logger->$this->logger->info('LogShipmentStatusChangeListener handled', [
            'event' => 'ShipmentStatusChangedEvent',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(ShipmentStatusChangedEvent $event, \Throwable $exception): void
    {
        $this->logger->error('LogShipmentStatusChangeListener failed', [
            'event' => 'ShipmentStatusChangedEvent',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
