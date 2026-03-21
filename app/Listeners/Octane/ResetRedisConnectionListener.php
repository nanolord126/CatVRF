<?php declare(strict_types=1);

namespace App\Listeners\Octane;

use Laravel\Octane\Events\RequestHandled;
use Illuminate\Support\Facades\Redis;

final class ResetRedisConnectionListener
{
    public function handle(RequestHandled $event): void
    {
        // Reset Redis connections to prevent stale connections
        try {
            Redis::connection()->ping();
        } catch (\Exception $e) {
            // Reconnect on failure
            Redis::connection()->disconnect();
            Redis::connection()->connect();
        }

        // Clear Redis connection pools
        Redis::flushdb();
    }
}
