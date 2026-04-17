<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\PriceUpdatedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class PriceUpdatedListener
{
    public function handle(PriceUpdatedEvent $event): void
    {
        Log::channel('audit')->info('Price updated event handled', [
            'correlation_id' => $event->correlationId,
            'master_id' => $event->masterId,
            'service_id' => $event->serviceId,
            'old_price' => $event->oldPrice,
            'new_price' => $event->newPrice,
        ]);

        $this->trackPriceHistory($event);
        $this->notifyPricingChange($event);
    }

    private function trackPriceHistory(PriceUpdatedEvent $event): void
    {
        $key = "beauty:price_history:{$event->serviceId}";
        Redis::lpush($key, json_encode([
            'timestamp' => now()->toIso8601String(),
            'master_id' => $event->masterId,
            'old_price' => $event->oldPrice,
            'new_price' => $event->newPrice,
        ]));
        Redis::expire($key, 86400 * 30);
    }

    private function notifyPricingChange(PriceUpdatedEvent $event): void
    {
        $priceChangePercent = $event->oldPrice > 0
            ? round((($event->newPrice - $event->oldPrice) / $event->oldPrice) * 100, 2)
            : 0;

        if (abs($priceChangePercent) >= 20) {
            $key = "beauty:significant_price_changes";
            Redis::lpush($key, json_encode([
                'timestamp' => now()->toIso8601String(),
                'master_id' => $event->masterId,
                'service_id' => $event->serviceId,
                'change_percent' => $priceChangePercent,
            ]));
            Redis::expire($key, 86400 * 7);
        }
    }
}
