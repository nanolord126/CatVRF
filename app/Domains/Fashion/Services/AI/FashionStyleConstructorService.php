<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services\AI;

use App\Domains\Fashion\DTOs\FashionStyleAnalysisDto;
use App\Domains\Fashion\DTOs\FashionVirtualTryOnDto;
use App\Domains\Fashion\DTOs\FashionDynamicPricingDto;
use App\Domains\Fashion\DTOs\FashionWebRTCSessionDto;
use App\Domains\Fashion\DTOs\FashionLoyaltyDto;
use App\Domains\Fashion\DTOs\FashionARPreviewDto;
use App\Domains\Fashion\Events\StyleAnalysisCompletedEvent;
use App\Domains\Fashion\Events\VirtualTryOnCompletedEvent;
use App\Domains\Fashion\Events\WebRTCSessionInitiatedEvent;
use App\Domains\Fashion\Events\DynamicPriceAppliedEvent;
use App\Domains\Fashion\Jobs\Generate3DModelJob;
use App\Domains\Fashion\Jobs\CalculateTrendScoreJob;
use App\Domains\Fashion\Jobs\SyncWithCRMJob;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\ML\UserBehaviorAnalyzerService;
use App\Services\RecommendationService;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\BonusService;
use App\Services\Payment\PaymentService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\CRM\Services\CRMIntegrationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use OpenAI\Client as OpenAIClient;

/**
 * AI-конструктор стиля для вертикали Fashion с killer-features.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 *
 * Killer-features:
 * - AI-подбор одежды по фото/стилистике + virtual try-on (AR + embeddings)
 * - Real-time 3D-модели товаров + персонализированные look'и
 * - Dynamic pricing + flash-sale по AI-прогнозу трендов
 * - Instant video-call с стилистом перед покупкой (WebRTC)
 * - AR-примерка в реальном времени (Vue 3 + model-viewer)
 * - Loyalty с gamification и NFT-цифровыми аватарами
 * - B2C: быстрый checkout. B2B: опт для бутиков, dropshipping, commission split
 * - ML-fraud по возвратах и поведении
 * - Wallet + instant cashback + split payment (клиент + бренд + маркетплейс)
 * - CRM-интеграция на каждом статусе (покупка, возврат, отзыв)
 */
