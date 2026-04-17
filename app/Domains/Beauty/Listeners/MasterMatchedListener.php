<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\MasterMatchedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class MasterMatchedListener
{
    public function handle(MasterMatchedEvent $event): void
    {
        Log::channel('audit')->info('Master matched event handled', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'matches_count' => count($event->matchedMasters),
        ]);

        $this->updateUserSearchHistory($event->userId, count($event->matchedMasters));

        $this->trackMasterPopularity($event->matchedMasters);
    }

    private function updateUserSearchHistory(int $userId, int $matchesCount): void
    {
        $key = "beauty:user_search_history:{$userId}";
        Redis::lpush($key, json_encode([
            'timestamp' => now()->toIso8601String(),
            'matches_count' => $matchesCount,
        ]));
        Redis::expire($key, 86400 * 30);
    }

    private function trackMasterPopularity(array $matchedMasters): void
    {
        foreach ($matchedMasters as $master) {
            $key = "beauty:master_popularity:{$master['id']}";
            Redis::incr($key);
            Redis::expire($key, 86400 * 7);
        }
    }
}
