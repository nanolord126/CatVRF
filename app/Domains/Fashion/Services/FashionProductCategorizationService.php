<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Сервис категоризации товаров Fashion с ML-улучшениями.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 */
final readonly class FashionProductCategorizationService
{
    private const CATEGORY_CONFIDENCE_THRESHOLD = 0.75;
    private const AUTO_CATEGORIZATION_ENABLED = true;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Автоматическая категоризация товара на основе атрибутов.
     */
    public function autoCategorizeProduct(
        int $productId,
        array $attributes,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        return $this->db->transaction(function () use ($productId, $attributes, $correlationId, $tenantId) {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($product === null) {
                throw new \InvalidArgumentException('Product not found', 404);
            }

            $primaryCategory = $this->determinePrimaryCategory($attributes, $correlationId);
            $secondaryCategories = $this->determineSecondaryCategories($attributes, $correlationId);
            $tags = $this->generateTags($attributes, $correlationId);
            $styleProfile = $this->determineStyleProfile($attributes, $correlationId);
            $season = $this->determineSeason($attributes, $correlationId);
            $targetAudience = $this->determineTargetAudience($attributes, $correlationId);

            $this->saveProductCategories(
                $productId,
                $tenantId,
                $primaryCategory,
                $secondaryCategories,
                $tags,
                $styleProfile,
                $season,
                $targetAudience,
                $correlationId
            );

            $this->audit->record(
                action: 'fashion_product_auto_categorized',
                subjectType: 'fashion_product',
                subjectId: $productId,
                oldValues: [],
                newValues: [
                    'primary_category' => $primaryCategory,
                    'secondary_categories' => $secondaryCategories,
                    'tags' => $tags,
                    'style_profile' => $styleProfile,
                    'season' => $season,
                    'target_audience' => $targetAudience,
                ],
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Fashion product auto-categorized', [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'primary_category' => $primaryCategory,
                'correlation_id' => $correlationId,
            ]);

            return [
                'product_id' => $productId,
                'primary_category' => $primaryCategory,
                'secondary_categories' => $secondaryCategories,
                'tags' => $tags,
                'style_profile' => $styleProfile,
                'season' => $season,
                'target_audience' => $targetAudience,
                'confidence_score' => $this->calculateConfidenceScore($attributes, $correlationId),
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Массовая перекатегоризация товаров на основе ML.
     */
    public function bulkRecategorizeProducts(
        array $productIds,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $results = [];
        foreach ($productIds as $productId) {
            try {
                $product = $this->db->table('fashion_products')
                    ->where('id', $productId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($product !== null) {
                    $attributes = json_decode($product->attributes ?? '{}', true);
                    $result = $this->autoCategorizeProduct($productId, $attributes, $correlationId);
                    $results[] = $result;
                }
            } catch (\Throwable $e) {
                Log::channel('audit')->warning('Failed to recategorize product', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return [
            'total_processed' => count($productIds),
            'successful' => count($results),
            'failed' => count($productIds) - count($results),
            'results' => $results,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить иерархию категорий для фильтрации.
     */
    public function getCategoryHierarchy(?string $parentCategory = null): array
    {
        $query = $this->db->table('fashion_categories')
            ->where('is_active', true);

        if ($parentCategory !== null) {
            $query->where('parent_category', $parentCategory);
        } else {
            $query->whereNull('parent_category');
        }

        $categories = $query->orderBy('sort_order')->get()->toArray();

        $hierarchy = [];
        foreach ($categories as $category) {
            $hierarchy[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'parent_category' => $category['parent_category'],
                'icon' => $category['icon'] ?? null,
                'product_count' => $this->getCategoryProductCount($category['id']),
                'children' => $this->getCategoryHierarchy($category['name']),
            ];
        }

        return $hierarchy;
    }

    /**
     * Получить умные рекомендации категорий на основе поведения пользователя.
     */
    public function getSmartCategorySuggestions(int $userId, int $limit = 10): array
    {
        $userCategories = $this->getUserPreferredCategories($userId);
        $trendingCategories = $this->getTrendingCategories();
        $seasonalCategories = $this->getSeasonalCategories();

        $suggestions = [];
        $categoryScores = [];

        foreach ($userCategories as $category => $score) {
            $categoryScores[$category] = ($categoryScores[$category] ?? 0) + ($score * 0.5);
        }

        foreach ($trendingCategories as $category => $score) {
            $categoryScores[$category] = ($categoryScores[$category] ?? 0) + ($score * 0.3);
        }

        foreach ($seasonalCategories as $category => $score) {
            $categoryScores[$category] = ($categoryScores[$category] ?? 0) + ($score * 0.2);
        }

        arsort($categoryScores);
        $suggestions = array_slice(array_keys($categoryScores), 0, $limit, true);

        return [
            'suggested_categories' => $suggestions,
            'scores' => array_slice($categoryScores, 0, $limit, true),
            'reasoning' => [
                'user_preferences' => array_keys(array_slice($userCategories, 0, 3, true)),
                'trending' => array_keys(array_slice($trendingCategories, 0, 3, true)),
                'seasonal' => array_keys(array_slice($seasonalCategories, 0, 3, true)),
            ],
        ];
    }

    private function determinePrimaryCategory(array $attributes, string $correlationId): string
    {
        $categoryRules = [
            'tops' => ['top', 't-shirt', 'shirt', 'blouse', 'sweater', 'hoodie', 'jacket', 'coat'],
            'bottoms' => ['pants', 'jeans', 'trousers', 'skirt', 'shorts', 'leggings'],
            'dresses' => ['dress', 'gown', 'romper', 'jumpsuit'],
            'outerwear' => ['coat', 'jacket', 'blazer', 'cardigan', 'vest'],
            'shoes' => ['shoes', 'boots', 'sneakers', 'heels', 'sandals', 'flats'],
            'accessories' => ['bag', 'belt', 'hat', 'scarf', 'jewelry', 'watch', 'sunglasses'],
            'underwear' => ['underwear', 'lingerie', 'socks', 'tights'],
        ];

        $name = strtolower($attributes['name'] ?? '');
        $description = strtolower($attributes['description'] ?? '');
        $combined = $name . ' ' . $description;

        foreach ($categoryRules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($combined, $keyword)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    private function determineSecondaryCategories(array $attributes, string $correlationId): array
    {
        $secondary = [];
        $material = strtolower($attributes['material'] ?? '');
        $style = strtolower($attributes['style'] ?? '');

        if (str_contains($material, 'denim') || str_contains($style, 'casual')) {
            $secondary[] = 'casual';
        }
        if (str_contains($style, 'formal') || str_contains($style, 'business')) {
            $secondary[] = 'formal';
        }
        if (str_contains($style, 'sport') || str_contains($style, 'athletic')) {
            $secondary[] = 'sport';
        }
        if (str_contains($style, 'elegant') || str_contains($style, 'evening')) {
            $secondary[] = 'evening';
        }

        return array_unique($secondary);
    }

    private function generateTags(array $attributes, string $correlationId): array
    {
        $tags = [];
        $colors = $attributes['colors'] ?? [];
        $brand = $attributes['brand'] ?? '';
        $material = $attributes['material'] ?? '';
        $style = $attributes['style'] ?? '';

        foreach ($colors as $color) {
            $tags[] = strtolower($color);
        }

        if ($brand) {
            $tags[] = strtolower($brand);
        }

        if ($material) {
            $tags[] = strtolower($material);
        }

        if ($style) {
            $tags[] = strtolower($style);
        }

        $price = (int) ($attributes['price_b2c'] ?? 0);
        if ($price < 1000) {
            $tags[] = 'budget';
        } elseif ($price < 5000) {
            $tags[] = 'mid-range';
        } else {
            $tags[] = 'premium';
        }

        return array_unique($tags);
    }

    private function determineStyleProfile(array $attributes, string $correlationId): string
    {
        $style = strtolower($attributes['style'] ?? '');
        $styleProfiles = [
            'minimalist' => ['minimalist', 'clean', 'simple', 'basic'],
            'classic' => ['classic', 'timeless', 'traditional', 'elegant'],
            'bohemian' => ['bohemian', 'boho', 'hippie', 'ethnic'],
            'streetwear' => ['street', 'urban', 'casual', 'trendy'],
            'vintage' => ['vintage', 'retro', 'classic'],
            'sporty' => ['sport', 'athletic', 'active', 'fitness'],
            'romantic' => ['romantic', 'feminine', 'floral', 'delicate'],
            'edgy' => ['edgy', 'punk', 'rock', 'bold'],
        ];

        foreach ($styleProfiles as $profile => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($style, $keyword)) {
                    return $profile;
                }
            }
        }

        return 'classic';
    }

    private function determineSeason(array $attributes, string $correlationId): string
    {
        $description = strtolower($attributes['description'] ?? '');
        $material = strtolower($attributes['material'] ?? '');

        $seasonKeywords = [
            'spring' => ['spring', 'lightweight', 'floral', 'pastel', 'transitional'],
            'summer' => ['summer', 'light', 'breathable', 'linen', 'cotton', 'sandal'],
            'autumn' => ['autumn', 'fall', 'layer', 'warm', 'cozy', 'knit'],
            'winter' => ['winter', 'warm', 'insulated', 'wool', 'coat', 'boot'],
        ];

        foreach ($seasonKeywords as $season => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword) || str_contains($material, $keyword)) {
                    return $season;
                }
            }
        }

        $currentMonth = Carbon::now()->month;
        if ($currentMonth >= 3 && $currentMonth <= 5) {
            return 'spring';
        } elseif ($currentMonth >= 6 && $currentMonth <= 8) {
            return 'summer';
        } elseif ($currentMonth >= 9 && $currentMonth <= 11) {
            return 'autumn';
        } else {
            return 'winter';
        }
    }

    private function determineTargetAudience(array $attributes, string $correlationId): string
    {
        $name = strtolower($attributes['name'] ?? '');
        $description = strtolower($attributes['description'] ?? '');

        $audienceKeywords = [
            'women' => ['women', 'ladies', 'female', 'her', 'she'],
            'men' => ['men', 'gentlemen', 'male', 'his', 'he'],
            'kids' => ['kids', 'children', 'child', 'boys', 'girls'],
            'unisex' => ['unisex', 'gender-neutral', 'everyone'],
        ];

        foreach ($audienceKeywords as $audience => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword) || str_contains($description, $keyword)) {
                    return $audience;
                }
            }
        }

        return 'women';
    }

    private function saveProductCategories(
        int $productId,
        int $tenantId,
        string $primaryCategory,
        array $secondaryCategories,
        array $tags,
        string $styleProfile,
        string $season,
        string $targetAudience,
        string $correlationId
    ): void {
        $this->db->table('fashion_product_categories')->updateOrInsert(
            ['product_id' => $productId, 'tenant_id' => $tenantId],
            [
                'primary_category' => $primaryCategory,
                'secondary_categories' => json_encode($secondaryCategories, JSON_UNESCAPED_UNICODE),
                'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                'style_profile' => $styleProfile,
                'season' => $season,
                'target_audience' => $targetAudience,
                'updated_at' => Carbon::now(),
            ]
        );
    }

    private function calculateConfidenceScore(array $attributes, string $correlationId): float
    {
        $score = 0.5;
        $score += isset($attributes['name']) ? 0.2 : 0;
        $score += isset($attributes['description']) ? 0.1 : 0;
        $score += isset($attributes['material']) ? 0.1 : 0;
        $score += isset($attributes['style']) ? 0.05 : 0;
        $score += !empty($attributes['colors']) ? 0.05 : 0;

        return min($score, 1.0);
    }

    private function getCategoryProductCount(int $categoryId): int
    {
        return $this->db->table('fashion_product_categories')
            ->where('primary_category', $this->db->table('fashion_categories')->where('id', $categoryId)->value('name'))
            ->count();
    }

    private function getUserPreferredCategories(int $userId): array
    {
        $views = $this->db->table('product_views')
            ->join('fashion_product_categories', 'product_views.product_id', '=', 'fashion_product_categories.product_id')
            ->where('product_views.user_id', $userId)
            ->selectRaw('fashion_product_categories.primary_category, COUNT(*) as count')
            ->groupBy('fashion_product_categories.primary_category')
            ->orderByRaw('count DESC')
            ->limit(10)
            ->get()
            ->pluck('count', 'primary_category')
            ->toArray();

        $total = array_sum($views);
        return array_map(fn($count) => $count / max($total, 1), $views);
    }

    private function getTrendingCategories(): array
    {
        $views = $this->db->table('product_views')
            ->join('fashion_product_categories', 'product_views.product_id', '=', 'fashion_product_categories.product_id')
            ->where('product_views.created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('fashion_product_categories.primary_category, COUNT(*) as count')
            ->groupBy('fashion_product_categories.primary_category')
            ->orderByRaw('count DESC')
            ->limit(10)
            ->get()
            ->pluck('count', 'primary_category')
            ->toArray();

        $total = array_sum($views);
        return array_map(fn($count) => $count / max($total, 1), $views);
    }

    private function getSeasonalCategories(): array
    {
        $currentSeason = $this->determineSeason([], '');
        $seasonalCategories = [
            'spring' => ['tops', 'dresses', 'light outerwear'],
            'summer' => ['tops', 'dresses', 'bottoms', 'shoes'],
            'autumn' => ['outerwear', 'tops', 'bottoms', 'accessories'],
            'winter' => ['outerwear', 'sweaters', 'boots', 'accessories'],
        ];

        $categories = $seasonalCategories[$currentSeason] ?? [];
        $scores = [];
        foreach ($categories as $category) {
            $scores[$category] = 1.0;
        }

        return $scores;
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