final readonly class FashionStyleConstructorService
{
    private const CACHE_TTL_ANALYSIS = 3600;
    private const CACHE_TTL_PRICING = 300;
    private const CACHE_TTL_TRY_ON = 1800;
    private const MAX_3D_MODELS_PER_DAY = 10;
    private const MAX_WEBRTC_SESSIONS_PER_DAY = 3;
    private const MIN_TREND_SCORE_FOR_FLASH_SALE = 0.85;
    private const FLASH_SALE_DISCOUNT_MAX = 0.30;
    private const LOYALTY_POINTS_PER_PURCHASE = 100;
    private const LOYALTY_POINTS_PER_TRY_ON = 10;
    private const LOYALTY_NFT_UNLOCK_THRESHOLD = 5000;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private WalletService $wallet,
        private BonusService $bonus,
        private PaymentService $payment,
        private CRMIntegrationService $crm,
        private OpenAIClient $openai,
        private \Illuminate\Contracts\Cache\Repository $cache,
        private \Illuminate\Database\DatabaseManager $db,
        private \Illuminate\Contracts\Bus\Dispatcher $bus,
    ) {}

    /**
     * Анализировать фото пользователя и сгенерировать персонализированный стиль.
     * Включает AI-анализ внешности, цветотипа, типажа фигуры, рекомендации по капсульному гардеробу.
     */
    public function analyzeAndRecommend(
        UploadedFile $photo,
        int $userId,
        ?string $eventType = null,
        bool $isB2B = false,
        string $correlationId = ''
    ): FashionStyleAnalysisDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();
        $businessGroupId = $isB2B ? $this->getBusinessGroupId($userId) : null;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_style_analysis',
            amount: 0,
            correlationId: $correlationId
        );

        $cacheKey = "fashion_style_analysis:{$tenantId}:{$userId}:" . md5($photo->getClientOriginalName() . ($eventType ?? ''));

        $result = $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL_ANALYSIS), function () use (
            $photo,
            $userId,
            $eventType,
            $isB2B,
            $correlationId,
            $tenantId,
            $businessGroupId
        ) {
            return $this->db->transaction(function () use (
                $photo,
                $userId,
                $eventType,
                $isB2B,
                $correlationId,
                $tenantId,
                $businessGroupId
            ) {
                $photoPath = $photo->store('fashion/style-photos', 's3');
                $photoUrl = Storage::disk('s3')->url($photoPath);

                $visionAnalysis = $this->analyzePhotoWithVision($photo, $correlationId);
                $tasteProfile = $this->tasteAnalyzer->getProfile($userId);
                $behaviorPattern = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));

                $styleProfile = $this->mergeStyleProfile($visionAnalysis, $tasteProfile, $behaviorPattern, $eventType);

                $recommendations = $this->getPersonalizedRecommendations(
                    $userId,
                    $styleProfile,
                    $isB2B,
                    $tenantId,
                    $businessGroupId,
                    $correlationId
                );

                $capsuleWardrobe = $this->buildCapsuleWardrobe($recommendations, $styleProfile);

                $embeddingVector = $this->generateEmbeddingVector($styleProfile, $correlationId);

                $designId = $this->saveStyleDesign(
                    $userId,
                    $tenantId,
                    $businessGroupId,
                    $styleProfile,
                    $capsuleWardrobe,
                    $embeddingVector,
                    $photoUrl,
                    $correlationId
                );

                $this->awardLoyaltyPoints($userId, self::LOYALTY_POINTS_PER_TRY_ON, 'style_analysis', $correlationId);

                $this->audit->record(
                    action: 'fashion_style_analysis_completed',
                    subjectType: 'fashion_style_design',
                    subjectId: $designId,
                    oldValues: [],
                    newValues: [
                        'style_profile' => $styleProfile,
                        'capsule_items' => count($capsuleWardrobe),
                        'recommendations_count' => count($recommendations),
                        'is_b2b' => $isB2B,
                    ],
                    correlationId: $correlationId
                );

                event(new StyleAnalysisCompletedEvent(
                    designId: $designId,
                    userId: $userId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    styleProfile: $styleProfile,
                    correlationId: $correlationId
                ));

                $this->bus->dispatch(new SyncWithCRMJob(
                    userId: $userId,
                    vertical: 'fashion',
                    action: 'style_analysis',
                    data: ['design_id' => $designId, 'style_profile' => $styleProfile],
                    correlationId: $correlationId
                ));

                Log::channel('audit')->info('Fashion style analysis completed', [
                    'design_id' => $designId,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'business_group_id' => $businessGroupId,
                    'color_type' => $styleProfile['color_type'] ?? 'unknown',
                    'capsule_items' => count($capsuleWardrobe),
                    'correlation_id' => $correlationId,
                ]);

                return new FashionStyleAnalysisDto(
                    designId: $designId,
                    userId: $userId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    styleProfile: $styleProfile,
                    capsuleWardrobe: $capsuleWardrobe,
                    recommendations: $recommendations,
                    embeddingVector: $embeddingVector,
                    photoUrl: $photoUrl,
                    arTryOnUrl: url("/fashion/ar-try-on/{$designId}"),
                    threeDModelsUrl: url("/fashion/3d-models/{$designId}"),
                    correlationId: $correlationId,
                );
            });
        });

        return $result;
    }

    /**
     * Виртуальная примерка одежды с AR + embeddings.
     * Генерирует AR-превью и рассчитывает fit-score.
     */
    public function virtualTryOn(
        int $designId,
        int $userId,
        array $productIds,
        bool $isB2B = false,
        string $correlationId = ''
    ): FashionVirtualTryOnDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();
        $businessGroupId = $isB2B ? $this->getBusinessGroupId($userId) : null;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_virtual_try_on',
            amount: 0,
            correlationId: $correlationId
        );

        $cacheKey = "fashion_virtual_try_on:{$tenantId}:{$designId}:" . md5(implode(',', $productIds));

        $result = $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL_TRY_ON), function () use (
            $designId,
            $userId,
            $productIds,
            $isB2B,
            $correlationId,
            $tenantId,
            $businessGroupId
        ) {
            return $this->db->transaction(function () use (
                $designId,
                $userId,
                $productIds,
                $isB2B,
                $correlationId,
                $tenantId,
                $businessGroupId
            ) {
                $design = $this->db->table('user_ai_designs')
                    ->where('id', $designId)
                    ->where('user_id', $userId)
                    ->where('vertical', 'fashion')
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($design === null) {
                    throw new \InvalidArgumentException('Style design not found', 404);
                }

                $styleProfile = json_decode($design->design_data, true)['style_profile'] ?? [];

                $tryOnResults = [];
                $totalFitScore = 0.0;

                foreach ($productIds as $productId) {
                    $product = $this->inventory->getProduct($productId);
                    if ($product === null) {
                        continue;
                    }

                    $fitScore = $this->calculateFitScore($styleProfile, $product, $correlationId);
                    $arPreviewUrl = $this->generateARPreview($designId, $productId, $styleProfile, $correlationId);
                    $embeddingSimilarity = $this->calculateEmbeddingSimilarity($styleProfile, $product, $correlationId);

                    $tryOnResults[] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'] ?? '',
                        'fit_score' => $fitScore,
                        'ar_preview_url' => $arPreviewUrl,
                        'embedding_similarity' => $embeddingSimilarity,
                        'in_stock' => $this->inventory->getAvailableStock($productId) > 0,
                        'price' => $isB2B ? ($product['wholesale_price'] ?? $product['price']) : $product['price'],
                    ];

                    $totalFitScore += $fitScore;
                }

                $averageFitScore = count($tryOnResults) > 0 ? $totalFitScore / count($tryOnResults) : 0.0;

                $this->saveVirtualTryOnResult(
                    $designId,
                    $userId,
                    $tenantId,
                    $businessGroupId,
                    $tryOnResults,
                    $averageFitScore,
                    $correlationId
                );

                $this->awardLoyaltyPoints($userId, self::LOYALTY_POINTS_PER_TRY_ON, 'virtual_try_on', $correlationId);

                $this->audit->record(
                    action: 'fashion_virtual_try_on_completed',
                    subjectType: 'fashion_virtual_try_on',
                    subjectId: $designId,
                    oldValues: [],
                    newValues: [
                        'products_count' => count($tryOnResults),
                        'average_fit_score' => $averageFitScore,
                        'is_b2b' => $isB2B,
                    ],
                    correlationId: $correlationId
                );

                event(new VirtualTryOnCompletedEvent(
                    designId: $designId,
                    userId: $userId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    tryOnResults: $tryOnResults,
                    averageFitScore: $averageFitScore,
                    correlationId: $correlationId
                ));

                Log::channel('audit')->info('Fashion virtual try-on completed', [
                    'design_id' => $designId,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'products_count' => count($tryOnResults),
                    'average_fit_score' => $averageFitScore,
                    'correlation_id' => $correlationId,
                ]);

                return new FashionVirtualTryOnDto(
                    designId: $designId,
                    userId: $userId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    tryOnResults: $tryOnResults,
                    averageFitScore: $averageFitScore,
                    correlationId: $correlationId,
                );
            });
        });

        return $result;
    }

    /**
     * Динамическое ценообразование на основе AI-прогноза трендов.
     * Автоматически применяет flash-sale при высоком trend score.
     */
    public function applyDynamicPricing(
        int $productId,
        int $userId,
        bool $isB2B = false,
        string $correlationId = ''
    ): FashionDynamicPricingDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();
        $businessGroupId = $isB2B ? $this->getBusinessGroupId($userId) : null;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_dynamic_pricing',
            amount: 0,
            correlationId: $correlationId
        );

        $cacheKey = "fashion_dynamic_pricing:{$tenantId}:{$productId}";

        $result = $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL_PRICING), function () use (
            $productId,
            $userId,
            $isB2B,
            $correlationId,
            $tenantId,
            $businessGroupId
        ) {
            return $this->db->transaction(function () use (
                $productId,
                $userId,
                $isB2B,
                $correlationId,
                $tenantId,
                $businessGroupId
            ) {
                $product = $this->inventory->getProduct($productId);
                if ($product === null) {
                    throw new \InvalidArgumentException('Product not found', 404);
                }

                $basePrice = $isB2B ? ($product['wholesale_price'] ?? $product['price']) : $product['price'];
                $trendScore = $this->calculateTrendScore($productId, $correlationId);
                $demandForecast = $this->forecastDemand($productId, $correlationId);
                $stockLevel = $this->inventory->getAvailableStock($productId);
                $userSegment = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));

                $dynamicPrice = $basePrice;
                $discountPercent = 0.0;
                $isFlashSale = false;
                $flashSaleEndTime = null;

                if ($trendScore >= self::MIN_TREND_SCORE_FOR_FLASH_SALE && $stockLevel > 10 && $demandForecast['velocity'] > 0.7) {
                    $discountPercent = min($trendScore * self::FLASH_SALE_DISCOUNT_MAX, self::FLASH_SALE_DISCOUNT_MAX);
                    $dynamicPrice = $basePrice * (1 - $discountPercent);
                    $isFlashSale = true;
                    $flashSaleEndTime = Carbon::now()->addHours(4);
                } elseif ($stockLevel < 5 && $demandForecast['velocity'] < 0.3) {
                    $discountPercent = 0.15;
                    $dynamicPrice = $basePrice * (1 - $discountPercent);
                } elseif (($userSegment['price_sensitivity'] ?? 0.5) > 0.7) {
                    $discountPercent = 0.10;
                    $dynamicPrice = $basePrice * (1 - $discountPercent);
                }

                $dynamicPrice = round($dynamicPrice, 2);

                $this->saveDynamicPricing(
                    $productId,
                    $tenantId,
                    $businessGroupId,
                    $basePrice,
                    $dynamicPrice,
                    $discountPercent,
                    $trendScore,
                    $isFlashSale,
                    $flashSaleEndTime,
                    $correlationId
                );

                $this->audit->record(
                    action: 'fashion_dynamic_pricing_applied',
                    subjectType: 'fashion_product',
                    subjectId: $productId,
                    oldValues: ['base_price' => $basePrice],
                    newValues: [
                        'dynamic_price' => $dynamicPrice,
                        'discount_percent' => $discountPercent,
                        'trend_score' => $trendScore,
                        'is_flash_sale' => $isFlashSale,
                        'is_b2b' => $isB2B,
                    ],
                    correlationId: $correlationId
                );

                event(new DynamicPriceAppliedEvent(
                    productId: $productId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    basePrice: $basePrice,
                    dynamicPrice: $dynamicPrice,
                    discountPercent: $discountPercent,
                    trendScore: $trendScore,
                    isFlashSale: $isFlashSale,
                    flashSaleEndTime: $flashSaleEndTime,
                    correlationId: $correlationId
                ));

                $this->bus->dispatch(new CalculateTrendScoreJob(
                    productId: $productId,
                    correlationId: $correlationId
                ));

                Log::channel('audit')->info('Fashion dynamic pricing applied', [
                    'product_id' => $productId,
                    'tenant_id' => $tenantId,
                    'base_price' => $basePrice,
                    'dynamic_price' => $dynamicPrice,
                    'discount_percent' => $discountPercent,
                    'trend_score' => $trendScore,
                    'is_flash_sale' => $isFlashSale,
                    'correlation_id' => $correlationId,
                ]);

                return new FashionDynamicPricingDto(
                    productId: $productId,
                    tenantId: $tenantId,
                    businessGroupId: $businessGroupId,
                    basePrice: $basePrice,
                    dynamicPrice: $dynamicPrice,
                    discountPercent: $discountPercent,
                    trendScore: $trendScore,
                    isFlashSale: $isFlashSale,
                    flashSaleEndTime: $flashSaleEndTime,
                    correlationId: $correlationId,
                );
            });
        });

        return $result;
    }

    /**
     * Инициировать WebRTC-сессию с персональным стилистом.
     * Предоставляет instant video-call перед покупкой.
     */
    public function initiateWebRTCSession(
        int $userId,
        ?int $stylistId = null,
        ?string $scheduledTime = null,
        bool $isB2B = false,
        string $correlationId = ''
    ): FashionWebRTCSessionDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();
        $businessGroupId = $isB2B ? $this->getBusinessGroupId($userId) : null;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_webrtc_session',
            amount: 0,
            correlationId: $correlationId
        );

        $dailySessionCount = $this->cache->get("fashion_webrtc_daily:{$userId}", 0);
        if ($dailySessionCount >= self::MAX_WEBRTC_SESSIONS_PER_DAY) {
            throw new \RuntimeException('Daily WebRTC session limit exceeded', 429);
        }

        return $this->db->transaction(function () use (
            $userId,
            $stylistId,
            $scheduledTime,
            $isB2B,
            $correlationId,
            $tenantId,
            $businessGroupId,
            &$dailySessionCount
        ) {
            $actualStylistId = $stylistId ?? $this->findAvailableStylist($tenantId, $correlationId);
            if ($actualStylistId === null) {
                throw new \RuntimeException('No available stylists at the moment', 503);
            }

            $sessionId = Str::uuid()->toString();
            $sessionToken = $this->generateWebRTCToken($sessionId, $userId, $actualStylistId, $correlationId);

            $sessionData = [
                'id' => $sessionId,
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'user_id' => $userId,
                'stylist_id' => $actualStylistId,
                'session_token' => $sessionToken,
                'status' => 'initiated',
                'scheduled_at' => $scheduledTime ? Carbon::parse($scheduledTime) : Carbon::now()->addMinutes(5),
                'expires_at' => Carbon::now()->addHours(2),
                'correlation_id' => $correlationId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            $this->db->table('fashion_webrtc_sessions')->insert($sessionData);

            $this->cache->put("fashion_webrtc_daily:{$userId}", $dailySessionCount + 1, Carbon::now()->endOfDay());

            $this->audit->record(
                action: 'fashion_webrtc_session_initiated',
                subjectType: 'fashion_webrtc_session',
                subjectId: $sessionId,
                oldValues: [],
                newValues: [
                    'user_id' => $userId,
                    'stylist_id' => $actualStylistId,
                    'scheduled_at' => $sessionData['scheduled_at'],
                    'is_b2b' => $isB2B,
                ],
                correlationId: $correlationId
            );

            event(new WebRTCSessionInitiatedEvent(
                sessionId: $sessionId,
                userId: $userId,
                stylistId: $actualStylistId,
                tenantId: $tenantId,
                businessGroupId: $businessGroupId,
                scheduledAt: $sessionData['scheduled_at'],
                correlationId: $correlationId
            ));

            Log::channel('audit')->info('Fashion WebRTC session initiated', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'stylist_id' => $actualStylistId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return new FashionWebRTCSessionDto(
                sessionId: $sessionId,
                userId: $userId,
                stylistId: $actualStylistId,
                tenantId: $tenantId,
                businessGroupId: $businessGroupId,
                sessionToken: $sessionToken,
                status: 'initiated',
                scheduledAt: $sessionData['scheduled_at'],
                expiresAt: $sessionData['expires_at'],
                webrtcUrl: url("/fashion/webrtc/session/{$sessionId}"),
                correlationId: $correlationId,
            );
        });
    }

    /**
     * Получить AR-превью для примерки.
     * Возвращает данные для Vue 3 + model-viewer.
     */
    public function getARPreview(
        int $designId,
        int $productId,
        int $userId,
        string $correlationId = ''
    ): FashionARPreviewDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $design = $this->db->table('user_ai_designs')
            ->where('id', $designId)
            ->where('user_id', $userId)
            ->where('vertical', 'fashion')
            ->where('tenant_id', $tenantId)
            ->first();

        if ($design === null) {
            throw new \InvalidArgumentException('Style design not found', 404);
        }

        $styleProfile = json_decode($design->design_data, true)['style_profile'] ?? [];
        $product = $this->inventory->getProduct($productId);

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found', 404);
        }

        $arModelUrl = $this->generateARModel($designId, $productId, $styleProfile, $correlationId);
        $textureUrl = $product['texture_url'] ?? null;
        $modelViewerConfig = $this->buildModelViewerConfig($product, $styleProfile);

        $this->audit->record(
            action: 'fashion_ar_preview_accessed',
            subjectType: 'fashion_ar_preview',
            subjectId: $designId,
            oldValues: [],
            newValues: [
                'product_id' => $productId,
                'user_id' => $userId,
            ],
            correlationId: $correlationId
        );

        return new FashionARPreviewDto(
            designId: $designId,
            productId: $productId,
            userId: $userId,
            tenantId: $tenantId,
            arModelUrl: $arModelUrl,
            textureUrl: $textureUrl,
            modelViewerConfig: $modelViewerConfig,
            correlationId: $correlationId,
        );
    }

    /**
     * Начислить loyalty points и проверить NFT-аватар.
     * Gamification с NFT-цифровыми аватарами.
     */
    public function processLoyaltyReward(
        int $userId,
        int $orderId,
        float $orderAmount,
        string $rewardType = 'purchase',
        string $correlationId = ''
    ): FashionLoyaltyDto {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_loyalty_reward',
            amount: (int) $orderAmount,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use (
            $userId,
            $orderId,
            $orderAmount,
            $rewardType,
            $correlationId,
            $tenantId
        ) {
            $pointsMultiplier = match ($rewardType) {
                'purchase' => 1.0,
                'review' => 0.5,
                'referral' => 2.0,
                default => 1.0,
            };

            $basePoints = self::LOYALTY_POINTS_PER_PURCHASE;
            $amountMultiplier = min($orderAmount / 1000, 5.0);
            $totalPoints = (int) ($basePoints * $pointsMultiplier * $amountMultiplier);

            $currentPoints = $this->db->table('fashion_loyalty_points')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->value('total_points') ?? 0;

            $newPoints = $currentPoints + $totalPoints;

            $this->db->table('fashion_loyalty_points')->updateOrInsert(
                ['user_id' => $userId, 'tenant_id' => $tenantId],
                [
                    'total_points' => $newPoints,
                    'last_earned_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

            $this->db->table('fashion_loyalty_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'points_earned' => $totalPoints,
                'reward_type' => $rewardType,
                'correlation_id' => $correlationId,
                'created_at' => Carbon::now(),
            ]);

            $nftUnlocked = false;
            $nftAvatarUrl = null;
            $nftMetadata = null;

            if ($newPoints >= self::LOYALTY_NFT_UNLOCK_THRESHOLD && $currentPoints < self::LOYALTY_NFT_UNLOCK_THRESHOLD) {
                $nftResult = $this->generateNFTAvatar($userId, $newPoints, $correlationId);
                $nftUnlocked = $nftResult['unlocked'];
                $nftAvatarUrl = $nftResult['avatar_url'];
                $nftMetadata = $nftResult['metadata'];

                $this->db->table('fashion_nft_avatars')->insert([
                    'id' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'avatar_url' => $nftAvatarUrl,
                    'metadata' => json_encode($nftMetadata),
                    'points_threshold' => self::LOYALTY_NFT_UNLOCK_THRESHOLD,
                    'correlation_id' => $correlationId,
                    'created_at' => Carbon::now(),
                ]);
            }

            $tier = $this->calculateLoyaltyTier($newPoints);

            $this->audit->record(
                action: 'fashion_loyalty_reward_processed',
                subjectType: 'fashion_loyalty',
                subjectId: $userId,
                oldValues: ['previous_points' => $currentPoints],
                newValues: [
                    'new_points' => $newPoints,
                    'points_earned' => $totalPoints,
                    'reward_type' => $rewardType,
                    'tier' => $tier,
                    'nft_unlocked' => $nftUnlocked,
                ],
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Fashion loyalty reward processed', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'points_earned' => $totalPoints,
                'new_points' => $newPoints,
                'tier' => $tier,
                'nft_unlocked' => $nftUnlocked,
                'correlation_id' => $correlationId,
            ]);

            return new FashionLoyaltyDto(
                userId: $userId,
                tenantId: $tenantId,
                currentPoints: $newPoints,
                pointsEarned: $totalPoints,
                tier: $tier,
                nftUnlocked: $nftUnlocked,
                nftAvatarUrl: $nftAvatarUrl,
                nftMetadata: $nftMetadata,
                correlationId: $correlationId,
            );
        });
    }

    /**
     * Split payment: клиент + бренд + маркетплейс.
     * Wallet + instant cashback integration.
     */
    public function processSplitPayment(
        int $userId,
        int $orderId,
        float $totalAmount,
        array $splitConfig,
        bool $isB2B = false,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();
        $businessGroupId = $isB2B ? $this->getBusinessGroupId($userId) : null;

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_split_payment',
            amount: (int) $totalAmount,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use (
            $userId,
            $orderId,
            $totalAmount,
            $splitConfig,
            $isB2B,
            $correlationId,
            $tenantId,
            $businessGroupId
        ) {
            $clientShare = $splitConfig['client_share'] ?? 0.70;
            $brandShare = $splitConfig['brand_share'] ?? 0.20;
            $marketplaceShare = $splitConfig['marketplace_share'] ?? 0.10;

            $clientAmount = $totalAmount * $clientShare;
            $brandAmount = $totalAmount * $brandShare;
            $marketplaceAmount = $totalAmount * $marketplaceShare;

            $paymentResult = $this->payment->initPayment(
                amount: $clientAmount,
                userId: $userId,
                orderId: $orderId,
                paymentMethod: 'split',
                correlationId: $correlationId
            );

            $walletCreditResult = $this->wallet->credit(
                walletId: $this->getUserWalletId($userId, $tenantId),
                amount: $clientAmount,
                type: 'payment',
                metadata: [
                    'order_id' => $orderId,
                    'split_config' => $splitConfig,
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                ],
                correlationId: $correlationId
            );

            $cashbackAmount = $totalAmount * 0.05;
            $cashbackResult = $this->bonus->award(
                userId: $userId,
                amount: $cashbackAmount,
                type: 'cashback',
                metadata: ['order_id' => $orderId],
                correlationId: $correlationId
            );

            $brandWalletId = $this->getBrandWalletId($orderId, $tenantId);
            if ($brandWalletId !== null) {
                $this->wallet->credit(
                    walletId: $brandWalletId,
                    amount: $brandAmount,
                    type: 'revenue',
                    metadata: ['order_id' => $orderId],
                    correlationId: $correlationId
                );
            }

            $marketplaceWalletId = $this->getMarketplaceWalletId($tenantId);
            $this->wallet->credit(
                walletId: $marketplaceWalletId,
                amount: $marketplaceAmount,
                type: 'commission',
                metadata: ['order_id' => $orderId],
                correlationId: $correlationId
            );

            $this->audit->record(
                action: 'fashion_split_payment_processed',
                subjectType: 'fashion_order',
                subjectId: $orderId,
                oldValues: [],
                newValues: [
                    'total_amount' => $totalAmount,
                    'client_amount' => $clientAmount,
                    'brand_amount' => $brandAmount,
                    'marketplace_amount' => $marketplaceAmount,
                    'cashback_amount' => $cashbackAmount,
                    'is_b2b' => $isB2B,
                ],
                correlationId: $correlationId
            );

            $this->bus->dispatch(new SyncWithCRMJob(
                userId: $userId,
                vertical: 'fashion',
                action: 'purchase',
                data: [
                    'order_id' => $orderId,
                    'total_amount' => $totalAmount,
                    'split_payment' => $splitConfig,
                ],
                correlationId: $correlationId
            ));

            Log::channel('audit')->info('Fashion split payment processed', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'total_amount' => $totalAmount,
                'client_amount' => $clientAmount,
                'brand_amount' => $brandAmount,
                'marketplace_amount' => $marketplaceAmount,
                'cashback_amount' => $cashbackAmount,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'payment_result' => $paymentResult,
                'wallet_credit' => $walletCreditResult,
                'cashback' => $cashbackResult,
                'splits' => [
                    'client' => $clientAmount,
                    'brand' => $brandAmount,
                    'marketplace' => $marketplaceAmount,
                ],
                'correlation_id' => $correlationId,
            ];
        });
    }

    private function analyzePhotoWithVision(UploadedFile $photo, string $correlationId): array
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyze this fashion photo and provide: 1) Color type (spring/summer/autumn/winter), 2) Contrast level (low/medium/high), 3) Figure type (hourglass/pear/apple/rectangle/inverted_triangle), 4) Preferred color palette (hex codes), 5) Recommended clothing cuts, 6) Cuts to avoid, 7) Confidence score (0-1). Return as JSON.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($photo->getRealPath())),
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1000,
            ]);

            $content = $response->choices[0]->message->content;
            $analysis = json_decode($content, true);

            if ($analysis === null || json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to parse Vision API response');
            }

            return [
                'color_type' => $analysis['color_type'] ?? 'autumn',
                'contrast_level' => $analysis['contrast_level'] ?? 'medium',
                'figure_type' => $analysis['figure_type'] ?? 'hourglass',
                'preferred_palette' => $analysis['preferred_palette'] ?? ['#8B4513', '#CD853F', '#556B2F'],
                'recommended_cuts' => $analysis['recommended_cuts'] ?? ['wrap_dress', 'a_line', 'bootcut'],
                'avoid_cuts' => $analysis['avoid_cuts'] ?? ['boxy_tops'],
                'confidence_score' => (float) ($analysis['confidence_score'] ?? 0.92),
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Vision API failed, using fallback', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'color_type' => 'autumn',
                'contrast_level' => 'medium',
                'figure_type' => 'hourglass',
                'preferred_palette' => ['#8B4513', '#CD853F', '#556B2F'],
                'recommended_cuts' => ['wrap_dress', 'a_line', 'bootcut'],
                'avoid_cuts' => ['boxy_tops'],
                'confidence_score' => 0.75,
            ];
        }
    }

    private function mergeStyleProfile(array $visionAnalysis, ?object $tasteProfile, array $behaviorPattern, ?string $eventType): array
    {
        $merged = $visionAnalysis;

        if ($tasteProfile !== null) {
            $tasteData = $tasteProfile->taste_profile ?? [];
            $merged['preferred_colors'] = array_merge(
                $merged['preferred_palette'] ?? [],
                $tasteData['preferred_colors'] ?? []
            );
            $merged['preferred_brands'] = $tasteData['preferred_brands'] ?? [];
            $merged['price_range'] = $tasteData['price_range'] ?? 'medium';
        }

        $merged['behavior_pattern'] = $behaviorPattern;
        $merged['event_type'] = $eventType;

        return $merged;
    }

    private function getPersonalizedRecommendations(
        int $userId,
        array $styleProfile,
        bool $isB2B,
        int $tenantId,
        ?int $businessGroupId,
        string $correlationId
    ): array {
        $recommendations = $this->recommendation->getForUser(
            userId: $userId,
            vertical: 'fashion',
            context: $styleProfile
        );

        $recArray = is_array($recommendations) ? $recommendations : $recommendations->toArray();

        foreach ($recArray as &$item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $item['in_stock'] = $productId > 0 ? $this->inventory->getAvailableStock($productId) > 0 : false;
            $item['price'] = $isB2B ? ($item['wholesale_price'] ?? $item['price'] ?? 0) : ($item['price'] ?? 0);
            $item['ar_try_on_url'] = $productId > 0 ? url("/fashion/ar-try-on/{$productId}") : null;
            $item['three_d_model_url'] = $productId > 0 ? url("/fashion/3d-model/{$productId}") : null;
            $item['color_match_score'] = $this->calculateColorMatch($styleProfile, $item, $correlationId);
        }
        unset($item);

        return $recArray;
    }

    private function buildCapsuleWardrobe(array $recommendations, array $styleProfile): array
    {
        $categories = ['top', 'bottom', 'dress', 'outerwear', 'shoes', 'accessories'];
        $capsule = [];

        foreach ($categories as $category) {
            $items = array_filter($recommendations, fn($r) => ($r['category'] ?? '') === $category);
            if (!empty($items)) {
                $sortedItems = array_values($items);
                usort($sortedItems, fn($a, $b) => ($b['color_match_score'] ?? 0) <=> ($a['color_match_score'] ?? 0));
                $capsule[] = $sortedItems[0];
            }
        }

        return $capsule;
    }

    private function generateEmbeddingVector(array $styleProfile, string $correlationId): array
    {
        $features = [
            ($styleProfile['color_type'] === 'spring' ? 1 : 0),
            ($styleProfile['color_type'] === 'summer' ? 1 : 0),
            ($styleProfile['color_type'] === 'autumn' ? 1 : 0),
            ($styleProfile['color_type'] === 'winter' ? 1 : 0),
            ($styleProfile['contrast_level'] === 'low' ? 1 : 0),
            ($styleProfile['contrast_level'] === 'medium' ? 1 : 0),
            ($styleProfile['contrast_level'] === 'high' ? 1 : 0),
            ($styleProfile['figure_type'] === 'hourglass' ? 1 : 0),
            ($styleProfile['figure_type'] === 'pear' ? 1 : 0),
            ($styleProfile['figure_type'] === 'apple' ? 1 : 0),
            ($styleProfile['figure_type'] === 'rectangle' ? 1 : 0),
            ($styleProfile['figure_type'] === 'inverted_triangle' ? 1 : 0),
            ($styleProfile['confidence_score'] ?? 0.5),
        ];

        $normalized = [];
        $sum = array_sum($features);
        foreach ($features as $feature) {
            $normalized[] = $sum > 0 ? $feature / $sum : 0;
        }

        return $normalized;
    }

    private function saveStyleDesign(
        int $userId,
        int $tenantId,
        ?int $businessGroupId,
        array $styleProfile,
        array $capsuleWardrobe,
        array $embeddingVector,
        string $photoUrl,
        string $correlationId
    ): int {
        $designId = $this->db->table('user_ai_designs')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'vertical' => 'fashion',
            'design_data' => json_encode([
                'style_profile' => $styleProfile,
                'capsule_wardrobe' => $capsuleWardrobe,
                'embedding_vector' => $embeddingVector,
                'photo_url' => $photoUrl,
            ], JSON_UNESCAPED_UNICODE),
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $designId;
    }

    private function calculateFitScore(array $styleProfile, array $product, string $correlationId): float
    {
        $colorScore = $this->calculateColorMatch($styleProfile, $product, $correlationId);
        $cutScore = $this->calculateCutMatch($styleProfile, $product, $correlationId);
        $styleScore = $this->calculateStyleMatch($styleProfile, $product, $correlationId);

        return ($colorScore * 0.4) + ($cutScore * 0.35) + ($styleScore * 0.25);
    }

    private function calculateColorMatch(array $styleProfile, array $product, string $correlationId): float
    {
        $productColors = $product['colors'] ?? [];
        $preferredPalette = $styleProfile['preferred_palette'] ?? [];

        if (empty($productColors) || empty($preferredPalette)) {
            return 0.5;
        }

        $matchCount = 0;
        foreach ($productColors as $productColor) {
            foreach ($preferredPalette as $preferredColor) {
                if ($this->colorsAreSimilar($productColor, $preferredColor)) {
                    $matchCount++;
                    break;
                }
            }
        }

        return min($matchCount / max(count($productColors), 1), 1.0);
    }

    private function colorsAreSimilar(string $color1, string $color2): bool
    {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        if ($rgb1 === null || $rgb2 === null) {
            return false;
        }

        $distance = sqrt(
            pow($rgb1['r'] - $rgb2['r'], 2) +
            pow($rgb1['g'] - $rgb2['g'], 2) +
            pow($rgb1['b'] - $rgb2['b'], 2)
        );

        return $distance < 100;
    }

    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    private function calculateCutMatch(array $styleProfile, array $product, string $correlationId): float
    {
        $recommendedCuts = $styleProfile['recommended_cuts'] ?? [];
        $avoidCuts = $styleProfile['avoid_cuts'] ?? [];
        $productCut = $product['cut'] ?? '';

        if (in_array($productCut, $avoidCuts, true)) {
            return 0.1;
        }

        if (in_array($productCut, $recommendedCuts, true)) {
            return 1.0;
        }

        return 0.6;
    }

    private function calculateStyleMatch(array $styleProfile, array $product, string $correlationId): float
    {
        $productStyle = $product['style'] ?? '';
        $eventType = $styleProfile['event_type'] ?? null;

        if ($eventType === null) {
            return 0.7;
        }

        $styleCompatibility = [
            'wedding' => ['formal', 'elegant', 'evening'],
            'office' => ['business', 'casual', 'smart'],
            'evening' => ['formal', 'elegant', 'cocktail'],
            'casual' => ['casual', 'relaxed', 'street'],
        ];

        $compatibleStyles = $styleCompatibility[$eventType] ?? [];

        return in_array($productStyle, $compatibleStyles, true) ? 1.0 : 0.5;
    }

    private function generateARPreview(int $designId, int $productId, array $styleProfile, string $correlationId): string
    {
        $previewPath = "fashion/ar-previews/{$designId}_{$productId}.glb";
        
        if (Storage::disk('s3')->exists($previewPath)) {
            return Storage::disk('s3')->url($previewPath);
        }

        $this->bus->dispatch(new Generate3DModelJob(
            designId: $designId,
            productId: $productId,
            styleProfile: $styleProfile,
            correlationId: $correlationId
        ));

        return url("/fashion/ar-preview/pending/{$designId}/{$productId}");
    }

    private function calculateEmbeddingSimilarity(array $styleProfile, array $product, string $correlationId): float
    {
        $userEmbedding = $styleProfile['embedding_vector'] ?? [];
        $productEmbedding = $product['embedding_vector'] ?? [];

        if (empty($userEmbedding) || empty($productEmbedding)) {
            return 0.5;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $minLength = min(count($userEmbedding), count($productEmbedding));
        for ($i = 0; $i < $minLength; $i++) {
            $dotProduct += $userEmbedding[$i] * $productEmbedding[$i];
            $normA += $userEmbedding[$i] * $userEmbedding[$i];
            $normB += $productEmbedding[$i] * $productEmbedding[$i];
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    private function saveVirtualTryOnResult(
        int $designId,
        int $userId,
        int $tenantId,
        ?int $businessGroupId,
        array $tryOnResults,
        float $averageFitScore,
        string $correlationId
    ): void {
        $this->db->table('fashion_virtual_try_on_results')->insert([
            'id' => Str::uuid()->toString(),
            'design_id' => $designId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'try_on_results' => json_encode($tryOnResults, JSON_UNESCAPED_UNICODE),
            'average_fit_score' => $averageFitScore,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
        ]);
    }

    private function calculateTrendScore(int $productId, string $correlationId): float
    {
        $views = $this->db->table('product_views')
            ->where('product_id', $productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $addToCarts = $this->db->table('cart_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $purchases = $this->db->table('order_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $socialMentions = $this->db->table('fashion_social_mentions')
            ->where('product_id', $productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $baseScore = 0.0;
        $baseScore += min($views / 1000, 0.3);
        $baseScore += min($addToCarts / 100, 0.3);
        $baseScore += min($purchases / 50, 0.25);
        $baseScore += min($socialMentions / 20, 0.15);

        return min($baseScore, 1.0);
    }

    private function forecastDemand(int $productId, string $correlationId): array
    {
        $historicalSales = $this->db->table('order_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        $totalSales = array_sum(array_column($historicalSales, 'sales'));
        $avgDailySales = count($historicalSales) > 0 ? $totalSales / count($historicalSales) : 0;
        $velocity = min($avgDailySales / 10, 1.0);

        $trend = 'stable';
        if (count($historicalSales) >= 2) {
            $recentSales = $historicalSales[count($historicalSales) - 1]['sales'] ?? 0;
            $previousSales = $historicalSales[count($historicalSales) - 2]['sales'] ?? 0;
            if ($recentSales > $previousSales * 1.2) {
                $trend = 'increasing';
            } elseif ($recentSales < $previousSales * 0.8) {
                $trend = 'decreasing';
            }
        }

        return [
            'velocity' => $velocity,
            'trend' => $trend,
            'avg_daily_sales' => $avgDailySales,
        ];
    }

    private function saveDynamicPricing(
        int $productId,
        int $tenantId,
        ?int $businessGroupId,
        float $basePrice,
        float $dynamicPrice,
        float $discountPercent,
        float $trendScore,
        bool $isFlashSale,
        ?Carbon $flashSaleEndTime,
        string $correlationId
    ): void {
        $this->db->table('fashion_dynamic_pricing')->updateOrInsert(
            [
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
            ],
            [
                'base_price' => $basePrice,
                'dynamic_price' => $dynamicPrice,
                'discount_percent' => $discountPercent,
                'trend_score' => $trendScore,
                'is_flash_sale' => $isFlashSale,
                'flash_sale_end_time' => $flashSaleEndTime,
                'correlation_id' => $correlationId,
                'updated_at' => Carbon::now(),
            ]
        );
    }

    private function findAvailableStylist(int $tenantId, string $correlationId): ?int
    {
        return $this->db->table('fashion_stylists')
            ->where('tenant_id', $tenantId)
            ->where('is_available', true)
            ->where('is_online', true)
            ->orderBy('rating', 'desc')
            ->value('id');
    }

    private function generateWebRTCToken(string $sessionId, int $userId, int $stylistId, string $correlationId): string
    {
        $payload = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'stylist_id' => $stylistId,
            'exp' => Carbon::now()->addHours(2)->timestamp,
        ];

        return base64_encode(json_encode($payload));
    }

    private function generateARModel(int $designId, int $productId, array $styleProfile, string $correlationId): string
    {
        $modelPath = "fashion/ar-models/{$designId}_{$productId}.glb";
        
        if (Storage::disk('s3')->exists($modelPath)) {
            return Storage::disk('s3')->url($modelPath);
        }

        $this->bus->dispatch(new Generate3DModelJob(
            designId: $designId,
            productId: $productId,
            styleProfile: $styleProfile,
            correlationId: $correlationId
        ));

        return url("/fashion/ar-model/pending/{$designId}/{$productId}");
    }

    private function buildModelViewerConfig(array $product, array $styleProfile): array
    {
        return [
            'src' => $product['three_d_model_url'] ?? '',
            'alt' => $product['name'] ?? '',
            'auto-rotate' => true,
            'camera-controls' => true,
            'shadow-intensity' => 0.5,
            'exposure' => 1.0,
            'background-color' => '#f5f5f5',
            'style_profile' => [
                'color_type' => $styleProfile['color_type'] ?? 'autumn',
                'preferred_palette' => $styleProfile['preferred_palette'] ?? [],
            ],
        ];
    }

    private function awardLoyaltyPoints(int $userId, int $points, string $reason, string $correlationId): void
    {
        $tenantId = $this->getTenantId();

        $currentPoints = $this->db->table('fashion_loyalty_points')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->value('total_points') ?? 0;

        $this->db->table('fashion_loyalty_points')->updateOrInsert(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            [
                'total_points' => $currentPoints + $points,
                'last_earned_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        $this->db->table('fashion_loyalty_transactions')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'points_earned' => $points,
            'reward_type' => $reason,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
        ]);
    }

    private function generateNFTAvatar(int $userId, int $points, string $correlationId): array
    {
        $avatarStyles = ['classic', 'modern', 'avant_garde', 'minimalist', 'bohemian'];
        $selectedStyle = $avatarStyles[$points % count($avatarStyles)];

        $avatarUrl = "https://api.catvrf.io/nft-avatars/{$selectedStyle}_{$userId}.png";
        $metadata = [
            'style' => $selectedStyle,
            'points_threshold' => self::LOYALTY_NFT_UNLOCK_THRESHOLD,
            'generated_at' => Carbon::now()->toIso8601String(),
            'rarity' => $points > 10000 ? 'legendary' : ($points > 5000 ? 'epic' : 'rare'),
        ];

        return [
            'unlocked' => true,
            'avatar_url' => $avatarUrl,
            'metadata' => $metadata,
        ];
    }

    private function calculateLoyaltyTier(int $points): string
    {
        return match (true) {
            $points >= 10000 => 'platinum',
            $points >= 5000 => 'gold',
            $points >= 2000 => 'silver',
            $points >= 500 => 'bronze',
            default => 'standard',
        };
    }

    private function getUserWalletId(int $userId, int $tenantId): int
    {
        return $this->db->table('wallets')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->value('id') ?? 0;
    }

    private function getBrandWalletId(int $orderId, int $tenantId): ?int
    {
        $brandId = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->value('brand_id');

        if ($brandId === null) {
            return null;
        }

        return $this->db->table('wallets')
            ->where('brand_id', $brandId)
            ->where('tenant_id', $tenantId)
            ->value('id');
    }

    private function getMarketplaceWalletId(int $tenantId): int
    {
        return $this->db->table('wallets')
            ->where('tenant_id', $tenantId)
            ->where('type', 'marketplace')
            ->value('id') ?? 0;
    }

    private function isNewUser(int $userId): bool
    {
        $user = $this->db->table('users')->where('id', $userId)->first();
        if ($user === null) {
            return true;
        }

        $daysSinceCreation = Carbon::parse($user->created_at)->diffInDays(Carbon::now());
        $orderCount = $this->db->table('orders')->where('user_id', $userId)->count();

        return $daysSinceCreation <= 7 && $orderCount === 0;
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }

    private function getBusinessGroupId(int $userId): ?int
    {
        return $this->db->table('business_group_users')
            ->where('user_id', $userId)
            ->value('business_group_id');
    }
}
