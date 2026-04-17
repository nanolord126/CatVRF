<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\LoyaltyPointsEarnedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class LoyaltyPointsEarnedListener
{
    public function handle(LoyaltyPointsEarnedEvent $event): void
    {
        Log::channel('audit')->info('Loyalty points earned event handled', [
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
        Redis::incrby($key, $event->points);
        Redis::expire($key, 86400 * 30);
    }

    private function checkTierUpgrade(LoyaltyPointsEarnedEvent $event): void
    {
        $userKey = "beauty:loyalty:user:{$event->userId}";
        $data = json_decode(Redis::get($userKey) ?: '{}', true);

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
        $key = "beauty:loyalty:tier_upgrades";
        Redis::lpush($key, json_encode([
            'timestamp' => now()->toIso8601String(),
            'user_id' => $userId,
            'from_tier' => $fromTier,
            'to_tier' => $toTier,
        ]));
        Redis::expire($key, 86400 * 30);
    }
}
