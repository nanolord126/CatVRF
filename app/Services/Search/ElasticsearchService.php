<?php declare(strict_types=1);

namespace App\Services\Search;

use App\Domains\Search\Models\SearchIndex;
use App\Domains\Search\Services\SearchService as DomainSearchService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ElasticsearchService - wrapper service for search functionality.
 * 
 * This service provides an Elasticsearch-like API but internally uses
 * the domain SearchService and SearchIndex model for database-based search.
 * This allows the V1 SearchController to work without requiring actual Elasticsearch.
 */
final readonly class ElasticsearchService
{
    public function __construct(
        private readonly DomainSearchService $domainSearchService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Perform search across all verticals
     */
    public function search(
        string $query,
        array $filters = [],
        string $sort = 'relevance',
        int $page = 1,
        int $perPage = 20,
    ): array {
        try {
            $tenantId = $filters['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : 1);
            $vertical = $filters['vertical'] ?? null;
            $category = $filters['category'] ?? null;
            $minPrice = $filters['min_price'] ?? null;
            $maxPrice = $filters['max_price'] ?? null;
            $geo = $filters['geo'] ?? null;

            // Build query
            $searchQuery = SearchIndex::where('tenant_id', $tenantId)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('content', 'like', "%{$query}%");
                });

            // Filter by vertical (from metadata)
            if ($vertical) {
                $searchQuery->whereJsonContains('metadata->vertical', $vertical);
            }

            // Filter by category (from metadata)
            if ($category) {
                $searchQuery->whereJsonContains('metadata->category', $category);
            }

            // Filter by price range (from metadata)
            if ($minPrice !== null) {
                $searchQuery->whereJsonContains('metadata->price', $minPrice, '>=');
            }

            if ($maxPrice !== null) {
                $searchQuery->whereJsonContains('metadata->price', $maxPrice, '<=');
            }

            // Apply sorting
            $this->applySorting($searchQuery, $sort);

            // Get total count before pagination
            $total = $searchQuery->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $results = $searchQuery
                ->offset($offset)
                ->limit($perPage)
                ->get()
                ->map(function ($item) {
                    return $this->formatSearchResult($item);
                })
                ->toArray();

            return [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'results' => $results,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Search error', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get search suggestions
     */
    public function suggestions(
        string $query,
        ?string $vertical = null,
        int $limit = 5,
        int $tenantId = 1,
    ): array {
        try {
            $searchQuery = SearchIndex::where('tenant_id', $tenantId)
                ->where('title', 'like', "{$query}%")
                ->orderBy('ranking_score', 'desc');

            if ($vertical) {
                $searchQuery->whereJsonContains('metadata->vertical', $vertical);
            }

            $results = $searchQuery
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'text' => $item->title,
                        'vertical' => $item->metadata['vertical'] ?? null,
                        'category' => $item->metadata['category'] ?? null,
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Throwable $e) {
            $this->logger->error('Search suggestions error', [
                'query' => $query,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Index a document
     */
    public function index(string $type, int $id, array $data): void
    {
        $this->domainSearchService->index(
            type: $type,
            id: $id,
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Remove a document from index
     */
    public function remove(string $type, int $id): void
    {
        $this->domainSearchService->remove(type: $type, id: $id);
    }

    /**
     * Rebuild index for a type
     */
    public function rebuild(string $type): void
    {
        $this->domainSearchService->rebuild(type: $type);
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, string $sort): void
    {
        switch ($sort) {
            case 'rating':
                $query->orderByJsonLength('metadata->rating', 'desc');
                break;
            case 'price_asc':
                $query->orderByJsonLength('metadata->price', 'asc');
                break;
            case 'price_desc':
                $query->orderByJsonLength('metadata->price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'relevance':
            default:
                $query->orderBy('ranking_score', 'desc');
                break;
        }
    }

    /**
     * Format search result
     */
    private function formatSearchResult($item): array
    {
        $metadata = $item->metadata ?? [];

        return [
            'id' => $item->uuid,
            'vertical' => $metadata['vertical'] ?? null,
            'type' => $item->searchable_type,
            'title' => $item->title,
            'description' => $item->content,
            'rating' => $metadata['rating'] ?? null,
            'price' => $metadata['price'] ?? null,
            'image' => $metadata['image'] ?? null,
            'location' => $metadata['location'] ?? null,
            'searchable_id' => $item->searchable_id,
            'searchable_type' => $item->searchable_type,
        ];
    }
}
