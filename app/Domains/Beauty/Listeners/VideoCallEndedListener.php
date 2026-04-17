<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\VideoCallEndedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class VideoCallEndedListener
{
    public function handle(VideoCallEndedEvent $event): void
    {
        Log::channel('audit')->info('Video call ended event handled', [
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
        $key = "beauty:call_stats:daily:" . now()->toDateString();
        Redis::hincrby($key, 'total_calls', 1);
        Redis::hincrby($key, 'total_duration', $event->durationSeconds);
        Redis::expire($key, 86400 * 30);
    }

    private function updateMasterAvailability(VideoCallEndedEvent $event): void
    {
        $key = "beauty:master:availability:{$event->masterId}";
        Redis::del($key);
    }
}
