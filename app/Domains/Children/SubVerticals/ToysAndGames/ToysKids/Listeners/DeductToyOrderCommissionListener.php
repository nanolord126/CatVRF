<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\ToysKids\Listeners;

use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\ToysAndGames\ToysKids\Events\ToyOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Class DeductToyOrderCommissionListener
 *
 * Part of the ToysAndGames vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class DeductToyOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(ToyOrderCreated $event): void
    {
        $this->logger->$this->logger->info('DeductToyOrderCommissionListener handled', [
            'event' => 'ToyOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(ToyOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductToyOrderCommissionListener failed', [
            'event' => 'ToyOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}
