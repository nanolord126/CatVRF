<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;

final class SuspiciousBehaviorDetector
{
    private const CACHE_PREFIX = 'suspicious_behavior:';
    private const BLOCK_DURATION_MINUTES = 60;
    private const RATE_LIMIT_WINDOW_MINUTES = 5;
    private const MAX_REQUESTS_PER_WINDOW = 10;

    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Проверяет пользователя на подозрительное поведение перед доступом к примерочной
     */
    public function checkLingerieFittingAccess(int $userId, string $userGender, string $correlationId = ''): array
    {
        // Проверка блокировки
        if ($this->isUserBlocked($userId)) {
            $this->logger->warning('Blocked user attempted lingerie fitting access', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'reason' => 'User is blocked',
            ]);

            return [
                'allowed' => false,
                'reason' => 'account_blocked',
                'message' => 'Доступ временно ограничен. Обратитесь в поддержку.',
                'block_expires_at' => $this->getBlockExpiration($userId),
            ];
        }

        // Проверка пола пользователя
        if ($userGender !== 'female') {
            $this->logger->channel('security')->warning('Non-female user attempted lingerie fitting access', [
                'user_id' => $userId,
                'user_gender' => $userGender,
                'correlation_id' => $correlationId,
            ]);

            $this->recordSuspiciousActivity($userId, 'gender_mismatch', $correlationId);
            
            return [
                'allowed' => false,
                'reason' => 'gender_restriction',
                'message' => 'Эта функция доступна только для женских аккаунтов',
            ];
        }

        // Проверка rate limiting
        if ($this->exceedsRateLimit($userId)) {
            $this->logger->channel('security')->warning('User exceeded rate limit for lingerie fitting', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            $this->recordSuspiciousActivity($userId, 'rate_limit_exceeded', $correlationId);
            
            return [
                'allowed' => false,
                'reason' => 'rate_limit',
                'message' => 'Слишком много попыток. Подождите несколько минут.',
            ];
        }

        // Проверка на подозрительные паттерны поведения
        $suspicionScore = $this->calculateSuspicionScore($userId);
        
        if ($suspicionScore >= 80) {
            $this->blockUser($userId, 'high_suspicion_score', $correlationId);
            
            $this->logger->channel('security')->critical('User blocked due to high suspicion score', [
                'user_id' => $userId,
                'suspicion_score' => $suspicionScore,
                'correlation_id' => $correlationId,
            ]);

            return [
                'allowed' => false,
                'reason' => 'suspicious_behavior',
                'message' => 'Доступ ограничен в целях безопасности',
            ];
        }

        if ($suspicionScore >= 50) {
            $this->logger->channel('security')->warning('User has elevated suspicion score', [
                'user_id' => $userId,
                'suspicion_score' => $suspicionScore,
                'correlation_id' => $correlationId,
            ]);

            return [
                'allowed' => true,
                'warning' => 'elevated_suspicion',
                'message' => 'Ваша активность мониторится',
            ];
        }

        // Записываем успешный доступ
        $this->recordSuccessfulAccess($userId);

        return [
            'allowed' => true,
            'message' => 'Доступ разрешен',
        ];
    }

