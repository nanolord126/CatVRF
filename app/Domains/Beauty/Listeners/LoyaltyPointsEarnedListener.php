<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\LoyaltyPointsEarnedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final class LoyaltyPointsEarnedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}
    public function handle(LoyaltyPointsEarnedEvent $event): void
    {
        $this->log->channel('audit')->$this->logger->info('Loyalty points earned event handled', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'points' => $event->points,
            'action' => $event->action,
        ]);

        $this->trackUserEngagement($event);
        $this->checkTierUpgrade($event);
    }

    private function trackUserEngagement(LoyaltyPointsEarnedEvent $event): void
    {
        $key = "beauty:loyalty:engagement:{$event->userId}";
        $this->redis->incrby($key, $event->points);
        $this->redis->expire($key, 86400 * 30);
    }

    private function checkTierUpgrade(LoyaltyPointsEarnedEvent $event): void
    {
        $userKey = "beauty:loyalty:user:{$event->userId}";
        $data = json_decode($this->redis->get($userKey) ?: '{}', true);

        $currentTier = $data['tier'] ?? 'bronze';
        $totalPoints = $data['total_points'] ?? 0;

        $newTier = $this->calculateTier($totalPoints);

        if ($newTier !== $currentTier) {
            $this->notifyTierUpgrade($event->userId, $currentTier, $newTier);
        }
    }

    private function calculateTier(int $totalPoints): string
    {
        if ($totalPoints >= 10000) {
            return 'platinum';
        }

        if ($totalPoints >= 5000) {
            return 'gold';
        }

        if ($totalPoints >= 2000) {
            return 'silver';
        }

        return 'bronze';
    }

    private function notifyTierUpgrade(int $userId, string $fromTier, string $toTier): void
    {
        $key = 'beauty:loyalty:tier_upgrades';
        $this->redis->lpush($key, json_encode([
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'user_id' => $userId,
            'from_tier' => $fromTier,
            'to_tier' => $toTier,
        ]));
        $this->redis->expire($key, 86400 * 30);
    }
}
