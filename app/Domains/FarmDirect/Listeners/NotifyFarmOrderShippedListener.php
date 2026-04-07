<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\FarmDirect\Events\FarmOrderShipped;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class NotifyFarmOrderShippedListener
 *
 * Part of the FarmDirect vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\FarmDirect\Listeners
 */
final class NotifyFarmOrderShippedListener implements ShouldQueue
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
    public function handle(FarmOrderShipped $event): void
    {
        $this->logger->info('NotifyFarmOrderShippedListener handled', [
            'event' => 'FarmOrderShipped',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(FarmOrderShipped $event, \Throwable $exception): void
    {
        $this->logger->error('NotifyFarmOrderShippedListener failed', [
            'event' => 'FarmOrderShipped',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}