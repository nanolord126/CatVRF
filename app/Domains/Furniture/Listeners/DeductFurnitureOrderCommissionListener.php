<?php declare(strict_types=1);

namespace App\Domains\Furniture\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Furniture\Events\FurnitureCustomOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class DeductFurnitureOrderCommissionListener
 *
 * Part of the Furniture vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Furniture\Listeners
 */
final class DeductFurnitureOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(FurnitureCustomOrderCreated $event): void
    {
        $this->logger->info('DeductFurnitureOrderCommissionListener handled', [
            'event' => 'FurnitureCustomOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(FurnitureCustomOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductFurnitureOrderCommissionListener failed', [
            'event' => 'FurnitureCustomOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}