    /**
     * Записывает подозрительную активность
     */
    public function recordSuspiciousActivity(int $userId, string $reason, string $correlationId = ''): void
    {
        $key = self::CACHE_PREFIX . "suspicious:{$userId}";
        $activities = $this->cache->get($key, []);
        
        $activities[] = [
            'timestamp' => now()->toIso8601String(),
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ];

        // Храним только последние 50 записей
        if (count($activities) > 50) {
            $activities = array_slice($activities, -50);
        }

        $this->cache->put($key, $activities, now()->addDays(30));

        $this->logger->channel('security')->info('Suspicious activity recorded', [
            'user_id' => $userId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Рассчитывает score подозрительности на основе паттернов поведения
     */
    private function calculateSuspicionScore(int $userId): int
    {
        $score = 0;
        
        // Проверка частоты запросов
        $recentRequests = $this->getRecentRequests($userId, 60); // за последний час
        if ($recentRequests > 30) {
            $score += 30;
        } elseif ($recentRequests > 20) {
            $score += 20;
        } elseif ($recentRequests > 10) {
            $score += 10;
        }

        // Проверка подозрительной активности
        $suspiciousActivities = $this->getSuspiciousActivities($userId);
        $recentSuspicious = array_filter($suspiciousActivities, function ($activity) {
            return now()->subMinutes(60)->lte($activity['timestamp']);
        });

        $score += count($recentSuspicious) * 15;

        // Проверка на аномальные паттерны времени
        $nightActivity = $this->checkNightActivity($userId);
        if ($nightActivity) {
            $score += 20;
        }

        // Проверка на множественные сессии
        $multipleSessions = $this->checkMultipleSessions($userId);
        if ($multipleSessions) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Проверяет, заблокирован ли пользователь
     */
    private function isUserBlocked(int $userId): bool
    {
        $key = self::CACHE_PREFIX . "blocked:{$userId}";
        return Cache::has($key);
    }

    /**
     * Блокирует пользователя
     */
    private function blockUser(int $userId, string $reason, string $correlationId = ''): void
    {
        $key = self::CACHE_PREFIX . "blocked:{$userId}";
        $expiration = now()->addMinutes(self::BLOCK_DURATION_MINUTES);
        
        $this->cache->put($key, [
            'blocked_at' => now()->toIso8601String(),
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ], $expiration);

        $this->logger->channel('security')->critical('User blocked', [
            'user_id' => $userId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
            'expires_at' => $expiration->toIso8601String(),
        ]);
    }

    /**
     * Получает время окончания блокировки
     */
    private function getBlockExpiration(int $userId): ?string
    {
        $key = self::CACHE_PREFIX . "blocked:{$userId}";
        $blockData = Cache::get($key);
        
        return $blockData ? now()->addMinutes(self::BLOCK_DURATION_MINUTES)->toIso8601String() : null;
    }

    /**
     * Проверяет превышение rate limit
     */
    private function exceedsRateLimit(int $userId): bool
    {
        $key = self::CACHE_PREFIX . "ratelimit:{$userId}";
        $requests = Cet($::y, 0);
        
        return $requests >= self::MAX_REQUESTS_PER_WINDOW;
    }

    /**
     * Записывает успешный доступ
     */
    private function recordSuccessfulAccess(int $userId): void
    {
        $key = self::CACHE_PREFIX . "ratelimit:{$userId}";
        $requests = $this->cache->get($key, 0);
        $this->cache->put($key, $requests + 1, now()->addMinutes(self::RATE_LIMIT_WINDOW_MINUTES));
    }

    /**
     * Получает количество недавних запросов
     */
    private function getRecentRequests(int $userId, int $minutes): int
    {
        $key = self::CACHE_PREFIX . "requests:{$userId}";
        $requests = $this->cache->get($key, []);
        
        $cutoff = now()->subMinutes($minutes);
        $recent = array_filter($requests, function ($request) use ($cutoff) {
            return $cutoff->lte($request['timestamp']);
        });

        $this->cache->put($key, $recent, now()->addHours(1));

        return count($recent);
    }

    /**
     * Получает подозрительную активность пользователя
     */
    private function getSuspiciousActivities(int $userId): array
    {
        $key = self::CACHE_PREFIX . "suspicious:{$userId}";
        return $this->cache->get($key, []);
    }

    /**
     * Проверяет ночную активность
     */
    private function checkNightActivity(int $userId): bool
    {
        $hour = now()->hour;
        
        // Ночная активность с 00:00 до 05:00
        if ($hour >= 0 && $hour < 5) {
            $key = self::CACHE_PREFIX . "night_activity:{$userId}:" . now()->format('Y-m-d');
            $count = $this->cache->get($key, 0);
            
            if ($count > 5) {
                return true;
            }
            
            $this->cache->put($key, $count + 1, now()->endOfDay());
        }

        return false;
    }

    /**
     * Проверяет множественные сессии
     */
    private function checkMultipleSessions(int $userId): bool
    {
        // Упрощенная проверка - в реальном проекте нужно использовать session management
        $key = self::CACHE_PREFIX . "sessions:{$userId}";
        $sessions = Cache::get($key, []);
        
        // Если более 3 активных сессий за последние 10 минут
        $recentSessions = array_filter($sessions, function ($session) {
            return now()->subMinutes(10)->lte($session['timestamp']);
        });

        return count($recentSessions) > 3;
    }

    /**
     * Разблокирует пользователя (для админа)
     */
    public function unblockUser(int $userId, string $adminReason): bool
    {
        $key = self::CACHE_PREFIX . "blocked:{$userId}";
        
        if (!$this->cache->has($key)) {
            return false;
        }

        $this->cache->forget($key);

        $this->logger->channel('security')->info('User unblocked by admin', [
            'user_id' => $userId,
            'admin_reason' => $adminReason,
        ]);

        return true;
    }

    /**
     * Получает статистику подозрительной активности пользователя
     */
    public function getUserSuspicionStats(int $userId): array
    {
        return [
            'is_blocked' => $this->isUserBlocked($userId),
            'block_expires_at' => $this->getBlockExpiration($userId),
            'suspicious_activities' => $this->getSuspiciousActivities($userId),
            'suspicion_score' => $this->calculateSuspicionScore($userId),
            'recent_requests' => $this->getRecentRequests($userId, 60),
        ];
    }
}
