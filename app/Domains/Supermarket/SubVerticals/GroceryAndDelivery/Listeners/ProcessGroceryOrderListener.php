<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Listeners;

use Psr\Log\LoggerInterface;
use App\Domains\GroceryAndDelivery\Events\OrderEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;

/**
 * Class ProcessGroceryOrderListener
 *
 * Part of the GroceryAndDelivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class ProcessGroceryOrderListener implements ShouldQueue
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
    public function handle(OrderEvents $event): void
    {
        $this->logger->$this->logger->info('ProcessGroceryOrderListener handled', [
            'event' => 'OrderEvents',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(OrderEvents $event, \Throwable $exception): void
    {
        $this->logger->error('ProcessGroceryOrderListener failed', [
            'event' => 'OrderEvents',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
