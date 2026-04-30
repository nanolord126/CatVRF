<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Interfaces;

use Modules\Marketplace\Domain\Entities\MarketplaceCategory;
use Ramsey\Uuid\UuidInterface;

/**
 * Интерфейс репозитория категорий маркетплейса
 */
interface CategoryRepositoryInterface
{
    /**
     * Сохранить категорию
     */
    public function save(MarketplaceCategory $category): void;

    /**
     * Найти по UUID
     */
    public function findByUuid(UuidInterface $uuid): ?MarketplaceCategory;

    /**
     * Найти по slug
     */
    public function findBySlug(string $slug): ?MarketplaceCategory;

    /**
     * Получить корневые категории
     *
     * @return MarketplaceCategory[]
     */
    public function findRoot(): array;

    /**
     * Получить дочерние категории
     *
     * @return MarketplaceCategory[]
     */
    public function findChildren(UuidInterface $parentUuid): array;

    /**
     * Получить дерево категорий
     *
     * @return MarketplaceCategory[]
     */
    public function findTree(): array;

    /**
     * Получить активные категории
     *
     * @return MarketplaceCategory[]
     */
    public function findActive(): array;

    /**
     * Получить категории по вертикали
     *
     * @return MarketplaceCategory[]
     */
    public function findByVertical(string $vertical): array;

    /**
     * Удалить категорию
     */
    public function delete(UuidInterface $uuid): void;
}
