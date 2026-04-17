<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * ML-сервис анализа паттернов памяти пользователей Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Анализирует поведение пользователей, запоминает паттерны,
 * предсказывает будущие действия и персонализирует рекомендации.
 */
final readonly class FashionUserPatternMemoryService
{
    private const MEMORY_RETENTION_DAYS = 180;
    private const MIN_INTERACTIONS_FOR_PATTERN = 5;
    private const PATTERN_CONFIDENCE_THRESHOLD = 0.7;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Записать взаимодействие пользователя с товаром в память.
     */
    public function recordInteraction(
        int $userId,
        int $productId,
        string $interactionType,
        array $context = [],
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_user_interaction_recorded',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use (
            $userId,
            $productId,
            $interactionType,
            $context,
            $correlationId,
            $tenantId
        ) {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($product === null) {
                throw new \InvalidArgumentException('Product not found', 404);
            }

            $interactionScore = $this->calculateInteractionScore($interactionType);
            $productCategories = $this->getProductCategories($productId);

            $this->db->table('fashion_user_memory_interactions')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'interaction_type' => $interactionType,
                'interaction_score' => $interactionScore,
                'category' => $productCategories['primary'] ?? 'other',
                'brand' => $product['brand'] ?? null,
                'price' => $product['price_b2c'] ?? 0,
                'style_profile' => $productCategories['style_profile'] ?? null,
                'color' => $product['color'] ?? null,
                'context' => json_encode($context, JSON_UNESCAPED_UNICODE),
                'correlation_id' => $correlationId,
                'created_at' => Carbon::now(),
            ]);

            $this->updateUserMemoryPatterns($userId, $tenantId, $correlationId);

            $this->audit->record(
                action: 'fashion_user_interaction_recorded',
                subjectType: 'fashion_user_memory',
                subjectId: $userId,
                oldValues: [],
                newValues: [
                    'product_id' => $productId,
                    'interaction_type' => $interactionType,
                    'interaction_score' => $interactionScore,
                ],
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Fashion user interaction recorded', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'interaction_type' => $interactionType,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'user_id' => $userId,
                'product_id' => $productId,
                'interaction_type' => $interactionType,
                'interaction_score' => $interactionScore,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить паттерны памяти пользователя.
     */
    public function getUserMemoryPatterns(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $patterns = $this->db->table('fashion_user_memory_patterns')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('pattern_type')
            ->toArray();

        return [
            'user_id' => $userId,
            'patterns' => $patterns,
            'total_patterns' => count($patterns),
            'last_updated' => !empty($patterns) ? max(array_column($patterns, 'updated_at')) : null,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Предсказать следующее действие пользователя.
     */
    public function predictNextAction(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $patterns = $this->getUserMemoryPatterns($userId, $correlationId);
        $recentInteractions = $this->getRecentInteractions($userId, 10);
        $currentSession = $this->getCurrentSessionInteractions($userId);

        $predictions = [
            'likely_to_purchase' => $this->predictPurchaseLikelihood($patterns, $recentInteractions),
            'likely_categories' => $this->predictLikelyCategories($patterns, $recentInteractions),
            'likely_price_range' => $this->predictPriceRange($patterns, $recentInteractions),
            'likely_brands' => $this->predictBrands($patterns, $recentInteractions),
            'optimal_time_to_convert' => $this->predictOptimalConversionTime($patterns, $recentInteractions),
            'cart_abandonment_risk' => $this->predictCartAbandonmentRisk($patterns, $currentSession),
            'recommended_action' => $this->recommendNextAction($patterns, $recentInteractions),
        ];

        return [
            'user_id' => $userId,
            'predictions' => $predictions,
            'confidence' => $this->calculatePredictionConfidence($patterns, $recentInteractions),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить персонализированные рекомендации на основе памяти.
     */
    public function getMemoryBasedRecommendations(
        int $userId,
        int $limit = 20,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $patterns = $this->getUserMemoryPatterns($userId, $correlationId);
        $interactionHistory = $this->getUserInteractionHistory($userId, 30);

        $preferredCategories = $patterns['patterns']['preferred_categories']['pattern_value'] ?? [];
        $preferredBrands = $patterns['patterns']['preferred_brands']['pattern_value'] ?? [];
        $preferredPriceRange = $patterns['patterns']['price_range']['pattern_value'] ?? 'medium';

        $query = $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0);

        if (!empty($preferredCategories)) {
            $query->whereIn('id', function ($q) use ($preferredCategories, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('primary_category', $preferredCategories);
            });
        }

        if (!empty($preferredBrands)) {
            $query->whereIn('brand', $preferredBrands);
        }

        $priceRange = $this->getPriceRangeBounds($preferredPriceRange);
        $query->whereBetween('price_b2c', [$priceRange['min'], $priceRange['max']]);

        $viewedProductIds = array_column($interactionHistory, 'product_id');
        if (!empty($viewedProductIds)) {
            $query->whereNotIn('id', $viewedProductIds);
        }

        $recommendations = $query
            ->orderBy('rating', 'desc')
            ->orderBy('stock_quantity', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();

        $enrichedRecommendations = [];
        foreach ($recommendations as $product) {
            $enrichedRecommendations[] = [
                ...$product,
                'relevance_score' => $this->calculateRelevanceScore($product, $patterns),
                'recommendation_reason' => $this->getRecommendationReason($product, $patterns),
            ];
        }

        usort($enrichedRecommendations, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return [
            'user_id' => $userId,
            'recommendations' => array_slice($enrichedRecommendations, 0, $limit),
            'total_found' => count($recommendations),
            'based_on_patterns' => array_keys($patterns['patterns']),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Очистить старые записи памяти (GDPR compliance).
     */
    public function cleanupOldMemoryRecords(int $daysToKeep = self::MEMORY_RETENTION_DAYS): array
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        $deletedInteractions = $this->db->table('fashion_user_memory_interactions')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $deletedPatterns = $this->db->table('fashion_user_memory_patterns')
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        Log::channel('audit')->info('Fashion user memory cleanup completed', [
            'deleted_interactions' => $deletedInteractions,
            'deleted_patterns' => $deletedPatterns,
            'cutoff_date' => $cutoffDate->toIso8601String(),
        ]);

        return [
            'deleted_interactions' => $deletedInteractions,
            'deleted_patterns' => $deletedPatterns,
            'cutoff_date' => $cutoffDate->toIso8601String(),
        ];
    }

    /**
     * Экспорт данных памяти пользователя (GDPR right to data portability).
     */
    public function exportUserMemoryData(int $userId): array
    {
        $tenantId = $this->getTenantId();

        $interactions = $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $patterns = $this->db->table('fashion_user_memory_patterns')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->toArray();

        return [
            'user_id' => $userId,
            'export_date' => Carbon::now()->toIso8601String(),
            'total_interactions' => count($interactions),
            'total_patterns' => count($patterns),
            'interactions' => $interactions,
            'patterns' => $patterns,
        ];
    }

    private function calculateInteractionScore(string $interactionType): float
    {
        return match ($interactionType) {
            'view' => 0.1,
            'add_to_cart' => 0.3,
            'add_to_wishlist' => 0.2,
            'purchase' => 1.0,
            'return' => -0.5,
            'review' => 0.4,
            'share' => 0.25,
            default => 0.1,
        };
    }

    private function getProductCategories(int $productId): array
    {
        $category = $this->db->table('fashion_product_categories')
            ->where('product_id', $productId)
            ->first();

        if ($category === null) {
            return [];
        }

        return [
            'primary' => $category['primary_category'],
            'secondary' => json_decode($category['secondary_categories'] ?? '[]', true),
            'style_profile' => $category['style_profile'],
            'season' => $category['season'],
            'target_audience' => $category['target_audience'],
        ];
    }

    private function updateUserMemoryPatterns(int $userId, int $tenantId, string $correlationId): void
    {
        $interactions = $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::MEMORY_RETENTION_DAYS))
            ->get()
            ->toArray();

        if (count($interactions) < self::MIN_INTERACTIONS_FOR_PATTERN) {
            return;
        }

        $patterns = [
            'preferred_categories' => $this->extractPreferredCategories($interactions),
            'preferred_brands' => $this->extractPreferredBrands($interactions),
            'price_range' => $this->extractPriceRange($interactions),
            'preferred_styles' => $this->extractPreferredStyles($interactions),
            'preferred_colors' => $this->extractPreferredColors($interactions),
            'shopping_frequency' => $this->extractShoppingFrequency($interactions),
            'peak_shopping_hours' => $this->extractPeakShoppingHours($interactions),
            'session_duration' => $this->extractSessionDuration($interactions),
            'conversion_rate' => $this->extractConversionRate($interactions),
        ];

        foreach ($patterns as $patternType => $patternValue) {
            $confidence = $this->calculatePatternConfidence($patternType, $patternValue, $interactions);

            if ($confidence >= self::PATTERN_CONFIDENCE_THRESHOLD) {
                $this->db->table('fashion_user_memory_patterns')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'tenant_id' => $tenantId,
                        'pattern_type' => $patternType,
                    ],
                    [
                        'pattern_value' => is_array($patternValue) ? json_encode($patternValue, JSON_UNESCAPED_UNICODE) : $patternValue,
                        'confidence' => $confidence,
                        'sample_size' => count($interactions),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }
        }
    }

    private function extractPreferredCategories(array $interactions): array
    {
        $categoryScores = [];
        foreach ($interactions as $interaction) {
            $category = $interaction['category'] ?? 'other';
            $score = $interaction['interaction_score'];
            $categoryScores[$category] = ($categoryScores[$category] ?? 0) + $score;
        }

        arsort($categoryScores);
        return array_slice(array_keys($categoryScores), 0, 5, true);
    }

    private function extractPreferredBrands(array $interactions): array
    {
        $brandScores = [];
        foreach ($interactions as $interaction) {
            $brand = $interaction['brand'];
            if ($brand === null) {
                continue;
            }
            $score = $interaction['interaction_score'];
            $brandScores[$brand] = ($brandScores[$brand] ?? 0) + $score;
        }

        arsort($brandScores);
        return array_slice(array_keys($brandScores), 0, 5, true);
    }

    private function extractPriceRange(array $interactions): string
    {
        $prices = array_column(array_filter($interactions, fn($i) => $i['interaction_type'] === 'purchase'), 'price');
        
        if (empty($prices)) {
            $prices = array_column($interactions, 'price');
        }

        if (empty($prices)) {
            return 'medium';
        }

        $avgPrice = array_sum($prices) / count($prices);

        return match (true) {
            $avgPrice < 1000 => 'budget',
            $avgPrice < 5000 => 'medium',
            default => 'premium',
        };
    }

    private function extractPreferredStyles(array $interactions): array
    {
        $styleScores = [];
        foreach ($interactions as $interaction) {
            $style = $interaction['style_profile'];
            if ($style === null) {
                continue;
            }
            $score = $interaction['interaction_score'];
            $styleScores[$style] = ($styleScores[$style] ?? 0) + $score;
        }

        arsort($styleScores);
        return array_slice(array_keys($styleScores), 0, 3, true);
    }

    private function extractPreferredColors(array $interactions): array
    {
        $colorScores = [];
        foreach ($interactions as $interaction) {
            $color = $interaction['color'];
            if ($color === null) {
                continue;
            }
            $score = $interaction['interaction_score'];
            $colorScores[$color] = ($colorScores[$color] ?? 0) + $score;
        }

        arsort($colorScores);
        return array_slice(array_keys($colorScores), 0, 5, true);
    }

    private function extractShoppingFrequency(array $interactions): array
    {
        $dailyInteractions = [];
        foreach ($interactions as $interaction) {
            $date = Carbon::parse($interaction['created_at'])->format('Y-m-d');
            $dailyInteractions[$date] = ($dailyInteractions[$date] ?? 0) + 1;
        }

        $avgDaily = count($dailyInteractions) > 0 ? array_sum($dailyInteractions) / count($dailyInteractions) : 0;

        return [
            'average_daily_interactions' => $avgDaily,
            'most_active_day' => array_key_first($dailyInteractions) ?? null,
            'total_days_active' => count($dailyInteractions),
        ];
    }

    private function extractPeakShoppingHours(array $interactions): array
    {
        $hourlyInteractions = array_fill(0, 24, 0);
        foreach ($interactions as $interaction) {
            $hour = Carbon::parse($interaction['created_at'])->hour;
            $hourlyInteractions[$hour]++;
        }

        arsort($hourlyInteractions);
        $peakHours = array_slice(array_keys($hourlyInteractions), 0, 3, true);

        return [
            'peak_hours' => $peakHours,
            'total_interactions' => array_sum($hourlyInteractions),
        ];
    }

    private function extractSessionDuration(array $interactions): array
    {
        $sessionDurations = [];
        $sessions = [];

        foreach ($interactions as $interaction) {
            $date = Carbon::parse($interaction['created_at'])->format('Y-m-d');
            $sessions[$date][] = $interaction['created_at'];
        }

        foreach ($sessions as $date => $timestamps) {
            if (count($timestamps) < 2) {
                continue;
            }
            $first = min($timestamps);
            $last = max($timestamps);
            $duration = Carbon::parse($last)->diffInMinutes(Carbon::parse($first));
            $sessionDurations[] = $duration;
        }

        return [
            'average_session_duration' => count($sessionDurations) > 0 ? array_sum($sessionDurations) / count($sessionDurations) : 0,
            'total_sessions' => count($sessionDurations),
        ];
    }

    private function extractConversionRate(array $interactions): float
    {
        $totalInteractions = count($interactions);
        $purchases = count(array_filter($interactions, fn($i) => $i['interaction_type'] === 'purchase'));

        return $totalInteractions > 0 ? $purchases / $totalInteractions : 0;
    }

    private function calculatePatternConfidence(string $patternType, $patternValue, array $interactions): float
    {
        $baseConfidence = 0.5;
        $sampleSize = count($interactions);
        
        if ($sampleSize >= 100) {
            $baseConfidence += 0.3;
        } elseif ($sampleSize >= 50) {
            $baseConfidence += 0.2;
        } elseif ($sampleSize >= 20) {
            $baseConfidence += 0.1;
        }

        if (is_array($patternValue)) {
            $baseConfidence += min(count($patternValue) * 0.05, 0.2);
        }

        return min($baseConfidence, 1.0);
    }

    private function getRecentInteractions(int $userId, int $limit): array
    {
        return $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function getCurrentSessionInteractions(int $userId): array
    {
        $sessionStart = Carbon::now()->subMinutes(30);

        return $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $sessionStart)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    private function getUserInteractionHistory(int $userId, int $days): array
    {
        return $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    private function predictPurchaseLikelihood(array $patterns, array $recentInteractions): float
    {
        $likelihood = 0.3;
        $conversionRate = $patterns['patterns']['conversion_rate']['pattern_value'] ?? 0;
        
        $likelihood += $conversionRate * 0.5;

        $recentCartActions = count(array_filter($recentInteractions, fn($i) => $i['interaction_type'] === 'add_to_cart'));
        $likelihood += min($recentCartActions * 0.1, 0.2);

        return min($likelihood, 1.0);
    }

    private function predictLikelyCategories(array $patterns, array $recentInteractions): array
    {
        return $patterns['patterns']['preferred_categories']['pattern_value'] ?? [];
    }

    private function predictPriceRange(array $patterns, array $recentInteractions): string
    {
        return $patterns['patterns']['price_range']['pattern_value'] ?? 'medium';
    }

    private function predictBrands(array $patterns, array $recentInteractions): array
    {
        return $patterns['patterns']['preferred_brands']['pattern_value'] ?? [];
    }

    private function predictOptimalConversionTime(array $patterns, array $recentInteractions): string
    {
        $peakHours = $patterns['patterns']['peak_shopping_hours']['pattern_value']['peak_hours'] ?? [12, 18, 20];
        $currentHour = Carbon::now()->hour;

        if (in_array($currentHour, $peakHours)) {
            return 'now';
        }

        $nextPeak = null;
        foreach ($peakHours as $hour) {
            if ($hour > $currentHour) {
                $nextPeak = $hour;
                break;
            }
        }

        return $nextPeak !== null ? "today at {$nextPeak}:00" : 'tomorrow';
    }

    private function predictCartAbandonmentRisk(array $patterns, array $currentSession): float
    {
        $risk = 0.3;
        $sessionDuration = count($currentSession);

        if ($sessionDuration > 20) {
            $risk += 0.2;
        }

        $recentViews = count(array_filter($currentSession, fn($i) => $i['interaction_type'] === 'view'));
        if ($recentViews > 10 && !array_filter($currentSession, fn($i) => $i['interaction_type'] === 'add_to_cart')) {
            $risk += 0.3;
        }

        return min($risk, 1.0);
    }

    private function recommendNextAction(array $patterns, array $recentInteractions): array
    {
        $actions = [];

        $cartItems = count(array_filter($recentInteractions, fn($i) => $i['interaction_type'] === 'add_to_cart'));
        if ($cartItems > 0) {
            $actions[] = ['action' => 'checkout', 'priority' => 'high', 'reason' => 'Items in cart'];
        }

        $views = count(array_filter($recentInteractions, fn($i) => $i['interaction_type'] === 'view'));
        if ($views > 5 && $cartItems === 0) {
            $actions[] = ['action' => 'add_to_cart_prompt', 'priority' => 'medium', 'reason' => 'High engagement'];
        }

        $actions[] = ['action' => 'show_recommendations', 'priority' => 'low', 'reason' => 'Personalized suggestions'];

        return $actions;
    }

    private function calculatePredictionConfidence(array $patterns, array $recentInteractions): float
    {
        $confidence = 0.5;
        $confidence += count($patterns['patterns']) * 0.05;
        $confidence += min(count($recentInteractions) * 0.02, 0.3);

        return min($confidence, 1.0);
    }

    private function calculateRelevanceScore(array $product, array $patterns): float
    {
        $score = 0.5;
        $productCategories = $this->getProductCategories((int) $product['id']);

        $preferredCategories = $patterns['patterns']['preferred_categories']['pattern_value'] ?? [];
        if (in_array($productCategories['primary'], $preferredCategories)) {
            $score += 0.2;
        }

        $preferredBrands = $patterns['patterns']['preferred_brands']['pattern_value'] ?? [];
        if (in_array($product['brand'], $preferredBrands)) {
            $score += 0.15;
        }

        $preferredStyles = $patterns['patterns']['preferred_styles']['pattern_value'] ?? [];
        if (in_array($productCategories['style_profile'], $preferredStyles)) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }

    private function getRecommendationReason(array $product, array $patterns): string
    {
        $reasons = [];

        $productCategories = $this->getProductCategories((int) $product['id']);
        $preferredCategories = $patterns['patterns']['preferred_categories']['pattern_value'] ?? [];
        if (in_array($productCategories['primary'], $preferredCategories)) {
            $reasons[] = 'Matches your preferred category';
        }

        $preferredBrands = $patterns['patterns']['preferred_brands']['pattern_value'] ?? [];
        if (in_array($product['brand'], $preferredBrands)) {
            $reasons[] = 'Brand you like';
        }

        return implode(', ', $reasons) ?: 'Popular item';
    }

    private function getPriceRangeBounds(string $priceRange): array
    {
        return match ($priceRange) {
            'budget' => ['min' => 0, 'max' => 2000],
            'medium' => ['min' => 1000, 'max' => 5000],
            'premium' => ['min' => 3000, 'max' => PHP_INT_MAX],
            default => ['min' => 0, 'max' => PHP_INT_MAX],
        };
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
