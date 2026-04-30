<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\MasterMatchedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final class MasterMatchedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}
    public function handle(MasterMatchedEvent $event): void
    {
        $this->log->channel('audit')->$this->logger->info('Master matched event handled', [
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
        $this->redis->lpush($key, json_encode([
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'matches_count' => $matchesCount,
        ]));
        $this->redis->expire($key, 86400 * 30);
    }

    private function trackMasterPopularity(array $matchedMasters): void
    {
        foreach ($matchedMasters as $master) {
            $key = "beauty:master_popularity:{$master['id']}";
            $this->redis->incr($key);
            $this->redis->expire($key, 86400 * 7);
        }
    }
}
