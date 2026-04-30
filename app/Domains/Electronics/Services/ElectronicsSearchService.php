<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Electronics\DTOs\SearchRequestDto;
use App\Domains\Electronics\DTOs\SearchResponseDto;
use App\Domains\Electronics\DTOs\FilterDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Services\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final readonly class ElectronicsSearchService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function search(SearchRequestDto $dto): SearchResponseDto
    {
        $startTime = microtime(true);

        $this->fraud->check(
            userId: 0,
            operationType: 'electronics_search',
            amount: 0,
            correlationId: $dto->correlationId
        );

        $cacheKey = $this->getCacheKey($dto);

        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->log->$this->logger->info('Search cache hit', [
                'query' => $dto->query,
                'correlation_id' => $dto->correlationId,
            ]);

            return SearchResponseDto::fromArray($cachedResult);
        }

        $query = $this->buildSearchQuery($dto);

        $total = $query->count();

        $products = $query
            ->offset($dto->getOffset())
            ->limit($dto->perPage)
            ->get()
            ->map(fn ($product) => $this->formatProductForSearch($product))
            ->toArray();

        $aggregations = $this->buildAggregations($dto);

        $totalPages = (int) ceil($total / $dto->perPage);

        $searchTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        $response = new SearchResponseDto(
            products: $products,
            total: $total,
            page: $dto->page,
            perPage: $dto->perPage,
            totalPages: $totalPages,
            aggregations: $aggregations,
            metadata: [
                'query' => $dto->query,
                'filters_applied' => $this->countAppliedFilters($dto),
                'search_type' => $dto->query ? 'full_text' : 'filter_only',
            ],
            correlationId: $dto->correlationId,
            searchTimeMs: $searchTimeMs,
        );

        $this->cache->put($cacheKey, $response->toArray(), CarbonImmutable::now()->addMinutes(5));

        $this->log->channel('audit')->$this->logger->info('Electronics search completed', [
            'query' => $dto->query,
            'total_results' => $total,
            'page' => $dto->page,
            'correlation_id' => $dto->correlationId,
            'search_time_ms' => $searchTimeMs,
        ]);

        return $response;
    }

    public function getAvailableFilters(?string $category = null): FilterDto
    {
        $cacheKey = "electronics_filters:{$category}";

        return $this->cache->remember($cacheKey, CarbonImmutable::now()->addHours(2), function () use ($category) {
            $query = ElectronicsProduct::query()
                ->where('is_active', true)
                ->where('availability_status', '!=', 'discontinued');

            if ($category) {
                $query->where('category', $category);
            }

            $brands = $query->clone()
                ->select('brand', $this->db->raw('COUNT(*) as count'))
                ->groupBy('brand')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'brand')
                ->toArray();

            $categories = ElectronicsProduct::query()
                ->where('is_active', true)
                ->select('category', $this->db->raw('COUNT(*) as count'))
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'category')
                ->toArray();

            $colors = $query->clone()
                ->whereNotNull('color')
                ->select('color', $this->db->raw('COUNT(*) as count'))
                ->groupBy('color')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'color')
                ->toArray();

            $specs = $this->extractSpecsAggregations($query);

            $priceRanges = $this->calculatePriceRanges($query);

            return new FilterDto(
                brands: $brands,
                categories: $categories,
                colors: $colors,
                specs: $specs,
                priceRanges: $priceRanges,
            );
        });
    }

    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'electronics_suggestions:'.md5($query);

        return $this->cache->remember($cacheKey, CarbonImmutable::now()->addMinutes(30), function () use ($query, $limit) {
            return ElectronicsProduct::query()
                ->where('is_active', true)
                ->where('availability_status', 'in_stock')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('brand', 'like', "%{$query}%")
                        ->orWhere('category', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'category' => $product->category,
                    'price_kopecks' => $product->price_kopecks,
                    'image' => $product->images[0] ?? null,
                ])
                ->toArray();
        });
    }

    private function buildSearchQuery(SearchRequestDto $dto): Builder
    {
        $query = ElectronicsProduct::query()
            ->where('is_active', true)
            ->where('tenant_id', tenant()->id);

        if ($dto->inStockOnly) {
            $query->where('availability_status', 'in_stock')
                ->where('stock_quantity', '>', 0);
        }

        if ($dto->withDiscount) {
            $query->whereColumn('original_price_kopecks', '>', 'price_kopecks');
        }

        if ($dto->type) {
            $query->where('type', $dto->type);
        }

        if ($dto->query) {
            $searchTerms = explode(' ', trim($dto->query));
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 2) {
                        $q->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('brand', 'like', "%{$term}%")
                            ->orWhere('category', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%")
                            ->orWhere('specs->>\"description\"', 'like', "%{$term}%");
                    }
                }
            });
        }

        if (! empty($dto->brands)) {
            $query->whereIn('brand', $dto->brands);
        }

        if (! empty($dto->categories)) {
            $query->whereIn('category', $dto->categories);
        }

        if (! empty($dto->colors)) {
            $query->whereIn('color', $dto->colors);
        }

        if ($dto->minPriceKopecks !== null) {
            $query->where('price_kopecks', '>=', $dto->minPriceKopecks);
        }

        if ($dto->maxPriceKopecks !== null) {
            $query->where('price_kopecks', '<=', $dto->maxPriceKopecks);
        }

        foreach ($dto->specsFilters as $specKey => $specValues) {
            if (! empty($specValues)) {
                $query->where(function ($q) use ($specKey, $specValues) {
                    foreach ($specValues as $value) {
                        $q->orWhere("specs->{$specKey}", 'like', "%{$value}%");
                    }
                });
            }
        }

        $this->applySorting($query, $dto->sort);

        return $query;
    }

    private function applySorting(Builder $query, array $sort): void
    {
        $field = $sort['field'] ?? 'relevance';
        $direction = $sort['direction'] ?? 'desc';

        match ($field) {
            'price' => $query->orderBy('price_kopecks', $direction),
            'rating' => $query->orderBy('rating', $direction),
            'reviews' => $query->orderBy('reviews_count', $direction),
            'newest' => $query->orderBy('created_at', $direction),
            'popularity' => $query->orderBy('views_count', $direction),
            'discount' => $query->orderByRaw('(original_price_kopecks - price_kopecks) DESC'),
            'relevance' => $query->orderBy('rating', 'desc')->orderBy('reviews_count', 'desc'),
            default => $query->orderBy('rating', 'desc'),
        };
    }

    private function formatProductForSearch(ElectronicsProduct $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'brand' => $product->brand,
            'category' => $product->category,
            'price_kopecks' => $product->price_kopecks,
            'original_price_kopecks' => $product->original_price_kopecks,
            'discount_percentage' => $product->original_price_kopecks > $product->price_kopecks
                ? round((($product->original_price_kopecks - $product->price_kopecks) / $product->original_price_kopecks) * 100)
                : 0,
            'color' => $product->color,
            'images' => $product->images,
            'specs' => $product->specs,
            'rating' => $product->rating,
            'reviews_count' => $product->reviews_count,
            'stock_quantity' => $product->stock_quantity,
            'availability_status' => $product->availability_status,
            'is_bestseller' => $product->is_bestseller ?? false,
            'is_new' => $product->created_at && $product->created_at->gt(CarbonImmutable::now()->subDays(30)),
            'views_count' => $product->views_count ?? 0,
        ];
    }

    private function buildAggregations(SearchRequestDto $dto): array
    {
        $baseQuery = $this->buildSearchQuery($dto);

        $brands = $baseQuery->clone()
            ->select('brand', $this->db->raw('COUNT(*) as count'))
            ->groupBy('brand')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->pluck('count', 'brand')
            ->toArray();

        $categories = $baseQuery->clone()
            ->select('category', $this->db->raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'category')
            ->toArray();

        $priceStats = $baseQuery->clone()
            ->select(
                $this->db->raw('MIN(price_kopecks) as min_price'),
                $this->db->raw('MAX(price_kopecks) as max_price'),
                $this->db->raw('AVG(price_kopecks) as avg_price')
            )
            ->first();

        return [
            'brands' => $brands,
            'categories' => $categories,
            'price_range' => [
                'min_kopecks' => (int) $priceStats->min_price,
                'max_kopecks' => (int) $priceStats->max_price,
                'avg_kopecks' => (int) $priceStats->avg_price,
            ],
        ];
    }

    private function extractSpecsAggregations(Builder $query): array
    {
        $products = $query->limit(1000)->get(['specs']);

        $specs = [];
        foreach ($products as $product) {
            $productSpecs = $product->specs ?? [];
            foreach ($productSpecs as $key => $value) {
                if (! isset($specs[$key])) {
                    $specs[$key] = [];
                }
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $specs[$key][$v] = ($specs[$key][$v] ?? 0) + 1;
                    }
                } else {
                    $specs[$key][$value] = ($specs[$key][$value] ?? 0) + 1;
                }
            }
        }

        foreach ($specs as $key => $values) {
            arsort($specs[$key]);
            $specs[$key] = array_slice($specs[$key], 0, 20, true);
        }

        return $specs;
    }

    private function calculatePriceRanges(Builder $query): array
    {
        $priceStats = $query->select(
            $this->db->raw('MIN(price_kopecks) as min_price'),
            $this->db->raw('MAX(price_kopecks) as max_price')
        )
            ->first();

        $min = (int) $priceStats->min_price;
        $max = (int) $priceStats->max_price;
        $range = $max - $min;

        if ($range === 0) {
            return [
                '0-5000' => $query->count(),
            ];
        }

        $steps = 5;
        $stepSize = (int) ($range / $steps);
        $ranges = [];

        for ($i = 0; $i < $steps; $i++) {
            $rangeStart = $min + ($i * $stepSize);
            $rangeEnd = $i === $steps - 1 ? $max : $rangeStart + $stepSize;
            $rangeKey = "{$rangeStart}-{$rangeEnd}";

            $ranges[$rangeKey] = $query->clone()
                ->whereBetween('price_kopecks', [$rangeStart, $rangeEnd])
                ->count();
        }

        return $ranges;
    }

    private function getCacheKey(SearchRequestDto $dto): string
    {
        $key = 'electronics_search:'.md5(serialize($dto->toArray()));

        return $key;
    }

    private function countAppliedFilters(SearchRequestDto $dto): int
    {
        $count = 0;

        if (! empty($dto->brands)) {
            $count++;
        }

        if (! empty($dto->categories)) {
            $count++;
        }

        if (! empty($dto->colors)) {
            $count++;
        }

        if ($dto->minPriceKopecks !== null || $dto->maxPriceKopecks !== null) {
            $count++;
        }

        if (! empty($dto->specsFilters)) {
            $count += count($dto->specsFilters);
        }

        if ($dto->inStockOnly) {
            $count++;
        }

        if ($dto->withDiscount) {
            $count++;
        }

        return $count;
    }
}
