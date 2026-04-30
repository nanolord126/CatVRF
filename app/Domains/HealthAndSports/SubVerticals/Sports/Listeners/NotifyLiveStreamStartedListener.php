<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Sports\Events\LiveStreamStartedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Log\LogManager;

final class NotifyLiveStreamStartedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly LogManager $log,) {}

    public function handle(LiveStreamStartedEvent $event): void
    {
        $this->log->channel('notifications')->$this->logger->info('Notifying users about live stream start', [
            'stream_id' => $event->streamId,
            'trainer_id' => $event->trainerId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            'live_stream_started_notification_sent',
            'sports_live_stream',
            $event->streamId,
            [],
            [
                'trainer_id' => $event->trainerId,
                'stream_title' => $event->streamTitle,
                'webrtc_room' => $event->webrtcRoom,
                'correlation_id' => $event->correlationId,
            ],
            $event->correlationId
        );
    }

    public function failed(LiveStreamStartedEvent $event, \Throwable $exception): void
    {
        $this->log->channel('notifications')->error('Failed to notify about live stream start', [
            'stream_id' => $event->streamId,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
