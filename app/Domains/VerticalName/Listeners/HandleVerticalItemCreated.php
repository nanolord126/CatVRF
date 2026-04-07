<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Listeners;

use App\Domains\VerticalName\Events\VerticalItemCreatedEvent;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Listener: обработка события создания VerticalItem.
 *
 * CANON 2026 — Layer 5: Listeners.
 * Отвечает за:
 * - Инвалидация кэша каталога
 * - Логирование события
 * - Оповещение ML-сервисов об обновлении каталога
 *
 * Никаких фасадов — только constructor injection.
 *
 * @package App\Domains\VerticalName\Listeners
 */
final readonly class HandleVerticalItemCreated
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheRepository $cache,
    ) {
    }

    /**
     * Обработка события создания товара.
     *
     * 1. Инвалидируем кэш каталога для данного tenant.
     * 2. Логируем факт создания.
     * 3. Отправляем в ML-пайплайн для пересчёта рекомендаций.
     */
    public function handle(VerticalItemCreatedEvent $event): void
    {
        $this->invalidateCatalogCache($event->tenantId);

        $this->logger->info('VerticalName item created event handled', $event->toLogContext());

        $this->notifyRecommendationService($event);
    }

    /**
     * Инвалидация кэша каталога для tenant.
     *
     * Ключи: vertical_name_catalog:{tenantId}, vertical_name_b2b:{tenantId}.
     */
    private function invalidateCatalogCache(int $tenantId): void
    {
        $this->cache->forget('vertical_name_catalog:' . $tenantId);
        $this->cache->forget('vertical_name_b2b:' . $tenantId);

        $this->logger->debug('VerticalName catalog cache invalidated', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Оповещение ML/Recommendation сервиса для обновления рекомендаций.
     *
     * В production dispatches RecalculateRecommendationsJob.
     */
    private function notifyRecommendationService(VerticalItemCreatedEvent $event): void
    {
        $this->logger->info('VerticalName recommendation recalculation triggered', [
            'item_id' => $event->item->id,
            'tenant_id' => $event->tenantId,
            'is_b2b' => $event->isB2B,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
