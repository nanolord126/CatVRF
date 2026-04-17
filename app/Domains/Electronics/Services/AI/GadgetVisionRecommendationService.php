<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services\AI;

use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisRequestDto;
use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisResponseDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;

final readonly class GadgetVisionRecommendationService
{
    public function __construct(
        private FraudControlService $fraud,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private Cache $cache,
        private DatabaseManager $db,
        private OpenAIClient $openai,
        private LoggerInterface $logger,
    ) {
    }

    public function analyzePhotoAndRecommend(GadgetVisionAnalysisRequestDto $dto): GadgetVisionAnalysisResponseDto
    {
        $correlationId = $dto->correlationId;

        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'electronics_vision_analysis',
            amount: $dto->budgetMaxKopecks / 100,
            correlationId: $correlationId
        );

        $cacheKey = sprintf(
            'electronics_vision_analysis:%d:%s',
            $dto->userId,
            md5(serialize($dto->toArray()))
        );

        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->logger->info('Electronics vision analysis cache hit', [
                'user_id' => $dto->userId,
                'correlation_id' => $correlationId,
            ]);

            return GadgetVisionAnalysisResponseDto::fromArray($cachedResult);
        }

        return $this->db->transaction(function () use ($dto, $correlationId, $cacheKey) {
            $visionAnalysis = $this->performVisionAnalysis($dto->image, $dto->analysisType);

            $userBehaviorType = $this->behaviorAnalyzer->classifyUser($dto->userId);

            $tasteProfile = $this->tasteAnalyzer->getProfile($dto->userId);

            $fullProfile = array_merge(
                $visionAnalysis,
                [
                    'preferred_brands' => $dto->preferredBrands,
                    'use_cases' => $dto->useCases,
                    'budget_max' => $dto->budgetMaxKopecks,
                    'additional_specs' => $dto->additionalSpecs,
                ],
                (array) ($tasteProfile->preferences ?? [])
            );

            $recommendations = $this->getPersonalizedRecommendations(
                $fullProfile,
                $dto->userId,
                $userBehaviorType
            );

            $arPreviewUrls = $this->generateARPreviews($recommendations);

            $pricingInfo = $this->calculateDynamicPricing($recommendations, $dto->userId);

            $videoCallData = $this->prepareVideoCallIntegration($dto->userId, $recommendations);

            $flashSaleOffer = $this->checkFlashSaleEligibility($dto->userId, $recommendations);

            $response = new GadgetVisionAnalysisResponseDto(
                success: true,
                correlationId: $correlationId,
                visionAnalysis: $visionAnalysis,
                recommendedProducts: $recommendations,
                arPreviewUrls: $arPreviewUrls,
                pricingInfo: $pricingInfo,
                videoCallAvailable: $videoCallData['available'],
                videoCallToken: $videoCallData['token'],
                flashSaleOffer: $flashSaleOffer,
            );

            $this->saveAnalysisResult($dto->userId, $response->toArray(), $correlationId);

            $this->cache->put($cacheKey, $response->toArray(), now()->addHours(1));

            Log::channel('audit')->info('Electronics vision analysis completed', [
                'user_id' => $dto->userId,
                'correlation_id' => $correlationId,
                'analysis_type' => $dto->analysisType,
                'recommendations_count' => count($recommendations),
                'behavior_type' => $userBehaviorType,
            ]);

            return $response;
        });
    }

    private function performVisionAnalysis(object $imageFile, string $analysisType): array
    {
        $imagePath = $imageFile->getRealPath();
        $imageBase64 = base64_encode(file_get_contents($imagePath));

        $prompt = match ($analysisType) {
            'gadget_recommendation' => 'Analyze this image and identify: 1) What device/gadget is shown or what use case is depicted? 2) Key features visible. 3) Estimated price range. 4) Compatible accessories. 5) Technical requirements. Respond in JSON format with keys: detected_device, features, estimated_price_range, compatible_accessories, technical_requirements, confidence_score.',
            'room_analysis' => 'Analyze this room image for electronics placement: 1) Available space dimensions. 2) Power outlet locations. 3) Lighting conditions. 4) Existing electronics. 5) Recommended gadget categories. Respond in JSON format with keys: space_analysis, power_outlets, lighting, existing_electronics, recommended_categories.',
            default => 'Analyze this image for electronics recommendations. Identify visible devices, use cases, and context. Respond in JSON format with keys: analysis, detected_items, context, recommendations.',
        };

        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $imageBase64,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1000,
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return [
                'raw_response' => $parsed,
                'analysis_type' => $analysisType,
                'model_used' => 'gpt-4o',
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Vision API analysis failed', [
                'error' => $e->getMessage(),
                'analysis_type' => $analysisType,
            ]);

            return [
                'error' => 'Vision analysis failed',
                'fallback_analysis' => $this->performFallbackAnalysis($imageFile),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    private function performFallbackAnalysis(object $imageFile): array
    {
        $imageSize = getimagesize($imageFile->getRealPath());
        $fileSize = filesize($imageFile->getRealPath());

        return [
            'detected_device' => 'unknown',
            'features' => [],
            'estimated_price_range' => '0-100000',
            'compatible_accessories' => [],
            'technical_requirements' => [],
            'confidence_score' => 0.3,
            'metadata' => [
                'image_width' => $imageSize[0] ?? 0,
                'image_height' => $imageSize[1] ?? 0,
                'file_size' => $fileSize,
            ],
        ];
    }

    private function getPersonalizedRecommendations(array $profile, int $userId, string $behaviorType): array
    {
        $query = ElectronicsProduct::query()
            ->where('availability_status', 'in_stock')
            ->where('is_active', true);

        if (!empty($profile['preferred_brands'])) {
            $query->whereIn('brand', $profile['preferred_brands']);
        }

        if (!empty($profile['budget_max'])) {
            $query->where('price_kopecks', '<=', $profile['budget_max']);
        }

        if (!empty($profile['detected_device'])) {
            $query->where(function ($q) use ($profile) {
                $q->where('name', 'like', '%' . $profile['detected_device'] . '%')
                  ->orWhere('category', 'like', '%' . $profile['detected_device'] . '%');
            });
        }

        $products = $query->limit(10)->get();

        $mlRecommendations = $this->recommendation->getForVertical(
            'Electronics',
            $profile,
            $userId
        );

        $merged = $products->concat($mlRecommendations)->unique('id')->take(8);

        $result = [];
        foreach ($merged as $product) {
            $result[] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category,
                'price_kopecks' => $product->price_kopecks,
                'original_price_kopecks' => $product->original_price_kopecks,
                'specs' => $product->specs,
                'images' => $product->images,
                'rating' => $product->rating,
                'reviews_count' => $product->reviews_count,
                'stock_quantity' => $product->stock_quantity,
                'compatibility_score' => $this->calculateCompatibilityScore($profile, $product),
                'personalization_score' => $behaviorType === 'returning' ? 0.95 : 0.75,
            ];
        }

        return $result;
    }

    private function calculateCompatibilityScore(array $profile, ElectronicsProduct $product): float
    {
        $score = 0.5;

        if (!empty($profile['preferred_brands']) && in_array($product->brand, $profile['preferred_brands'])) {
            $score += 0.2;
        }

        if (!empty($profile['budget_max']) && $product->price_kopecks <= $profile['budget_max']) {
            $score += 0.15;
        }

        if (!empty($profile['use_cases'])) {
            $productSpecs = json_encode($product->specs);
            foreach ($profile['use_cases'] as $useCase) {
                if (stripos($productSpecs, $useCase) !== false) {
                    $score += 0.05;
                }
            }
        }

        return min($score, 1.0);
    }

    private function generateARPreviews(array $recommendations): array
    {
        $arUrls = [];

        foreach ($recommendations as $product) {
            $productId = $product['id'];
            $arUrls[$productId] = [
                'model_viewer_url' => url("/api/v1/electronics/products/{$productId}/ar-model"),
                'qr_code_url' => url("/api/v1/electronics/products/{$productId}/ar-qr"),
                'ar_type' => 'webxr',
                'supported_formats' => ['glb', 'gltf', 'usdz'],
                'preview_thumbnail' => $product['images'][0] ?? null,
            ];
        }

        return $arUrls;
    }

    private function calculateDynamicPricing(array $recommendations, int $userId): array
    {
        $pricingInfo = [
            'base_total_kopecks' => array_sum(array_column($recommendations, 'price_kopecks')),
            'discount_percentage' => 0,
            'final_total_kopecks' => 0,
            'savings_kopecks' => 0,
            'dynamic_factors' => [],
        ];

        $userLTV = $this->getUserLTV($userId);
        $behaviorType = $this->behaviorAnalyzer->classifyUser($userId);

        if ($behaviorType === 'returning' && $userLTV > 5000000) {
            $pricingInfo['discount_percentage'] = 15;
            $pricingInfo['dynamic_factors'][] = 'loyalty_discount';
        } elseif ($behaviorType === 'new') {
            $pricingInfo['discount_percentage'] = 5;
            $pricingInfo['dynamic_factors'][] = 'first_purchase_bonus';
        }

        $currentHour = now()->hour;
        if ($currentHour >= 18 && $currentHour <= 23) {
            $pricingInfo['discount_percentage'] += 3;
            $pricingInfo['dynamic_factors'][] = 'evening_hours';
        }

        $pricingInfo['final_total_kopecks'] = (int) (
            $pricingInfo['base_total_kopecks'] * (1 - $pricingInfo['discount_percentage'] / 100)
        );
        $pricingInfo['savings_kopecks'] = $pricingInfo['base_total_kopecks'] - $pricingInfo['final_total_kopecks'];

        return $pricingInfo;
    }

    private function getUserLTV(int $userId): int
    {
        return $this->cache->remember("user_ltv:{$userId}", now()->addHours(6), function () use ($userId) {
            return (int) DB::table('orders')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('total_kopecks');
        });
    }

    private function prepareVideoCallIntegration(int $userId, array $recommendations): array
    {
        $hasExpensiveItems = collect($recommendations)->contains(function ($product) {
            return $product['price_kopecks'] > 10000000;
        });

        if (!$hasExpensiveItems) {
            return ['available' => false, 'token' => null];
        }

        $videoCallToken = hash('sha256', $userId . now()->timestamp . Str::random(32));

        $this->cache->put(
            "video_call_token:{$videoCallToken}",
            ['user_id' => $userId, 'expires_at' => now()->addMinutes(30)],
            now()->addMinutes(30)
        );

        return [
            'available' => true,
            'token' => $videoCallToken,
            'expires_in_minutes' => 30,
            'expert_categories' => array_unique(array_column($recommendations, 'category')),
        ];
    }

    private function checkFlashSaleEligibility(int $userId, array $recommendations): ?string
    {
        $flashSaleActive = $this->cache->get('flash_sale:electronics:active', false);

        if (!$flashSaleActive) {
            return null;
        }

        $flashSaleData = $this->cache->get('flash_sale:electronics:data', []);

        $eligibleProducts = array_filter($recommendations, function ($product) use ($flashSaleData) {
            return in_array($product['id'], $flashSaleData['product_ids'] ?? []);
        });

        if (empty($eligibleProducts)) {
            return null;
        }

        return json_encode([
            'sale_id' => $flashSaleData['sale_id'] ?? null,
            'discount_percentage' => $flashSaleData['discount'] ?? 0,
            'eligible_product_ids' => array_column($eligibleProducts, 'id'),
            'ends_at' => $flashSaleData['ends_at'] ?? null,
        ]);
    }

    private function saveAnalysisResult(int $userId, array $result, string $correlationId): void
    {
        $this->db->table('user_ai_designs')->insert([
            'user_id' => $userId,
            'vertical' => 'electronics',
            'design_data' => json_encode($result),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
