<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\RecommendationService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\SearchPropertyDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Exception;

final readonly class PropertySearchService
{
    private const SEARCH_CACHE_TTL_SECONDS = 300;
    private const MAX_RESULTS_PER_PAGE = 50;
    private const MIN_PRICE_FILTER = 100000;
    private const MAX_PRICE_FILTER = 1000000000;
    private const MIN_AREA_FILTER = 10;
    private const MAX_AREA_FILTER = 10000;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private RecommendationService $recommendation,
    ) {}

    public function searchProperties(SearchPropertyDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'property_search',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $cacheKey = $this->generateSearchCacheKey($dto);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $query = Property::where('tenant_id', $dto->tenantId)
            ->where('status', PropertyStatusEnum::AVAILABLE->value);

        $this->applyFilters($query, $dto);
        $this->applySorting($query, $dto);

        $total = $query->count();
        $results = $query
            ->offset(($dto->page - 1) * $dto->perPage)
            ->limit(min($dto->perPage, self::MAX_RESULTS_PER_PAGE))
            ->get();

        $enhancedResults = $this->enhanceSearchResults($results, $dto);

        $response = [
            'data' => $enhancedResults,
            'meta' => [
                'total' => $total,
                'page' => $dto->page,
                'per_page' => $dto->perPage,
                'total_pages' => (int) ceil($total / $dto->perPage),
                'has_next' => $dto->page * $dto->perPage < $total,
                'has_prev' => $dto->page > 1,
            ],
            'filters_applied' => $this->getAppliedFilters($dto),
            'search_id' => Str::uuid()->toString(),
        ];

        Cache::put($cacheKey, $response, self::SEARCH_CACHE_TTL_SECONDS);

        Log::channel('audit')->info('Property search executed', [
            'user_id' => $dto->userId,
            'total_results' => $total,
            'filters' => $this->getAppliedFilters($dto),
            'correlation_id' => $dto->correlationId,
            'tenant_id' => $dto->tenantId,
        ]);

        return $response;
    }

    public function getPersonalizedRecommendations(int $userId, int $tenantId, int $limit = 10, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'personalized_recommendations',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "recommendations:user:{$userId}:{$tenantId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $mlRecommendations = $this->recommendation->getPersonalizedProperties($userId, $tenantId, $limit);

        $propertyIds = array_column($mlRecommendations, 'property_id');
        $properties = Property::whereIn('id', $propertyIds)
            ->where('tenant_id', $tenantId)
            ->where('status', PropertyStatusEnum::AVAILABLE->value)
            ->get()
            ->keyBy('id');

        $enhancedResults = [];
        foreach ($mlRecommendations as $rec) {
            if (isset($properties[$rec['property_id']])) {
                $property = $properties[$rec['property_id']];
                $enhancedResults[] = [
                    ...$this->formatPropertyForSearch($property),
                    'recommendation_score' => $rec['score'],
                    'recommendation_reason' => $rec['reason'],
                    'is_personalized' => true,
                ];
            }
        }

        Cache::put($cacheKey, $enhancedResults, self::SEARCH_CACHE_TTL_SECONDS);

        Log::channel('audit')->info('Personalized recommendations generated', [
            'user_id' => $userId,
            'results_count' => count($enhancedResults),
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        return $enhancedResults;
    }

    public function getSimilarProperties(int $propertyId, int $tenantId, int $limit = 6, string $correlationId): array
    {
        $property = Property::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $cacheKey = "similar:property:{$propertyId}:{$tenantId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $similarProperties = Property::where('tenant_id', $tenantId)
            ->where('status', PropertyStatusEnum::AVAILABLE->value)
            ->where('id', '!=', $propertyId)
            ->where('property_type', $property->property_type)
            ->where('listing_type', $property->listing_type)
            ->whereBetween('price', [
                $property->price * 0.7,
                $property->price * 1.3,
            ])
            ->whereBetween('area', [
                $property->area * 0.8,
                $property->area * 1.2,
            ])
            ->where('city', $property->city)
            ->orderByRaw(
                "ABS(price - ?) + ABS(area - ?) ASC",
                [$property->price, $property->area]
            )
            ->limit($limit)
            ->get();

        $enhancedResults = $similarProperties->map(function ($prop) {
            return [
                ...$this->formatPropertyForSearch($prop),
                'similarity_score' => $this->calculateSimilarityScore($property, $prop),
            ];
        })->toArray();

        Cache::put($cacheKey, $enhancedResults, self::SEARCH_CACHE_TTL_SECONDS);

        return $enhancedResults;
    }

    public function getPropertyById(int $propertyId, int $tenantId, string $correlationId): array
    {
        $property = Property::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->with(['seller', 'agent'])
            ->firstOrFail();

        $result = [
            ...$this->formatPropertyForSearch($property),
            'seller' => [
                'id' => $property->seller->id,
                'name' => $property->seller->name,
                'phone' => $property->seller->phone ?? '',
                'email' => $property->seller->email,
                'verified' => $property->seller->verified_at !== null,
            ],
            'agent' => $property->agent ? [
                'id' => $property->agent->id,
                'name' => $property->agent->name,
                'phone' => $property->agent->phone ?? '',
                'email' => $property->agent->email ?? '',
                'company' => $property->agent->company ?? '',
                'rating' => $property->agent->rating ?? 0,
            ] : null,
            'viewing_slots' => $this->generateViewingSlots($property),
            'dynamic_price_info' => $property->dynamic_pricing_enabled ? [
                'current_price' => $property->price,
                'suggested_price' => $property->suggested_price,
                'price_trend' => 'stable',
                'demand_level' => 'medium',
            ] : null,
        ];

        Log::channel('audit')->info('Property retrieved by ID', [
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    public function getSearchFilters(int $tenantId, string $correlationId): array
    {
        $cacheKey = "search_filters:{$tenantId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $filters = [
            'property_types' => Property::where('tenant_id', $tenantId)
                ->where('status', PropertyStatusEnum::AVAILABLE->value)
                ->distinct('property_type')
                ->pluck('property_type')
                ->values()
                ->toArray(),
            'listing_types' => Property::where('tenant_id', $tenantId)
                ->where('status', PropertyStatusEnum::AVAILABLE->value)
                ->distinct('listing_type')
                ->pluck('listing_type')
                ->values()
                ->toArray(),
            'cities' => Property::where('tenant_id', $tenantId)
                ->where('status', PropertyStatusEnum::AVAILABLE->value)
                ->distinct('city')
                ->orderBy('city')
                ->pluck('city')
                ->values()
                ->toArray(),
            'price_range' => [
                'min' => Property::where('tenant_id', $tenantId)
                    ->where('status', PropertyStatusEnum::AVAILABLE->value)
                    ->min('price') ?? self::MIN_PRICE_FILTER,
                'max' => Property::where('tenant_id', $tenantId)
                    ->where('status', PropertyStatusEnum::AVAILABLE->value)
                    ->max('price') ?? self::MAX_PRICE_FILTER,
            ],
            'area_range' => [
                'min' => Property::where('tenant_id', $tenantId)
                    ->where('status', PropertyStatusEnum::AVAILABLE->value)
                    ->min('area') ?? self::MIN_AREA_FILTER,
                'max' => Property::where('tenant_id', $tenantId)
                    ->where('status', PropertyStatusEnum::AVAILABLE->value)
                    ->max('area') ?? self::MAX_AREA_FILTER,
            ],
            'amenities' => Property::where('tenant_id', $tenantId)
                ->where('status', PropertyStatusEnum::AVAILABLE->value)
                ->selectRaw('unnest(amenities) as amenity')
                ->distinct()
                ->pluck('amenity')
                ->filter()
                ->values()
                ->toArray(),
        ];

        Cache::put($cacheKey, $filters, self::SEARCH_CACHE_TTL_SECONDS);

        return $filters;
    }

    public function saveSearch(int $userId, int $tenantId, array $searchCriteria, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'save_search',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $searchId = DB::table('saved_searches')->insertGetId([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'uuid' => Str::uuid()->toString(),
            'search_criteria' => json_encode($searchCriteria),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::channel('audit')->info('Search saved', [
            'user_id' => $userId,
            'search_id' => $searchId,
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        return [
            'search_id' => $searchId,
            'uuid' => DB::table('saved_searches')->where('id', $searchId)->value('uuid'),
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function applyFilters(Builder $query, SearchPropertyDto $dto): void
    {
        if ($dto->propertyType !== null) {
            $query->where('property_type', $dto->propertyType);
        }

        if ($dto->listingType !== null) {
            $query->where('listing_type', $dto->listingType);
        }

        if ($dto->minPrice !== null) {
            $query->where('price', '>=', max($dto->minPrice, self::MIN_PRICE_FILTER));
        }

        if ($dto->maxPrice !== null) {
            $query->where('price', '<=', min($dto->maxPrice, self::MAX_PRICE_FILTER));
        }

        if ($dto->minArea !== null) {
            $query->where('area', '>=', max($dto->minArea, self::MIN_AREA_FILTER));
        }

        if ($dto->maxArea !== null) {
            $query->where('area', '<=', min($dto->maxArea, self::MAX_AREA_FILTER));
        }

        if ($dto->rooms !== null) {
            $query->where('rooms', $dto->rooms);
        }

        if ($dto->bathrooms !== null) {
            $query->where('bathrooms', '>=', $dto->bathrooms);
        }

        if ($dto->city !== null) {
            $query->where('city', $dto->city);
        }

        if ($dto->district !== null) {
            $query->where('district', $dto->district);
        }

        if ($dto->isB2b !== null) {
            $query->where('is_b2b', $dto->isB2b);
        }

        if ($dto->isFeatured !== null) {
            $query->where('is_featured', $dto->isFeatured);
        }

        if ($dto->isVerified !== null) {
            $query->where('is_verified', $dto->isVerified);
        }

        if ($dto->amenities !== null && count($dto->amenities) > 0) {
            foreach ($dto->amenities as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        if ($dto->features !== null && count($dto->features) > 0) {
            foreach ($dto->features as $feature) {
                $query->whereJsonContains('features', $feature);
            }
        }

        if ($dto->petPolicy !== null) {
            $query->where('pet_policy', $dto->petPolicy);
        }

        if ($dto->furnishing !== null) {
            $query->where('furnishing', $dto->furnishing);
        }

        if ($dto->availableFrom !== null) {
            $query->where('available_from', '<=', $dto->availableFrom);
        }

        if ($dto->availableUntil !== null) {
            $query->where('available_until', '>=', $dto->availableUntil);
        }

        if ($dto->searchQuery !== null) {
            $query->where(function ($q) use ($dto) {
                $q->where('title', 'ilike', "%{$dto->searchQuery}%")
                    ->orWhere('description', 'ilike', "%{$dto->searchQuery}%")
                    ->orWhere('address', 'ilike', "%{$dto->searchQuery}%")
                    ->orWhere('district', 'ilike', "%{$dto->searchQuery}%");
            });
        }

        if ($dto->lat !== null && $dto->lon !== null && $dto->radiusKm !== null) {
            $this->applyGeoFilter($query, $dto->lat, $dto->lon, $dto->radiusKm);
        }
    }

    private function applySorting(Builder $query, SearchPropertyDto $dto): void
    {
        $sortBy = $dto->sortBy ?? 'created_at';
        $sortOrder = $dto->sortOrder ?? 'desc';

        $validSortFields = [
            'price', 'area', 'rooms', 'created_at', 'published_at',
            'liquidity_score', 'fraud_score', 'suggested_price'
        ];

        if (!in_array($sortBy, $validSortFields, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc'], true)
            ? strtolower($sortOrder)
            : 'desc';

        if ($sortBy === 'price' && $dto->sortBy === 'suggested_price') {
            $query->orderByRaw('COALESCE(suggested_price, price) ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($dto->isFeatured === true) {
            $query->orderBy('is_featured', 'desc');
        }
    }

    private function applyGeoFilter(Builder $query, float $lat, float $lon, int $radiusKm): void
    {
        $earthRadius = 6371;
        $query->selectRaw(
            "*, ({$earthRadius} * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
            [$lat, $lon, $lat]
        )->havingRaw('distance <= ?', [$radiusKm])->orderBy('distance');
    }

    private function enhanceSearchResults($results, SearchPropertyDto $dto): array
    {
        return $results->map(function ($property) use ($dto) {
            $formatted = $this->formatPropertyForSearch($property);

            if ($dto->includeDynamicPricing && $property->dynamic_pricing_enabled) {
                $formatted['dynamic_pricing'] = [
                    'current_price' => $property->price,
                    'suggested_price' => $property->suggested_price,
                    'price_diff_percent' => $property->suggested_price
                        ? (($property->suggested_price - $property->price) / $property->price) * 100
                        : 0,
                    'is_underpriced' => $property->suggested_price && $property->price < $property->suggested_price,
                ];
            }

            if ($dto->includeScores) {
                $formatted['scores'] = [
                    'liquidity_score' => $property->liquidity_score,
                    'fraud_score' => $property->fraud_score,
                    'overall_score' => $this->calculateOverallScore($property),
                ];
            }

            return $formatted;
        })->toArray();
    }

    private function formatPropertyForSearch(Property $property): array
    {
        return [
            'id' => $property->id,
            'uuid' => $property->uuid,
            'title' => $property->title,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'listing_type' => $property->listing_type,
            'price' => $property->price,
            'currency' => $property->currency,
            'area' => $property->area,
            'rooms' => $property->rooms,
            'bathrooms' => $property->bathrooms,
            'floor' => $property->floor,
            'total_floors' => $property->total_floors,
            'year_built' => $property->year_built,
            'address' => $property->address,
            'city' => $property->city,
            'district' => $property->district,
            'lat' => $property->lat,
            'lon' => $property->lon,
            'images' => $property->images,
            'video_url' => $property->video_url,
            'tour_3d_url' => $property->tour_3d_url,
            'virtual_tour_enabled' => $property->virtual_tour_enabled,
            'amenities' => $property->amenities,
            'features' => $property->features,
            'status' => $property->status,
            'is_b2b' => $property->is_b2b,
            'is_featured' => $property->is_featured,
            'is_verified' => $property->is_verified,
            'blockchain_verified' => $property->blockchain_verified,
            'seller_id' => $property->seller_id,
            'agent_id' => $property->agent_id,
            'published_at' => $property->published_at?->toIso8601String(),
            'created_at' => $property->created_at->toIso8601String(),
            'updated_at' => $property->updated_at->toIso8601String(),
        ];
    }

    private function generateSearchCacheKey(SearchPropertyDto $dto): string
    {
        $criteria = [
            $dto->tenantId,
            $dto->propertyType,
            $dto->listingType,
            $dto->minPrice,
            $dto->maxPrice,
            $dto->minArea,
            $dto->maxArea,
            $dto->rooms,
            $dto->bathrooms,
            $dto->city,
            $dto->district,
            $dto->isB2b,
            $dto->isFeatured,
            $dto->isVerified,
            $dto->sortBy,
            $dto->sortOrder,
            $dto->page,
            $dto->perPage,
        ];

        return 'search:' . md5(json_encode($criteria));
    }

    private function getAppliedFilters(SearchPropertyDto $dto): array
    {
        return array_filter([
            'property_type' => $dto->propertyType,
            'listing_type' => $dto->listingType,
            'price_range' => $dto->minPrice !== null || $dto->maxPrice !== null
                ? ['min' => $dto->minPrice, 'max' => $dto->maxPrice]
                : null,
            'area_range' => $dto->minArea !== null || $dto->maxArea !== null
                ? ['min' => $dto->minArea, 'max' => $dto->maxArea]
                : null,
            'rooms' => $dto->rooms,
            'bathrooms' => $dto->bathrooms,
            'city' => $dto->city,
            'district' => $dto->district,
            'is_b2b' => $dto->isB2b,
            'amenities' => $dto->amenities,
            'search_query' => $dto->searchQuery,
        ], fn($value) => $value !== null);
    }

    private function calculateSimilarityScore(Property $property1, Property $property2): float
    {
        $priceDiff = abs($property1->price - $property2->price) / max($property1->price, 1);
        $areaDiff = abs($property1->area - $property2->area) / max($property1->area, 1);
        $roomsDiff = abs($property1->rooms - $property2->rooms) / max($property1->rooms, 1);

        $similarity = 1 - (($priceDiff * 0.5) + ($areaDiff * 0.3) + ($roomsDiff * 0.2));

        return round($similarity * 100, 2);
    }

    private function calculateOverallScore(Property $property): float
    {
        $liquidityScore = $property->liquidity_score ?? 50;
        $fraudScore = $property->fraud_score ?? 50;
        $verifiedBonus = $property->is_verified ? 10 : 0;
        $featuredBonus = $property->is_featured ? 5 : 0;

        return round(min(100, ($liquidityScore * 0.4) + ((100 - $fraudScore) * 0.4) + $verifiedBonus + $featuredBonus), 2);
    }

    private function generateViewingSlots(Property $property): array
    {
        $slots = [];
        $startHour = 9;
        $endHour = 21;

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slots[] = sprintf('%02d:00', $hour);
            $slots[] = sprintf('%02d:30', $hour);
        }

        return [
            'date' => now()->toDateString(),
            'slots' => $slots,
            'duration_minutes' => 30,
        ];
    }
}
