<?php declare(strict_types=1);

namespace App\Services\Security;


use Illuminate\Support\Facades\Redis;
use Illuminate\Log\LogManager;

final readonly class RateLimiterService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    private const SLIDING_WINDOW_TTL = 60;      // 1 минута для sliding window
        private const BURST_LIMIT_THRESHOLD = 3;    // Количество отказов перед exponential backoff
        private const BURST_TEMP_BAN_MINUTES = 5;   // Временный бан на 5 минут

        /**
         * Проверить rate limit для инициации платежа.
         * Limit: 10 попыток в минуту на пользователя
         *
         * @param int $tenantId
         * @param int $userId
         * @param string $correlationId
         * @return bool
         */
        public function checkPaymentInit(
            int $tenantId,
            int $userId,
            string $correlationId = ''
        ): bool {
            return $this->checkSlidingWindow(
                key: "rate_limit:payment_init:{$tenantId}:{$userId}",
                limit: 10,
                windowSeconds: 60,
                correlationId: $correlationId,
                operation: 'payment_init'
            );
        }

        /**
         * Проверить rate limit для применения промо.
         * Limit: 50 попыток в минуту на тенант
         *
         * @param int $tenantId
         * @param string $correlationId
         * @return bool
         */
        public function checkPromoApply(
            int $tenantId,
            string $correlationId = ''
        ): bool {
            return $this->checkSlidingWindow(
                key: "rate_limit:promo_apply:{$tenantId}",
                limit: 50,
                windowSeconds: 60,
                correlationId: $correlationId,
                operation: 'promo_apply'
            );
        }

        /**
         * Проверить rate limit для оплаты вишлиста.
         * Limit: 20 попыток в минуту на пользователя
         *
         * @param int $userId
         * @param string $correlationId
         * @return bool
         */
        public function checkWishlistPay(
            int $userId,
            string $correlationId = ''
        ): bool {
            return $this->checkSlidingWindow(
                key: "rate_limit:wishlist_pay:{$userId}",
                limit: 20,
                windowSeconds: 60,
                correlationId: $correlationId,
                operation: 'wishlist_pay'
            );
        }

        /**
         * Проверить rate limit для поиска (включая ML-запросы).
         *
         * Light search: 1000 запросов в час
         * Heavy search (с ML): 100 запросов в час
         *
         * @param int $userId
         * @param bool $isHeavy Включает ли запрос ML-операции
         * @param string $correlationId
         * @return bool
         */
        public function checkSearch(
            int $userId,
            bool $isHeavy = false,
            string $correlationId = ''
        ): bool {
            $limit = $isHeavy ? 100 : 1000;
            $windowSeconds = 3600;  // 1 час
            $operation = $isHeavy ? 'search_heavy' : 'search_light';

            return $this->checkSlidingWindow(
                key: "rate_limit:{$operation}:{$userId}",
                limit: $limit,
                windowSeconds: $windowSeconds,
                correlationId: $correlationId,
                operation: $operation
            );
        }

        /**
         * Проверить rate limit для реферального заявления.
         * Limit: 5 попыток в час на пользователя
         *
         * @param int $userId
         * @param string $correlationId
         * @return bool
         */
        public function checkReferralClaim(
            int $userId,
            string $correlationId = ''
        ): bool {
            return $this->checkSlidingWindow(
                key: "rate_limit:referral_claim:{$userId}",
                limit: 5,
                windowSeconds: 3600,  // 1 час
                correlationId: $correlationId,
                operation: 'referral_claim'
            );
        }

        /**
         * Проверить rate limit для webhook retry.
         * Limit: 100 попыток в час на тенант
         *
         * @param int $tenantId
         * @param string $provider
         * @param string $correlationId
         * @return bool
         */
        public function checkWebhookRetry(
            int $tenantId,
            string $provider,
            string $correlationId = ''
        ): bool {
            return $this->checkSlidingWindow(
                key: "rate_limit:webhook_retry:{$tenantId}:{$provider}",
                limit: 100,
                windowSeconds: 3600,
                correlationId: $correlationId,
                operation: 'webhook_retry'
            );
        }

        /**
         * Проверить, является ли пользователь временно забанен (burst protection).
         *
         * @param int $tenantId
         * @param int $userId
         * @return bool true если пользователь забанен
         */
        public function isBurstBanned(int $tenantId, int $userId): bool
        {
            return Redis::exists("burst_ban:{$tenantId}:{$userId}") === 1
                || Redis::exists("rate_limit:payment_init:{$tenantId}:{$userId}:burst_ban") === 1;
        }

        /**
         * Получить информацию о remaining attempts для клиента.
         *
         * @param string $operation
         * @param int $limit
         * @param int $tenantIdOrUserId
         * @return array ['remaining' => int, 'reset_at' => Carbon]
         */
        public function getRemaining(
            string $operation,
            int $limit,
            int $tenantIdOrUserId
        ): array {
            $key = "rate_limit:{$operation}:{$tenantIdOrUserId}";

            $attempts = Redis::zcard($key);
            $remaining = max(0, $limit - $attempts);

            $resetAt = Redis::ttl($key);
            $resetSeconds = $resetAt > 0 ? $resetAt : 0;

            return [
                'remaining' => $remaining,
                'reset_in_seconds' => $resetSeconds,
                'limit' => $limit,
            ];
        }

        /**
         * Основной метод: проверить rate limit с sliding window алгоритмом.
         *
         * Алгоритм:
         * 1. Проверить, не в ли пользователь burst-ban списке
         * 2. Добавить текущее время в Redis list
         * 3. Удалить старые записи за пределами окна
         * 4. Если количество >= лимита → отказать
         * 5. Отслеживать количество отказов для burst protection
         *
         * @param string $key Redis key
         * @param int $limit Максимум попыток
         * @param int $windowSeconds Размер окна в секундах
         * @param string $correlationId
         * @param string $operation
         * @return bool
         */
        private function checkSlidingWindow(
            string $key,
            int $limit,
            int $windowSeconds,
            string $correlationId = '',
            string $operation = 'unknown'
        ): bool {
            // Проверить burst ban
            if (Redis::exists("{$key}:burst_ban") === 1) {
                $this->logger->channel('fraud_alert')->warning('Rate limit burst ban active', [
                    'key' => $key,
                    'operation' => $operation,
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            $now = now()->timestamp;
            $windowStart = $now - $windowSeconds;
            $member = (string) $now . ':' . (string) random_int(1000, 9999);

            Redis::zadd($key, [$member => $now]);

            // Установить TTL на ключ
            Redis::expire($key, $windowSeconds + 60);

            // Удалить старые записи
            Redis::zremrangebyscore($key, 0, $windowStart);

            // Получить текущее количество попыток в окне
            $attempts = Redis::zcard($key);

            if ($attempts > $limit) {
                // Лимит превышен
                $this->handleRateLimitExceeded($key, $operation, $correlationId);
                return false;
            }

            return true;
        }

        /**
         * Обработать превышение rate limit.
         *
         * Если количество отказов > BURST_LIMIT_THRESHOLD → применить burst protection.
         *
         * @param string $key
         * @param string $operation
         * @param string $correlationId
         * @return void
         */
        private function handleRateLimitExceeded(
            string $key,
            string $operation,
            string $correlationId
        ): void {
            $rejectionKey = "{$key}:rejections";

            // Инкрементировать счётчик отказов
            $rejectionCount = Redis::incr($rejectionKey);
            Redis::expire($rejectionKey, 60);  // TTL 1 минута

            // Если много отказов → применить burst protection
            if ($rejectionCount > self::BURST_LIMIT_THRESHOLD) {
                Redis::setex(
                    "{$key}:burst_ban",
                    self::BURST_TEMP_BAN_MINUTES * 60,
                    now()->toDateTimeString()
                );

                $this->logger->channel('fraud_alert')->warning('Rate limit burst protection activated', [
                    'key' => $key,
                    'operation' => $operation,
                    'rejection_count' => $rejectionCount,
                    'ban_duration_minutes' => self::BURST_TEMP_BAN_MINUTES,
                    'correlation_id' => $correlationId,
                ]);

                return;
            }

            $this->logger->channel('security')->warning('Rate limit exceeded', [
                'key' => $key,
                'operation' => $operation,
                'rejection_count' => $rejectionCount,
                'correlation_id' => $correlationId,
            ]);
        }

        /**
         * Очистить все rate limit ключи для пользователя (при запросе).
         *
         * @param int $userId
         * @return int Количество очищенных ключей
         */
        public function clearUserLimits(int $userId): int
        {
            $pattern = "rate_limit:*:{$userId}*";
            $keys = Redis::keys($pattern);

            if (empty($keys)) {
                return 0;
            }

            return Redis::del(...$keys);
        }

        /**
         * Базовая эвристика подозрительной активности.
         * Возвращает true, если пользователь превышает мягкий лимит burst-событий.
         */
        public function isSuspicious(int $userId, string $operationType): bool
        {
            $key = "rate_limit:suspicious:{$operationType}:{$userId}";
            $now = now()->timestamp;

            Redis::zadd($key, [(string) $now . ':' . (string) random_int(1000, 9999) => $now]);
            Redis::zremrangebyscore($key, 0, $now - self::SLIDING_WINDOW_TTL);
            Redis::expire($key, self::SLIDING_WINDOW_TTL + 10);

            return Redis::zcard($key) > 30;
        }
}
