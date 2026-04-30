<?php

declare(strict_types=1);

namespace App\Octane\Listeners;

use Illuminate\Cache\CacheManager;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Laravel\Octane\Events\WorkerStarting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Log\LogManager;

final readonly class WarmupSwooleCache
{
    public function __construct(private readonly CacheManager $cacheManager,
        private readonly LoggerInterface $logger,) {}

    public function handle(WorkerStarting $event): void
    {
        try {
            // Warm up frequently accessed cache keys
            $warmupKeys = [
                'medical:appointment:slots:config',
                'payment:gateways:config',
                'geo:regions:cache',
                'fraud:rules:config',
            ];

            foreach ($warmupKeys as $key) {
                $this->cacheManager->remember($key, CarbonImmutable::now()->addHours(1), fn () => null);
            }

            $this->log->$this->logger->info('Swoole cache warmed up successfully', [
                'keys_warmed' => count($warmupKeys),
            ]);
        } catch (\Throwable $e) {
            $this->log->error('Failed to warm up Swoole cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Non-critical, don't throw
        }
    }
}
