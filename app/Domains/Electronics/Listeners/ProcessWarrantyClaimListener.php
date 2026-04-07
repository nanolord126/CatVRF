<?php declare(strict_types=1);

namespace App\Domains\Electronics\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Electronics\Events\WarrantyClaimSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class ProcessWarrantyClaimListener
 *
 * Part of the Electronics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Electronics\Listeners
 */
final class ProcessWarrantyClaimListener implements ShouldQueue
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
    public function handle(WarrantyClaimSubmitted $event): void
    {
        $this->logger->info('ProcessWarrantyClaimListener handled', [
            'event' => 'WarrantyClaimSubmitted',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(WarrantyClaimSubmitted $event, \Throwable $exception): void
    {
        $this->logger->error('ProcessWarrantyClaimListener failed', [
            'event' => 'WarrantyClaimSubmitted',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}