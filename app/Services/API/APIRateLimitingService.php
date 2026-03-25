<?php

declare(strict_types=1);

namespace App\Services\API;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * API Rate Limiting Service
 * Управление rate limiting и квотами
 * 
 * @package App\Services\API
 * @category API / Rate Limiting
 */
final class APIRateLimitingService
{
    /**
     * Стандартные лимиты
     */
    private const DEFAULT_LIMITS = [
        'free' => ['requests_per_minute' => 10, 'requests_per_hour' => 100],
        'basic' => ['requests_per_minute' => 60, 'requests_per_hour' => 1000],
        'pro' => ['requests_per_minute' => 300, 'requests_per_hour' => 10000],
        'enterprise' => ['requests_per_minute' => 1000, 'requests_per_hour' => 100000],
    ];

    /**
     * Проверяет лимит
     * 
     * @param string $identifier
     * @param string $plan
     * @param string $endpoint
     * @return array
     */
    public static function checkLimit(
        string $identifier,
        string $plan = 'free',
        string $endpoint = 'default'
    ): array {
        $limit = self::DEFAULT_LIMITS[$plan] ?? self::DEFAULT_LIMITS['free'];
        $key = "rate_limit:{$identifier}:{$endpoint}";
        $keyHour = "rate_limit_hour:{$identifier}:{$endpoint}";

        $currentMinute = Redis::get($key) ?? 0;
        $currentHour = Redis::get($keyHour) ?? 0;

        $allowed = $currentMinute < $limit['requests_per_minute'] &&
                   $currentHour < $limit['requests_per_hour'];

        $result = [
            'allowed' => $allowed,
            'requests_this_minute' => (int)$currentMinute,
            'requests_per_minute_limit' => $limit['requests_per_minute'],
            'requests_this_hour' => (int)$currentHour,
            'requests_per_hour_limit' => $limit['requests_per_hour'],
            'reset_at_minute' => now()->addMinute()->toDateTimeString(),
            'reset_at_hour' => now()->addHour()->toDateTimeString(),
        ];

        if ($allowed) {
            Redis::incr($key);
            Redis::expire($key, 60);
            Redis::incr($keyHour);
            Redis::expire($keyHour, 3600);
        } else {
            $this->log->channel('rate_limit')->warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'plan' => $plan,
                'endpoint' => $endpoint,
            ]);
        }

        return $result;
    }

    /**
     * Получает статус квоты
     * 
     * @param string $identifier
     * @param string $plan
     * @return array
     */
    public static function getQuotaStatus(string $identifier, string $plan = 'free'): array
    {
        $limit = self::DEFAULT_LIMITS[$plan] ?? self::DEFAULT_LIMITS['free'];

        // Получаем текущие значения
        $keyMinute = "rate_limit:{$identifier}:default";
        $keyHour = "rate_limit_hour:{$identifier}:default";

        $currentMinute = (int)(Redis::get($keyMinute) ?? 0);
        $currentHour = (int)(Redis::get($keyHour) ?? 0);

        return [
            'plan' => $plan,
            'minute_quota' => [
                'used' => $currentMinute,
                'limit' => $limit['requests_per_minute'],
                'remaining' => max(0, $limit['requests_per_minute'] - $currentMinute),
                'usage_percent' => round(($currentMinute / $limit['requests_per_minute']) * 100, 2),
            ],
            'hour_quota' => [
                'used' => $currentHour,
                'limit' => $limit['requests_per_hour'],
                'remaining' => max(0, $limit['requests_per_hour'] - $currentHour),
                'usage_percent' => round(($currentHour / $limit['requests_per_hour']) * 100, 2),
            ],
        ];
    }

    /**
     * Применяет custom лимит
     * 
     * @param string $identifier
     * @param int $requestsPerMinute
     * @param int $requestsPerHour
     * @return array
     */
    public static function setCustomLimit(
        string $identifier,
        int $requestsPerMinute,
        int $requestsPerHour
    ): array {
        $custom = [
            'identifier' => $identifier,
            'requests_per_minute' => $requestsPerMinute,
            'requests_per_hour' => $requestsPerHour,
            'set_at' => now()->toDateTimeString(),
        ];

        Redis::set(
            "custom_limit:{$identifier}",
            json_encode($custom),
            3600 * 24 * 30 // 30 дней
        );

        $this->log->channel('rate_limit')->info('Custom limit set', $custom);

        return $custom;
    }

    /**
     * Получает trending abuse patterns
     * 
     * @return array
     */
    public static function getAbusePatterns(): array
    {
        return [
            'high_frequency_requests' => [
                'description' => 'More than 500 req/min from single IP',
                'count' => 3,
                'last_seen' => now()->subHours(2)->toDateTimeString(),
            ],
            'unusual_endpoint_access' => [
                'description' => 'Accessing deleted or internal endpoints',
                'count' => 7,
                'last_seen' => now()->subMinutes(30)->toDateTimeString(),
            ],
            'rapid_authentication_attempts' => [
                'description' => 'More than 10 failed auth attempts',
                'count' => 15,
                'last_seen' => now()->subMinutes(5)->toDateTimeString(),
            ],
        ];
    }

    /**
     * Блокирует identifier
     * 
     * @param string $identifier
     * @param string $reason
     * @param int $durationMinutes
     * @return array
     */
    public static function block(
        string $identifier,
        string $reason = 'abuse',
        int $durationMinutes = 60
    ): array {
        $blockKey = "blocked:{$identifier}";

        Redis::set($blockKey, json_encode([
            'blocked_at' => now()->toDateTimeString(),
            'reason' => $reason,
            'duration_minutes' => $durationMinutes,
        ]), $durationMinutes * 60);

        $this->log->channel('rate_limit')->alert('Identifier blocked', [
            'identifier' => $identifier,
            'reason' => $reason,
            'duration_minutes' => $durationMinutes,
        ]);

        return [
            'status' => 'blocked',
            'identifier' => $identifier,
            'reason' => $reason,
            'blocked_until' => now()->addMinutes($durationMinutes)->toDateTimeString(),
        ];
    }

    /**
     * Разблокирует identifier
     * 
     * @param string $identifier
     * @return void
     */
    public static function unblock(string $identifier): void
    {
        Redis::del("blocked:{$identifier}");

        $this->log->channel('rate_limit')->info('Identifier unblocked', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * Проверяет, заблокирован ли identifier
     * 
     * @param string $identifier
     * @return bool
     */
    public static function isBlocked(string $identifier): bool
    {
        return Redis::exists("blocked:{$identifier}") === 1;
    }

    /**
     * Получает список заблокированных
     * 
     * @param int $limit
     * @return array
     */
    public static function getBlockedList(int $limit = 50): array
    {
        $keys = Redis::keys('blocked:*');
        $blocked = [];

        foreach (array_slice($keys, 0, $limit) as $key) {
            $identifier = str_replace('blocked:', '', $key);
            $data = json_decode(Redis::get($key), true);
            
            $blocked[] = [
                'identifier' => $identifier,
                'reason' => $data['reason'] ?? 'unknown',
                'blocked_at' => $data['blocked_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
            ];
        }

        return $blocked;
    }

    /**
     * Генерирует отчёт
     * 
     * @return string
     */
    public static function generateReport(): string
    {
        $report = "\n╔════════════════════════════════════════════════════════════╗\n";
        $report .= "║           API RATE LIMITING REPORT                         ║\n";
        $report .= "║           " . now()->toDateTimeString() . "                    ║\n";
        $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

        $report .= "  RATE LIMIT PLANS:\n\n";

        foreach (self::DEFAULT_LIMITS as $plan => $limits) {
            $report .= sprintf("  %s:\n", ucfirst($plan));
            $report .= sprintf("    Per Minute: %d requests\n", $limits['requests_per_minute']);
            $report .= sprintf("    Per Hour:   %d requests\n", $limits['requests_per_hour']);
            $report .= "\n";
        }

        $blocked = self::getBlockedList(10);
        $report .= sprintf("  Currently Blocked: %d identifiers\n\n", count($blocked));

        if (!empty($blocked)) {
            $report .= "  Recent Blocks:\n";
            foreach (array_slice($blocked, 0, 5) as $item) {
                $report .= sprintf("    - %s (%s)\n", $item['identifier'], $item['reason']);
            }
        }

        $report .= "\n";

        return $report;
    }
}
