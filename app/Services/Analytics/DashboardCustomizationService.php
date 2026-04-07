<?php declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class DashboardCustomizationService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

    private const CACHE_TTL = 86400;  // 24 hours

        /**
         * Сохранить макет дашборда
         */
        public function saveDashboardLayout(
            int $userId,
            int $tenantId,
            array $widgets,
            array $context = []
        ): array {
            $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

            $layout = [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'widgets' => $widgets,
                'saved_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";
            $this->cache->put($cacheKey, $layout, self::CACHE_TTL);

            $this->logger->channel('audit')->info('Dashboard layout saved', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'widgets_count' => count($widgets),
            ]);

            return $layout;
        }

        /**
         * Получить макет дашборда
         */
        public function getDashboardLayout(int $userId, int $tenantId, array $context = []): array {
            $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
            $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";

            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Default layout
            $defaultLayout = [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'widgets' => [
                    ['id' => 'revenue-widget', 'size' => 'large', 'position' => 0],
                    ['id' => 'orders-widget', 'size' => 'medium', 'position' => 1],
                    ['id' => 'conversion-widget', 'size' => 'medium', 'position' => 2],
                    ['id' => 'aov-widget', 'size' => 'small', 'position' => 3],
                ],
                'saved_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $this->cache->put($cacheKey, $defaultLayout, self::CACHE_TTL);

            return $defaultLayout;
        }

        /**
         * Удалить кастомный макет
         */
        public function deleteDashboardLayout(int $userId, int $tenantId, array $context = []): bool {
            $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
            $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";

            $this->cache->forget($cacheKey);

            $this->logger->channel('audit')->info('Dashboard layout deleted', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ]);

            return true;
        }

        /**
         * Сбросить на дефолтный макет
         */
        public function resetToDefault(int $userId, int $tenantId, array $context = []): array {
            $this->deleteDashboardLayout($userId, $tenantId, $context);
            return $this->getDashboardLayout($userId, $tenantId, $context);
        }

        /**
         * Сохранить имя дашборда
         */
        public function saveDashboardName(
            int $userId,
            int $tenantId,
            string $name,
            array $context = []
        ): array {
            $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

            $nameData = [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'name' => $name,
                'saved_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $cacheKey = "dashboard:name:{$tenantId}:{$userId}";
            $this->cache->put($cacheKey, $nameData, self::CACHE_TTL);

            $this->logger->channel('audit')->info('Dashboard name saved', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'name' => $name,
            ]);

            return $nameData;
        }
}
