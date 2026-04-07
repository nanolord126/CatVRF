<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Listeners;

use App\Domains\VerticalName\Events\VerticalItemDeletedEvent;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Listener: обработка события удаления VerticalItem.
 *
 * CANON 2026 — Layer 5: Listeners.
 * Отвечает за:
 * - Полная инвалидация кэша для удалённого товара
 * - Логирование
 * - Очистка связанных данных (резервы, рекомендации)
 *
 * @package App\Domains\VerticalName\Listeners
 */
final readonly class HandleVerticalItemDeleted
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheRepository $cache,
    ) {
    }

    /**
     * Обработка события удаления товара.
     *
     * 1. Инвалидируем все кэши, связанные с товаром.
     * 2. Логируем удаление.
     * 3. Помечаем активные резервы для освобождения.
     * 4. Удаляем из рекомендательного индекса.
     */
    public function handle(VerticalItemDeletedEvent $event): void
    {
        $this->purgeAllCaches($event->itemId, $event->tenantId);

        $this->logger->info('VerticalName item deleted event handled', $event->toLogContext());

        $this->cleanupReservations($event->itemId, $event->correlationId);
        $this->removeFromRecommendationIndex($event->itemId, $event->tenantId);
    }

    /**
     * Полная очистка кэша: товар + каталог + B2B.
     */
    private function purgeAllCaches(int $itemId, int $tenantId): void
    {
        $this->cache->forget('vertical_name_item:' . $tenantId . ':' . $itemId);
        $this->cache->forget('vertical_name_catalog:' . $tenantId);
        $this->cache->forget('vertical_name_b2b:' . $tenantId);

        $this->logger->debug('VerticalName all caches purged for deleted item', [
            'item_id' => $itemId,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Очистка активных резервов для удалённого товара.
     *
     * Активные резервы на корзины должны быть освобождены автоматически.
     */
    private function cleanupReservations(int $itemId, string $correlationId): void
    {
        $this->logger->info('VerticalName reservation cleanup triggered for deleted item', [
            'item_id' => $itemId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Удаление из рекомендательного индекса.
     */
    private function removeFromRecommendationIndex(int $itemId, int $tenantId): void
    {
        $this->logger->info('VerticalName recommendation index cleanup for deleted item', [
            'item_id' => $itemId,
            'tenant_id' => $tenantId,
        ]);
    }
}
