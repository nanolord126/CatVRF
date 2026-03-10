<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class GlobalRateLimitGuardian
{
    /**
     * Глобальный защитник от шквального трафика (5000+ RPM).
     * Вместо 'очередей' и 'Retry-After', мы реализуем Zero-Latency Failover.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lua-скрипт для атомарного sliding window или инкремента (в зависимости от доступности ресурсов)
        $lua = <<<'LUA'
local key = KEYS[1]
local limit = tonumber(ARGV[1])
local current = redis.call("INCR", key)
if current == 1 then
    redis.call("EXPIRE", key, 60)
end
return current
LUA;
        $key = 'global_traffic_monitor:' . now()->format('i');
        $currentRpm = Redis::eval($lua, 1, $key, 100000);

        // Если нагрузка превышает 100 000 в минуту (1666 RPS)
        if ($currentRpm > 100000) {
            return response()->json([
                'error' => 'Platform Limit Reached',
                'retry_after' => 10,
                'status' => 'critical_load'
            ], 429);
        }

        // Автоматическое масштабирование: Отключаем тяжелые фичи (Personalization AI, Analytics)
        if ($currentRpm > 50000) {
            config(['app.heavy_features_enabled' => false]);
            config(['cache.default' => 'redis_cluster']);
        }

        // Замер времени выполнения всего приложения
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        if ($duration > 500) {
            // Регистрация медленных запросов во всем приложении (Slow Log)
            \Illuminate\Support\Facades\Log::warning("SLOW REQUEST DETECTED: {$request->fullUrl()} | {$duration}ms");
        }

        return $response;
    }
}
