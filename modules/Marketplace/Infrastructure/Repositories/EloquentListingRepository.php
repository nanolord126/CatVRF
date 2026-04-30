<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Repositories;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent репозиторий витринных позиций
 */
final class EloquentListingRepository implements ListingRepositoryInterface
{
    private const TABLE = 'marketplace_listings';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function save(ProductListing $listing): void
    {
        $data = $listing->toArray();
        $data['price'] = json_encode($data['price']);
        $data['rating'] = json_encode($data['rating']);
        $data['categories'] = json_encode($data['categories']);
        $data['tags'] = json_encode($data['tags']);
        $data['images'] = json_encode($data['images']);
        $data['attributes'] = json_encode($data['attributes']);
        $data['metadata'] = json_encode($data['metadata']);

        $exists = $this->db->table(self::TABLE)
            ->where('uuid', $listing->uuid->toString())
            ->exists();

        if ($exists) {
            $this->db->table(self::TABLE)
                ->where('uuid', $listing->uuid->toString())
                ->update($data);

            $this->logger->debug('Listing updated', ['uuid' => $listing->uuid->toString()]);
        } else {
            $this->db->table(self::TABLE)->insert($data);

            $this->logger->debug('Listing created', ['uuid' => $listing->uuid->toString()]);
        }
    }

    public function findByUuid(UuidInterface $uuid): ?ProductListing
    {
        $record = $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findBySourceId(int $sourceId, string $sourceType): ?ProductListing
    {
        $record = $this->db->table(self::TABLE)
            ->where('source_id', $sourceId)
            ->where('source_type', $sourceType)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findActive(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->where('in_stock', true)
            ->orderBy('ranking_score', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findByVertical(VerticalSource $source): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('source', $source->value)
            ->where('status', 'published')
            ->orderBy('ranking_score', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findByCategory(string $category): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->whereJsonContains('categories', $category)
            ->orderBy('ranking_score', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function search(array $filters = []): array
    {
        $query = $this->db->table(self::TABLE)
            ->where('status', 'published');

        $query = $this->applyFilters($query, $filters);

        $records = $query
            ->orderBy('ranking_score', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function searchPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->db->table(self::TABLE)
            ->where('status', 'published');

        $query = $this->applyFilters($query, $filters);

        $total = $query->count();

        $records = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->orderBy('ranking_score', 'desc')
            ->get();

        return [
            'listings' => array_map([$this, 'mapToEntity'], $records->all()),
            'total' => $total,
        ];
    }

    public function findTopRanked(int $limit = 100): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->where('in_stock', true)
            ->orderBy('ranking_score', 'desc')
            ->limit($limit)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findRecommended(int $limit = 20): array
    {
        // Рекомендации на основе комбинированного score
        $records = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->where('in_stock', true)
            ->where('conversion_rate', '>', 0.02)
            ->orderBy('ranking_score', 'desc')
            ->limit($limit)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findFeatured(int $limit = 10): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->where('is_featured', true)
            ->where('in_stock', true)
            ->orderBy('ranking_score', 'desc')
            ->limit($limit)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function fullTextSearch(string $query, array $filters = []): array
    {
        $queryBuilder = $this->db->table(self::TABLE)
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', [$query]);
            });

        $queryBuilder = $this->applyFilters($queryBuilder, $filters);

        $records = $queryBuilder
            ->orderBy('ranking_score', 'desc')
            ->limit(50)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function delete(UuidInterface $uuid): void
    {
        $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->delete();

        $this->logger->debug('Listing deleted', ['uuid' => $uuid->toString()]);
    }

    public function batchUpdateRankings(array $rankings): void
    {
        foreach ($rankings as $uuid => $score) {
            $this->db->table(self::TABLE)
                ->where('uuid', $uuid)
                ->update([
                    'ranking_score' => $score,
                    'updated_at' => new \DateTime(),
                ]);
        }

        $this->logger->info('Batch rankings updated', ['count' => count($rankings)]);
    }

    public function getStatsByVertical(VerticalSource $source): array
    {
        $stats = $this->db->table(self::TABLE)
            ->where('source', $source->value)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_featured = ? THEN 1 ELSE 0 END) as featured
            ', ['published', true])
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
            'featured' => (int) ($stats->featured ?? 0),
        ];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['vertical'])) {
            $query->where('source', $filters['vertical']->value);
        }

        if (isset($filters['category'])) {
            $query->whereJsonContains('categories', $filters['category']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']->value);
        }

        if (isset($filters['min_price'])) {
            $query->whereJsonLength('price->amount', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->whereJsonLength('price->amount', '<=', $filters['max_price']);
        }

        if (isset($filters['in_stock'])) {
            $query->where('in_stock', $filters['in_stock']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['min_rating'])) {
            $query->whereJsonLength('rating->value', '>=', $filters['min_rating']);
        }

        if (!empty($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return $query;
    }

    private function mapToEntity(object $record): ProductListing
    {
        $data = (array) $record;
        $data['price'] = json_decode($data['price'], true);
        $data['rating'] = json_decode($data['rating'], true) ?: null;
        $data['categories'] = json_decode($data['categories'], true);
        $data['tags'] = json_decode($data['tags'], true);
        $data['images'] = json_decode($data['images'], true);
        $data['attributes'] = json_decode($data['attributes'], true);
        $data['metadata'] = json_decode($data['metadata'], true);

        return ProductListing::fromArray($data);
    }
}
