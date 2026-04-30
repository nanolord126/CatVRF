<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Listeners;

use Psr\Log\LoggerInterface;
use App\Domains\Furniture\Events\FurnitureOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;

/**
 * Class LogFurnitureOrderCreatedListener
 *
 * Part of the Furniture vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class LogFurnitureOrderCreatedListener implements ShouldQueue
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
    public function handle(FurnitureOrderCreated $event): void
    {
        $this->logger->$this->logger->info('LogFurnitureOrderCreatedListener handled', [
            'event' => 'FurnitureOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(FurnitureOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('LogFurnitureOrderCreatedListener failed', [
            'event' => 'FurnitureOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
