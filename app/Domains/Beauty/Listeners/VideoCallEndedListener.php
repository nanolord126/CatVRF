<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\VideoCallEndedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final class VideoCallEndedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}
    public function handle(VideoCallEndedEvent $event): void
    {
        $this->log->channel('audit')->$this->logger->info('Video call ended event handled', [
            'correlation_id' => $event->correlationId,
            'call_id' => $event->callId,
            'user_id' => $event->userId,
            'master_id' => $event->masterId,
            'duration_seconds' => $event->durationSeconds,
            'reason' => $event->reason,
        ]);

        $this->trackCallStatistics($event);
        $this->updateMasterAvailability($event);
    }

    private function trackCallStatistics(VideoCallEndedEvent $event): void
    {
        $key = 'beauty:call_stats:daily:'.CarbonImmutable::now()->toDateString();
        $this->redis->hincrby($key, 'total_calls', 1);
        $this->redis->hincrby($key, 'total_duration', $event->durationSeconds);
        $this->redis->expire($key, 86400 * 30);
    }

    private function updateMasterAvailability(VideoCallEndedEvent $event): void
    {
        $key = "beauty:master:availability:{$event->masterId}";
        $this->redis->del($key);
    }
}
