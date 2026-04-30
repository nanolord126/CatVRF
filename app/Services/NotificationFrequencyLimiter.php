<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\User;
use App\Models\NotificationLog;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

final readonly class NotificationFrequencyLimiter
{
    use WithAuditLogging;

    // Frequency limits per user
    private const MAX_NOTIFICATIONS_PER_HOUR = 5;
    private const MAX_NOTIFICATIONS_PER_DAY = 15;
    private const MAX_NOTIFICATIONS_PER_WEEK = 50;
    private const MIN_MINUTES_BETWEEN_SIMILAR = 30;

    // Frequency limits per tenant
    private const MAX_PROMOTIONS_PER_DAY = 2;
    private const MAX_BROADCASTS_PER_HOUR = 1000;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Check if user can receive notification
     */
    public function canSendToUser(int $userId, int $tenantId, ?string $notificationType = null, string $correlationId = ''): array
    {
        $cacheKey = "frequency:user:{$userId}:{$tenantId}";

        $result = $this->cache->remember($cacheKey, now()->addMinutes(5), function () use ($userId, $tenantId, $notificationType) {
            $checks = [];
            $passed = true;
            $reasons = [];

            // Check hourly limit
            $hourlyCheck = $this->checkHourlyLimit($userId);
            $checks['hourly'] = $hourlyCheck;
            if (!$hourlyCheck['passed']) {
                $passed = false;
                $reasons[] = $hourlyCheck['reason'];
            }

            // Check daily limit
            $dailyCheck = $this->checkDailyLimit($userId);
            $checks['daily'] = $dailyCheck;
            if (!$dailyCheck['passed']) {
                $passed = false;
                $reasons[] = $dailyCheck['reason'];
            }

            // Check weekly limit
            $weeklyCheck = $this->checkWeeklyLimit($userId);
            $checks['weekly'] = $weeklyCheck;
            if (!$weeklyCheck['passed']) {
                $passed = false;
                $reasons[] = $weeklyCheck['reason'];
            }

            // Check similar notification cooldown
            if ($notificationType) {
                $similarCheck = $this->checkSimilarNotificationCooldown($userId, $notificationType);
                $checks['similar_cooldown'] = $similarCheck;
                if (!$similarCheck['passed']) {
                    $passed = false;
                    $reasons[] = $similarCheck['reason'];
                }
            }

            return [
                'passed' => $passed,
                'checks' => $checks,
                'reasons' => $reasons,
                'checked_at' => now()->toIso8601String(),
            ];
        });

        // Log frequency check
        $this->logAction(
            'notification_frequency_checked',
            'User',
            $userId,
            [
                'tenant_id' => $tenantId,
                'passed' => $result['passed'],
                'notification_type' => $notificationType,
            ],
            $correlationId
        );

        if (!$result['passed']) {
            $this->logger->channel('frequency')->info('User blocked by frequency limiter', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'reasons' => $result['reasons'],
                'correlation_id' => $correlationId,
            ]);
        }

        return $result;
    }

    /**
     * Check if tenant can send broadcast
     */
    public function canSendBroadcast(int $tenantId, string $correlationId = ''): array
    {
        $cacheKey = "frequency:tenant:{$tenantId}";

        $result = $this->cache->remember($cacheKey, now()->addMinutes(1), function () use ($tenantId) {
            $checks = [];
            $passed = true;
            $reasons = [];

            // Check hourly broadcast limit
            $hourlyBroadcasts = NotificationCampaign::where('tenant_id', $tenantId)
                ->where('status', NotificationCampaign::STATUS_SENDING)
                ->where('sent_at', '>=', now()->subHour())
                ->sum('sent_count');

            $hourlyCheck = [
                'passed' => $hourlyBroadcasts < self::MAX_BROADCASTS_PER_HOUR,
                'current' => $hourlyBroadcasts,
                'limit' => self::MAX_BROADCASTS_PER_HOUR,
                'reason' => $hourlyBroadcasts >= self::MAX_BROADCASTS_PER_HOUR 
                    ? "Hourly broadcast limit exceeded ({$hourlyBroadcasts}/" . self::MAX_BROADCASTS_PER_HOUR . ')' 
                    : 'Within hourly limit',
            ];
            $checks['hourly_broadcast'] = $hourlyCheck;

            if (!$hourlyCheck['passed']) {
                $passed = false;
                $reasons[] = $hourlyCheck['reason'];
            }

            // Check daily promotion limit
            $dailyPromotions = NotificationCampaign::where('tenant_id', $tenantId)
                ->where('type', NotificationCampaign::TYPE_PROMOTION)
                ->where('status', NotificationCampaign::STATUS_COMPLETED)
                ->where('sent_at', '>=', now()->subDay())
                ->count();

            $dailyCheck = [
                'passed' => $dailyPromotions < self::MAX_PROMOTIONS_PER_DAY,
                'current' => $dailyPromotions,
                'limit' => self::MAX_PROMOTIONS_PER_DAY,
                'reason' => $dailyPromotions >= self::MAX_PROMOTIONS_PER_DAY 
                    ? "Daily promotion limit exceeded ({$dailyPromotions}/" . self::MAX_PROMOTIONS_PER_DAY . ')' 
                    : 'Within daily promotion limit',
            ];
            $checks['daily_promotion'] = $dailyCheck;

            if (!$dailyCheck['passed']) {
                $passed = false;
                $reasons[] = $dailyCheck['reason'];
            }

            return [
                'passed' => $passed,
                'checks' => $checks,
                'reasons' => $reasons,
                'checked_at' => now()->toIso8601String(),
            ];
        });

        $this->logAction(
            'broadcast_frequency_checked',
            'Tenant',
            $tenantId,
            ['passed' => $result['passed']],
            $correlationId
        );

        return $result;
    }

    /**
     * Record notification sent for frequency tracking
     */
    public function recordNotificationSent(int $userId, int $tenantId, string $notificationType, string $correlationId = ''): void
    {
        $key = "frequency:user:{$userId}:{$tenantId}";
        $this->cache->forget($key);

        // Use Redis for accurate counting
        $redis = Redis::connection();
        $now = now();

        // Increment counters
        $redis->incr("frequency:user:{$userId}:hour:{$now->format('Y-m-d-H')}");
        $redis->incr("frequency:user:{$userId}:day:{$now->format('Y-m-d')}");
        $redis->incr("frequency:user:{$userId}:week:{$now->format('Y-W')}");
        
        // Set expiry
        $redis->expire("frequency:user:{$userId}:hour:{$now->format('Y-m-d-H')}", 3600);
        $redis->expire("frequency:user:{$userId}:day:{$now->format('Y-m-D')}", 86400);
        $redis->expire("frequency:user:{$userId}:week:{$now->format('Y-W')}", 604800);

        // Record similar notification
        $redis->setex(
            "frequency:similar:{$userId}:{$notificationType}",
            self::MIN_MINUTES_BETWEEN_SIMILAR * 60,
            $now->toIso8601String()
        );

        $this->logAction(
            'notification_frequency_recorded',
            'User',
            $userId,
            [
                'tenant_id' => $tenantId,
                'notification_type' => $notificationType,
            ],
            $correlationId
        );
    }

    /**
     * Check hourly limit for user
     */
    private function checkHourlyLimit(int $userId): array
    {
        $redis = Redis::connection();
        $key = "frequency:user:{$userId}:hour:" . now()->format('Y-m-d-H');
        $count = (int) $redis->get($key);

        return [
            'passed' => $count < self::MAX_NOTIFICATIONS_PER_HOUR,
            'current' => $count,
            'limit' => self::MAX_NOTIFICATIONS_PER_HOUR,
            'reason' => $count >= self::MAX_NOTIFICATIONS_PER_HOUR 
                ? "Hourly limit exceeded ({$count}/" . self::MAX_NOTIFICATIONS_PER_HOUR . ')' 
                : 'Within hourly limit',
        ];
    }

    /**
     * Check daily limit for user
     */
    private function checkDailyLimit(int $userId): array
    {
        $redis = Redis::connection();
        $key = "frequency:user:{$userId}:day:" . now()->format('Y-m-d');
        $count = (int) $redis->get($key);

        return [
            'passed' => $count < self::MAX_NOTIFICATIONS_PER_DAY,
            'current' => $count,
            'limit' => self::MAX_NOTIFICATIONS_PER_DAY,
            'reason' => $count >= self::MAX_NOTIFICATIONS_PER_DAY 
                ? "Daily limit exceeded ({$count}/" . self::MAX_NOTIFICATIONS_PER_DAY . ')' 
                : 'Within daily limit',
        ];
    }

    /**
     * Check weekly limit for user
     */
    private function checkWeeklyLimit(int $userId): array
    {
        $redis = Redis::connection();
        $key = "frequency:user:{$userId}:week:" . now()->format('Y-W');
        $count = (int) $redis->get($key);

        return [
            'passed' => $count < self::MAX_NOTIFICATIONS_PER_WEEK,
            'current' => $count,
            'limit' => self::MAX_NOTIFICATIONS_PER_WEEK,
            'reason' => $count >= self::MAX_NOTIFICATIONS_PER_WEEK 
                ? "Weekly limit exceeded ({$count}/" . self::MAX_NOTIFICATIONS_PER_WEEK . ')' 
                : 'Within weekly limit',
        ];
    }

    /**
     * Check similar notification cooldown
     */
    private function checkSimilarNotificationCooldown(int $userId, string $notificationType): array
    {
        $redis = Redis::connection();
        $key = "frequency:similar:{$userId}:{$notificationType}";
        $lastSent = $redis->get($key);

        if (!$lastSent) {
            return [
                'passed' => true,
                'last_sent' => null,
                'cooldown_minutes' => self::MIN_MINUTES_BETWEEN_SIMILAR,
                'reason' => 'No similar notification sent recently',
            ];
        }

        $minutesSinceLast = now()->diffInMinutes(now()->parse($lastSent));

        return [
            'passed' => $minutesSinceLast >= self::MIN_MINUTES_BETWEEN_SIMILAR,
            'last_sent' => $lastSent,
            'cooldown_minutes' => self::MIN_MINUTES_BETWEEN_SIMILAR,
            'minutes_since_last' => $minutesSinceLast,
            'reason' => $minutesSinceLast >= self::MIN_MINUTES_BETWEEN_SIMILAR 
                ? 'Cooldown period passed' 
                : "Cooldown period not met ({$minutesSinceLast}/" . self::MIN_MINUTES_BETWEEN_SIMILAR . ' min)',
        ];
    }

    /**
     * Get user notification statistics
     */
    public function getUserNotificationStats(int $userId, int $tenantId): array
    {
        $redis = Redis::connection();
        $now = now();

        return [
            'hourly' => (int) $redis->get("frequency:user:{$userId}:hour:{$now->format('Y-m-d-H')}") ?? 0,
            'daily' => (int) $redis->get("frequency:user:{$userId}:day:{$now->format('Y-m-d')}") ?? 0,
            'weekly' => (int) $redis->get("frequency:user:{$userId}:week:{$now->format('Y-W')}") ?? 0,
            'limits' => [
                'hourly' => self::MAX_NOTIFICATIONS_PER_HOUR,
                'daily' => self::MAX_NOTIFICATIONS_PER_DAY,
                'weekly' => self::MAX_NOTIFICATIONS_PER_WEEK,
            ],
        ];
    }

    /**
     * Reset user frequency limits (admin function)
     */
    public function resetUserFrequency(int $userId, string $correlationId = ''): void
    {
        $redis = Redis::connection();
        $pattern = "frequency:user:{$userId}:*";
        $keys = $redis->keys($pattern);

        if (!empty($keys)) {
            $redis->del($keys);
        }

        $this->logAction(
            'user_frequency_reset',
            'User',
            $userId,
            [],
            $correlationId
        );

        $this->logger->channel('frequency')->info('User frequency limits reset', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Get tenant-wide frequency statistics
     */
    public function getTenantFrequencyStats(int $tenantId): array
    {
        $cacheKey = "frequency:tenant_stats:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            $today = now()->startOfDay();
            
            return [
                'total_notifications_today' => NotificationLog::where('tenant_id', $tenantId)
                    ->where('sent_at', '>=', $today)
                    ->count(),
                'unique_users_reached_today' => NotificationLog::where('tenant_id', $tenantId)
                    ->where('sent_at', '>=', $today)
                    ->distinct('user_id')
                    ->count('user_id'),
                'promotions_today' => NotificationCampaign::where('tenant_id', $tenantId)
                    ->where('type', NotificationCampaign::TYPE_PROMOTION)
                    ->where('sent_at', '>=', $today)
                    ->count(),
                'blocked_users_today' => 0, // Track blocked users separately if needed
            ];
        });
    }
}
