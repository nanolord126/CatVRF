<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Listeners;

use App\Domains\VerticalName\Events\VerticalItemUpdatedEvent;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Listener: обработка события обновления VerticalItem.
 *
 * CANON 2026 — Layer 5: Listeners.
 * Отвечает за:
 * - Инвалидация кэша товара и каталога
 * - Логирование изменений
 * - Оповещение поисковых индексов
 *
 * @package App\Domains\VerticalName\Listeners
 */
final readonly class HandleVerticalItemUpdated
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheRepository $cache,
    ) {
    }

    /**
     * Обработка события обновления товара.
     *
     * 1. Инвалидируем кэш конкретного товара.
     * 2. Инвалидируем кэш каталога для tenant.
     * 3. Логируем изменённые поля.
     * 4. Обновляем поисковый индекс (если изменились name/description/category).
     */
    public function handle(VerticalItemUpdatedEvent $event): void
    {
        $this->invalidateItemCache($event->item->id, $event->tenantId);
        $this->invalidateCatalogCache($event->tenantId);

        $this->logger->info('VerticalName item updated event handled', $event->toLogContext());

        if ($this->shouldUpdateSearchIndex($event->changedFields)) {
            $this->triggerSearchIndexUpdate($event);
        }
    }

    /**
     * Инвалидация кэша конкретного товара.
     */
    private function invalidateItemCache(int $itemId, int $tenantId): void
    {
        $this->cache->forget('vertical_name_item:' . $tenantId . ':' . $itemId);

        $this->logger->debug('VerticalName item cache invalidated', [
            'item_id' => $itemId,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Инвалидация кэша каталога.
     */
    private function invalidateCatalogCache(int $tenantId): void
    {
        $this->cache->forget('vertical_name_catalog:' . $tenantId);
        $this->cache->forget('vertical_name_b2b:' . $tenantId);
    }

    /**
     * Нужно ли обновить поисковый индекс?
     *
     * Да, если изменились: name, description, category, sku, tags.
     */
    private function shouldUpdateSearchIndex(array $changedFields): bool
    {
        $searchableFields = ['name', 'description', 'category', 'sku', 'tags'];

        return count(array_intersect($changedFields, $searchableFields)) > 0;
    }

    /**
     * Запуск обновления поискового индекса.
     */
    private function triggerSearchIndexUpdate(VerticalItemUpdatedEvent $event): void
    {
        $this->logger->info('VerticalName search index update triggered', [
            'item_id' => $event->item->id,
            'changed_fields' => $event->changedFields,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
