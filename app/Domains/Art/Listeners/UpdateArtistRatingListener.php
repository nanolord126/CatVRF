<?php declare(strict_types=1);

namespace App\Domains\Art\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Art\Events\ReviewRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class UpdateArtistRatingListener
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
final class UpdateArtistRatingListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(ReviewRecorded $event): void
    {
        $this->logger->info('UpdateArtistRatingListener handled', [
            'event' => 'ReviewRecorded',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(ReviewRecorded $event, \Throwable $exception): void
    {
        $this->logger->error('UpdateArtistRatingListener failed', [
            'event' => 'ReviewRecorded',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);
    }
}