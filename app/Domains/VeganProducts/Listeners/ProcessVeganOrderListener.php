<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\VeganProducts\Events\VeganEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class ProcessVeganOrderListener
 *
 * Part of the VeganProducts vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\VeganProducts\Listeners
 */
final class ProcessVeganOrderListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(VeganEvents $event): void
    {
        $this->logger->info('ProcessVeganOrderListener handled', [
            'event' => 'VeganEvents',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(VeganEvents $event, \Throwable $exception): void
    {
        $this->logger->error('ProcessVeganOrderListener failed', [
            'event' => 'VeganEvents',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}