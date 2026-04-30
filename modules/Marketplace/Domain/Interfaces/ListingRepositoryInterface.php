<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Interfaces;

use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Интерфейс репозитория витринных позиций
 */
interface ListingRepositoryInterface
{
    /**
     * Сохранить позицию
     */
    public function save(ProductListing $listing): void;

    /**
     * Найти по UUID
     */
    public function findByUuid(UuidInterface $uuid): ?ProductListing;

    /**
     * Найти по source ID
     */
    public function findBySourceId(int $sourceId, string $sourceType): ?ProductListing;

    /**
     * Получить все активные позиции
     *
     * @return ProductListing[]
     */
    public function findActive(): array;

    /**
     * Получить позиции по вертикали
     *
     * @return ProductListing[]
     */
    public function findByVertical(VerticalSource $source): array;

    /**
     * Получить позиции по категории
     *
     * @return ProductListing[]
     */
    public function findByCategory(string $category): array;

    /**
     * Поиск с фильтрами
     *
     * @param array{
     *   vertical?: VerticalSource,
     *   category?: string,
     *   type?: string,
     *   min_price?: float,
     *   max_price?: float,
     *   in_stock?: bool,
     *   is_featured?: bool,
     *   min_rating?: float,
     *   tags?: string[],
     *   tenant_id?: int,
     * } $filters
     * @return ProductListing[]
     */
    public function search(array $filters = []): array;

    /**
     * Пагинированный поиск
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array{listings: ProductListing[], total: int}
     */
    public function searchPaginated(array $filters = [], int $page = 1, int $perPage = 20): array;

    /**
     * Получить топ позиций по рейтингу
     *
     * @return ProductListing[]
     */
    public function findTopRanked(int $limit = 100): array;

    /**
     * Получить рекомендуемые позиции
     *
     * @return ProductListing[]
     */
    public function findRecommended(int $limit = 20): array;

    /**
     * Получить featured позиции
     *
     * @return ProductListing[]
     */
    public function findFeatured(int $limit = 10): array;

    /**
     * Полно-textовый поиск
     *
     * @return ProductListing[]
     */
    public function fullTextSearch(string $query, array $filters = []): array;

    /**
     * Удалить позицию
     */
    public function delete(UuidInterface $uuid): void;

    /**
     * Массовое обновление рейтингов
     *
     * @param array<string, float> $rankings [uuid => score]
     */
    public function batchUpdateRankings(array $rankings): void;

    /**
     * Получить статистику по вертикали
     *
     * @return array{total: int, active: int, featured: int}
     */
    public function getStatsByVertical(VerticalSource $source): array;
}
