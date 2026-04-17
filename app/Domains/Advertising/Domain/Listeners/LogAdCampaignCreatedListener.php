<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Domains\Advertising\Domain\Events\AdCampaignCreated;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * Listener for AdCampaignCreated domain event.
 *
 * Logs campaign creation via AuditService and logger.
 * Runs asynchronously via queue (ShouldQueue).
 * Does NOT inject Request — unavailable in queue context.
 *
 * @package App\Domains\Advertising\Domain\Listeners
 */
final class LogAdCampaignCreatedListener implements ShouldQueue
{

    /**
     * The queue connection that should handle the job.
     */

    /**
     * Handle the event.
     *
     * Logger and Audit resolved via DI in handle() — safe for serialization.
     */
    public function handle(AdCampaignCreated $event, LoggerInterface $logger, AuditService $audit): void
    {
        $logger->info('AdCampaignCreated event handled', [
            'event' => 'AdCampaignCreated',
            'campaign_id' => $event->campaignId,
            'correlation_id' => $event->correlationId,
        ]);

        $audit->log(
            action: 'ad_campaign_created_event',
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
    public function failed(AdCampaignCreated $event, \Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'LogAdCampaignCreatedListener failed [campaign_id=%s, correlation_id=%s]: %s',
                $event->campaignId,
                $event->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
