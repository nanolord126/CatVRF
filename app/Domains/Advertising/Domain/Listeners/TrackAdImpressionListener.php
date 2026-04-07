<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Listeners;

use App\Domains\Advertising\Domain\Events\AdImpressionRegistered;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * Listener for AdImpressionRegistered domain event.
 *
 * Tracks impression analytics via AuditService and logger.
 * Runs asynchronously via queue (ShouldQueue).
 * Does NOT inject Request — unavailable in queue context.
 *
 * @package App\Domains\Advertising\Domain\Listeners
 */
final class TrackAdImpressionListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The queue connection that should handle the job.
     */
    public string $queue = 'advertising';

    /**
     * Handle the event.
     *
     * Logger and Audit resolved via DI in handle() — safe for serialization.
     */
    public function handle(AdImpressionRegistered $event, LoggerInterface $logger, AuditService $audit): void
    {
        $logger->info('AdImpressionRegistered event handled', [
            'event' => 'AdImpressionRegistered',
            'campaign_id' => $event->campaignId,
            'cost' => $event->cost,
            'correlation_id' => $event->correlationId,
        ]);

        $audit->log(
            action: 'ad_impression_registered_event',
            subjectType: 'AdCampaign',
            subjectId: $event->campaignId,
            old: [],
            new: $event->toArray(),
            correlationId: $event->correlationId,
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(AdImpressionRegistered $event, \Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'TrackAdImpressionListener failed [campaign_id=%s, correlation_id=%s]: %s',
                $event->campaignId,
                $event->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
