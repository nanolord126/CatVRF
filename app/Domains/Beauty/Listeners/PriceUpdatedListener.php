<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\PriceUpdatedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final class PriceUpdatedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}
    public function handle(PriceUpdatedEvent $event): void
    {
        $this->log->channel('audit')->$this->logger->info('Price updated event handled', [
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
        $this->redis->lpush($key, json_encode([
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'master_id' => $event->masterId,
            'old_price' => $event->oldPrice,
            'new_price' => $event->newPrice,
        ]));
        $this->redis->expire($key, 86400 * 30);
    }

    private function notifyPricingChange(PriceUpdatedEvent $event): void
    {
        $priceChangePercent = $event->oldPrice > 0
            ? round((($event->newPrice - $event->oldPrice) / $event->oldPrice) * 100, 2)
            : 0;

        if (abs($priceChangePercent) >= 20) {
            $key = 'beauty:significant_price_changes';
            $this->redis->lpush($key, json_encode([
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
                'master_id' => $event->masterId,
                'service_id' => $event->serviceId,
                'change_percent' => $priceChangePercent,
            ]));
            $this->redis->expire($key, 86400 * 7);
        }
    }
}
