<?php declare(strict_types=1);

namespace App\Domains\Art\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Art\Events\ArtworkCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogArtworkCreatedListener
 *
 * Part of the Art vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Art\Listeners
 */
final class LogArtworkCreatedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(ArtworkCreated $event): void
    {
        $this->logger->info('LogArtworkCreatedListener handled', [
            'event' => 'ArtworkCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(ArtworkCreated $event, \Throwable $exception): void
    {
        $this->logger->error('LogArtworkCreatedListener failed', [
            'event' => 'ArtworkCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);
    }
}