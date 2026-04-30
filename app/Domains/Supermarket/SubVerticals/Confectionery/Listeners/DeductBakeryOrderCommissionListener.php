<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Confectionery\Listeners;

use Psr\Log\LoggerInterface;
use App\Domains\Confectionery\Events\BakeryOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Class DeductBakeryOrderCommissionListener
 *
 * Part of the Confectionery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class DeductBakeryOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(BakeryOrderCreated $event): void
    {
        $this->logger->$this->logger->info('DeductBakeryOrderCommissionListener handled', [
            'event' => 'BakeryOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(BakeryOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductBakeryOrderCommissionListener failed', [
            'event' => 'BakeryOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? Str::uuid()->toString(),
        ]);
    }
}
