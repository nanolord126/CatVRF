<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

/**
 * Интерфейс адаптера для интеграции с вертикалью
 * Каждый адаптер реализует логику получения данных из конкретной вертикали
 */
interface VerticalAdapterInterface
{
    /**
     * Получить источники данных из вертикали
     *
     * @param array $filters Фильтры для выборки
     * @param int $limit Лимит записей
     * @return array<array{id: int, title: string, description: string, price: float, currency: string, category: string, type: string, tags: array, images: array, attributes: array, metadata: array, tenant_id: int, business_group_id: int}>
     */
    public function fetchSources(array $filters = [], int $limit = 100): array;

    /**
     * Получить один источник по ID
     *
     * @param int $sourceId ID источника в вертикали
     * @param array $filters Дополнительные фильтры
     * @return array|null
     */
    public function fetchSingle(int $sourceId, array $filters = []): ?array;

    /**
     * Получить количество доступных источников
     *
     * @param array $filters Фильтры
     */
    public function countSources(array $filters = []): int;

    /**
     * Проверить валидность источника
     *
     * @param array $source Данные источника
     */
    public function validateSource(array $source): bool;

    /**
     * Получить поддерживаемые типы сущностей
     *
     * @return string[]
     */
    public function getSupportedEntityTypes(): array;

    /**
     * Получить дефолтные фильтры для вертикали
     *
     * @return array
     */
    public function getDefaultFilters(): array;
}
