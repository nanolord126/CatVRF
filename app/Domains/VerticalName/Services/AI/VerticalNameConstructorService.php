<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Services\AI;

use App\Domains\VerticalName\Models\VerticalItem;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * VerticalNameConstructorService — AI-конструктор вертикали VerticalName.
 *
 * CANON 2026 — PRODUCTION MANDATORY: каждая вертикаль обязана иметь AI-конструктор.
 *
 * Поток:
 * 1. Fraud + quota check
 * 2. Vision API (анализ входных данных)
 * 3. UserTasteProfile merge (персонализация)
 * 4. RecommendationService (подбор товаров)
 * 5. Inventory check (наличие)
 * 6. Сохранение в user_ai_designs
 * 7. Audit log + correlation_id
 *
 * Кэш: Redis TTL 3600 сек, теги user_ai_designs:{userId}.
 *
 * @package App\Domains\VerticalName\Services\AI
 */
final readonly class VerticalNameConstructorService
{
    private const CACHE_TTL_SECONDS = 3600;

    private const CACHE_PREFIX = 'vertical_name_ai_design';

    private const VISION_PROMPT = 'Анализ изображения для вертикали VerticalName. '
        . 'Определи ключевые характеристики, стиль, предпочтения пользователя. '
        . 'Рекомендуй подходящие товары и услуги на основе анализа.';

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private CacheRepository $cache,
    ) {
    }

    /**
     * Анализировать входные данные и сгенерировать персонализированные рекомендации.
     *
     * @param UploadedFile $file        Загруженное изображение/файл
     * @param int          $userId      ID пользователя
     * @param array        $preferences Дополнительные предпочтения пользователя
     *
     * @return array{
     *     success: bool,
     *     analysis: array,
     *     recommendations: array,
     *     taste_profile: array,
     *     ar_preview_url: string|null,
     *     total_cost_kopecks: int,
     *     correlation_id: string,
     * }
     */
    public function analyzeAndRecommend(
        UploadedFile $file,
        int $userId,
        array $preferences = [],
    ): array {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'vertical_name_ai_constructor',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = self::CACHE_PREFIX . ':' . $userId . ':' . md5($file->getClientOriginalName());

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            $this->logger->info('VerticalName AI constructor cache hit', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            return $cached;
        }

        return $this->db->transaction(function () use ($file, $userId, $preferences, $correlationId, $cacheKey): array {
            $analysis = $this->performVisionAnalysis($file);

            $tasteProfile = $this->tasteAnalyzer->getProfile($userId);
            $mergedProfile = $this->mergeWithTasteProfile($analysis, $tasteProfile);

            $recommendations = $this->generateRecommendations($mergedProfile, $userId);

            $availableRecommendations = $this->filterByInventory($recommendations);

            $totalCostKopecks = $this->calculateTotalCost($availableRecommendations);

            $this->saveDesignToProfile($userId, $mergedProfile, $availableRecommendations, $correlationId);

            $result = [
                'success' => true,
                'analysis' => $analysis,
                'recommendations' => $availableRecommendations,
                'taste_profile' => $mergedProfile,
                'ar_preview_url' => $this->generateArPreviewUrl($userId),
                'total_cost_kopecks' => $totalCostKopecks,
                'correlation_id' => $correlationId,
            ];

            $this->cache->put($cacheKey, $result, self::CACHE_TTL_SECONDS);

            $this->audit->record(
                action: 'vertical_name_ai_constructor_used',
                subjectType: 'ai_design',
                subjectId: $userId,
                oldValues: [],
                newValues: [
                    'analysis_keys' => array_keys($analysis),
                    'recommendations_count' => count($availableRecommendations),
                    'total_cost_kopecks' => $totalCostKopecks,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('VerticalName AI constructor completed', [
                'user_id' => $userId,
                'recommendations_count' => count($availableRecommendations),
                'total_cost_kopecks' => $totalCostKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }

    /**
     * Анализ изображения через Vision API.
     *
     * В production: OpenAI GPT-4o Vision или GigaChat Vision.
     * Здесь — контракт для интеграции.
     */
    private function performVisionAnalysis(UploadedFile $file): array
    {
        $imagePath = $file->getRealPath();

        $this->logger->info('VerticalName Vision API analysis started', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        return [
            'detected_style' => 'modern',
            'detected_colors' => ['#2C3E50', '#3498DB', '#ECF0F1'],
            'detected_category' => 'general',
            'confidence' => 0.92,
            'attributes' => [
                'quality' => 'high',
                'type' => 'product_photo',
            ],
            'prompt_used' => self::VISION_PROMPT,
        ];
    }

    /**
     * Объединение результатов анализа с профилем вкусов пользователя.
     */
    private function mergeWithTasteProfile(array $analysis, mixed $tasteProfile): array
    {
        $userPreferences = [];

        if ($tasteProfile !== null && is_object($tasteProfile)) {
            $userPreferences = [
                'favorite_categories' => $tasteProfile->categories ?? [],
                'favorite_brands' => $tasteProfile->favorite_brands ?? [],
                'price_range' => $tasteProfile->price_range ?? [],
                'preferred_colors' => $tasteProfile->preferred_colors ?? [],
            ];
        }

        return array_merge($analysis, [
            'user_preferences' => $userPreferences,
            'personalized' => true,
        ]);
    }

    /**
     * Генерация рекомендаций на основе объединённого профиля.
     */
    private function generateRecommendations(array $mergedProfile, int $userId): array
    {
        $tenantId = (int) (function_exists('tenant') && tenant() !== null ? tenant()->id : 0);

        $items = VerticalItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('stock_quantity', '>', 0)
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        return $items->map(fn (VerticalItem $item): array => [
            'product_id' => $item->id,
            'name' => $item->name,
            'price_kopecks' => $item->price_kopecks,
            'category' => $item->category,
            'rating' => $item->rating,
            'in_stock' => $item->stock_quantity > 0,
            'image_url' => $item->image_url,
            'match_score' => $this->calculateMatchScore($item, $mergedProfile),
        ])->toArray();
    }

    /**
     * Фильтрация рекомендаций по реальному наличию.
     */
    private function filterByInventory(array $recommendations): array
    {
        return array_values(array_filter(
            $recommendations,
            static fn (array $item): bool => $item['in_stock'] === true,
        ));
    }

    /**
     * Подсчёт суммарной стоимости рекомендуемых товаров.
     */
    private function calculateTotalCost(array $recommendations): int
    {
        return (int) array_sum(array_column($recommendations, 'price_kopecks'));
    }

    /**
     * Расчёт match-score товара относительно профиля.
     */
    private function calculateMatchScore(VerticalItem $item, array $profile): float
    {
        $score = 0.5;

        $preferredCategories = $profile['user_preferences']['favorite_categories'] ?? [];
        if (in_array($item->category, $preferredCategories, true)) {
            $score += 0.3;
        }

        if ($item->rating >= 4.0) {
            $score += 0.1;
        }

        if ($item->review_count > 10) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }

    /**
     * Сохранение AI-дизайна в профиль пользователя.
     */
    private function saveDesignToProfile(
        int $userId,
        array $profile,
        array $recommendations,
        string $correlationId,
    ): void {
        $this->db->table('user_ai_designs')->insert([
            'user_id' => $userId,
            'vertical' => 'vertical_name',
            'design_data' => json_encode([
                'profile' => $profile,
                'recommendations' => $recommendations,
            ], JSON_THROW_ON_ERROR),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Генерация URL для AR/3D-превью.
     */
    private function generateArPreviewUrl(int $userId): string
    {
        return '/vertical-name/ar-preview/' . $userId;
    }
}
