<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\FarmDirect\Events\FarmOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class DeductFarmOrderCommissionListener
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
final class DeductFarmOrderCommissionListener implements ShouldQueue
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
    public function handle(FarmOrderCreated $event): void
    {
        $this->logger->info('DeductFarmOrderCommissionListener handled', [
            'event' => 'FarmOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(FarmOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductFarmOrderCommissionListener failed', [
            'event' => 'FarmOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}