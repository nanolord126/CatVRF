<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * DashboardCustomizationService — сохранение и управление кастомными дашбордами
 * 
 * Методы:
 * - saveDashboardLayout(userId, tenantId, widgets)
 * - getDashboardLayout(userId, tenantId)
 * - deleteDashboardLayout(userId, tenantId)
 * - resetToDefault(userId, tenantId)
 * - saveDashboardName(userId, tenantId, name)
 */
final class DashboardCustomizationService
{
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
        $correlationId = $context['correlation_id'] ?? Str::uuid();

        $layout = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'widgets' => $widgets,
            'saved_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";
        Cache::put($cacheKey, $layout, self::CACHE_TTL);

        Log::channel('audit')->info('Dashboard layout saved', [
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
        $correlationId = $context['correlation_id'] ?? Str::uuid();
        $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";

        $cached = Cache::get($cacheKey);
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

        Cache::put($cacheKey, $defaultLayout, self::CACHE_TTL);

        return $defaultLayout;
    }

    /**
     * Удалить кастомный макет
     */
    public function deleteDashboardLayout(int $userId, int $tenantId, array $context = []): bool {
        $correlationId = $context['correlation_id'] ?? Str::uuid();
        $cacheKey = "dashboard:layout:{$tenantId}:{$userId}";

        Cache::forget($cacheKey);

        Log::channel('audit')->info('Dashboard layout deleted', [
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
        $correlationId = $context['correlation_id'] ?? Str::uuid();

        $nameData = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'saved_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $cacheKey = "dashboard:name:{$tenantId}:{$userId}";
        Cache::put($cacheKey, $nameData, self::CACHE_TTL);

        Log::channel('audit')->info('Dashboard name saved', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'name' => $name,
        ]);

        return $nameData;
    }
}
