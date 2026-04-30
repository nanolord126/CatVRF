<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Repositories;

use Illuminate\Database\ConnectionInterface;
use Modules\Marketplace\Domain\Entities\MarketplaceCategory;
use Modules\Marketplace\Domain\Interfaces\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent репозиторий категорий маркетплейса
 */
final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    private const TABLE = 'marketplace_categories';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function save(MarketplaceCategory $category): void
    {
        $data = $category->toArray();
        $data['parent_uuid'] = $data['parent_uuid'] ?? null;
        $data['verticals'] = json_encode($data['verticals']);
        $data['attributes'] = json_encode($data['attributes']);
        $data['metadata'] = json_encode($data['metadata']);

        $exists = $this->db->table(self::TABLE)
            ->where('uuid', $category->uuid->toString())
            ->exists();

        if ($exists) {
            $this->db->table(self::TABLE)
                ->where('uuid', $category->uuid->toString())
                ->update($data);

            $this->logger->debug('Category updated', ['uuid' => $category->uuid->toString()]);
        } else {
            $this->db->table(self::TABLE)->insert($data);

            $this->logger->debug('Category created', ['uuid' => $category->uuid->toString()]);
        }
    }

    public function findByUuid(UuidInterface $uuid): ?MarketplaceCategory
    {
        $record = $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findBySlug(string $slug): ?MarketplaceCategory
    {
        $record = $this->db->table(self::TABLE)
            ->where('slug', $slug)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findRoot(): array
    {
        $records = $this->db->table(self::TABLE)
            ->whereNull('parent_uuid')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findChildren(UuidInterface $parentUuid): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('parent_uuid', $parentUuid->toString())
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findTree(): array
    {
        $roots = $this->findRoot();
        $tree = [];

        foreach ($roots as $root) {
            $tree[] = $this->buildTree($root);
        }

        return $tree;
    }

    public function findActive(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('is_active', true)
            ->orderBy('level', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findByVertical(string $vertical): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('is_active', true)
            ->whereJsonContains('verticals', $vertical)
            ->orderBy('level', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function delete(UuidInterface $uuid): void
    {
        $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->delete();

        $this->logger->debug('Category deleted', ['uuid' => $uuid->toString()]);
    }

    private function buildTree(MarketplaceCategory $category): MarketplaceCategory
    {
        $children = $this->findChildren($category->uuid);

        if (!empty($children)) {
            foreach ($children as $child) {
                $this->buildTree($child);
            }
        }

        return $category;
    }

    private function mapToEntity(object $record): MarketplaceCategory
    {
        $data = (array) $record;
        $data['verticals'] = json_decode($data['verticals'], true);
        $data['attributes'] = json_decode($data['attributes'], true);
        $data['metadata'] = json_decode($data['metadata'], true);

        return MarketplaceCategory::fromArray($data);
    }
}
