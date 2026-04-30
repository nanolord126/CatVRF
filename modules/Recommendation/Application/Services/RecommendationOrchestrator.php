<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Application\DTOs\RecommendationRequestDTO;
use Modules\Recommendation\Application\DTOs\RecommendationResponseDTO;
use Modules\Recommendation\Domain\Entities\RecommendationItem;
use Modules\Recommendation\Domain\Entities\SellerRecommendation;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\Events\RecommendationServed;
use Modules\Recommendation\Domain\Interfaces\FairnessEvaluatorInterface;
use Modules\Recommendation\Domain\Interfaces\MLInferenceInterface;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\ValueObjects\FairnessConfig;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;
use Modules\Recommendation\Domain\ValueObjects\RecommendationScore;

final readonly class RecommendationOrchestrator
{
    use WithAuditLogging;

    public function __construct(
        private RecommendationRepositoryInterface $repository,
        private MLInferenceInterface $mlInference,
        private FairnessEvaluatorInterface $fairnessEvaluator,
        private RuleBasedFallbackService $fallbackService,
        private AuditService $audit,
    ) {}

    public function getRecommendations(RecommendationRequestDTO $request): RecommendationResponseDTO
    {
        $startTime = microtime(true);
        $correlationId = $request->correlationId ?? $this->generateCorrelationId();

        $this->logAction('recommendation_request', 'Recommendation', null, [
            'user_id' => $request->userId,
            'scenario' => $request->scenario->value,
            'vertical' => $request->vertical,
            'correlation_id' => $correlationId,
        ], $request->userId, $request->tenantId);

        $cacheKey = $this->getCacheKey($request, $correlationId);
        $cached = Cache::tags(['recommendations', 'user:' . $request->userId])
            ->get($cacheKey);

        if ($cached !== null) {
            $response = RecommendationResponseDTO::fromArray($cached);
            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->logAction('recommendation_cache_hit', 'Recommendation', null, [
                'user_id' => $request->userId,
                'scenario' => $request->scenario->value,
                'correlation_id' => $correlationId,
            ], $request->userId, $request->tenantId);

            return new RecommendationResponseDTO(
                tenantId: $response->tenantId,
                userId: $response->userId,
                scenario: $response->scenario,
                items: $response->items,
                correlationId: $response->correlationId,
                latencyMs: $latencyMs,
                modelVersion: $response->modelVersion,
                servedFromCache: true,
            );
        }

        try {
            $items = $this->generateRecommendations($request, $correlationId);

            $response = new RecommendationResponseDTO(
                tenantId: $request->tenantId,
                userId: $request->userId,
                scenario: $request->scenario->value,
                items: $items,
                correlationId: $correlationId,
                latencyMs: (microtime(true) - $startTime) * 1000,
                modelVersion: $this->mlInference->getModelVersion(),
                servedFromCache: false,
            );

            Cache::tags(['recommendations', 'user:' . $request->userId])
                ->put($cacheKey, $response->toArray(), $request->scenario->cacheTtl());

            Event::dispatch(new RecommendationServed(
                tenantId: $request->tenantId,
                userId: $request->userId,
                scenario: $request->scenario->value,
                itemCount: count($items),
                source: 'orchestrator',
                correlationId: $correlationId,
                modelVersion: $this->mlInference->getModelVersion(),
                latencyMs: $response->latencyMs,
            ));

            $this->logAction('recommendation_served', 'Recommendation', null, [
                'user_id' => $request->userId,
                'scenario' => $request->scenario->value,
                'item_count' => count($items),
                'correlation_id' => $correlationId,
            ], $request->userId, $request->tenantId);

            return $response;
        } catch (\Throwable $e) {
            Log::error('Recommendation generation failed, using fallback', [
                'error' => $e->getMessage(),
                'user_id' => $request->userId,
                'scenario' => $request->scenario->value,
                'correlation_id' => $correlationId,
            ]);

            $fallbackItems = $this->fallbackService->getFallbackRecommendations($request, $correlationId);

            return new RecommendationResponseDTO(
                tenantId: $request->tenantId,
                userId: $request->userId,
                scenario: $request->scenario->value,
                items: $fallbackItems,
                correlationId: $correlationId,
                latencyMs: (microtime(true) - $startTime) * 1000,
                modelVersion: 'fallback',
                servedFromCache: false,
                fallbackReason: $e->getMessage(),
            );
        }
    }

    private function generateRecommendations(RecommendationRequestDTO $request, string $correlationId): array
    {
        $fairnessConfig = FairnessConfig::default();
        $candidates = [];

        switch ($request->scenario) {
            case RecommendationScenario::HOME_FEED:
                $candidates = $this->generateHomeFeed($request, $correlationId);
                break;
            case RecommendationScenario::PRODUCT_DETAIL:
                $candidates = $this->generateProductDetail($request, $correlationId);
                break;
            case RecommendationScenario::SEARCH:
                $candidates = $this->generateSearchResults($request, $correlationId);
                break;
            case RecommendationScenario::SELLER_PAGE:
                $candidates = $this->generateSellerPage($request, $correlationId);
                break;
            case RecommendationScenario::CART:
                $candidates = $this->generateCartRecommendations($request, $correlationId);
                break;
            default:
                $candidates = $this->generateGeneric($request, $correlationId);
        }

        $fairnessEvaluated = $this->fairnessEvaluator->evaluateFeedFairness(
            $request->tenantId,
            $candidates,
            $fairnessConfig
        );

        if ($fairnessEvaluated['needs_rebalance']) {
            $candidates = $this->fairnessEvaluator->rebalanceForFairness(
                $request->tenantId,
                $candidates,
                $fairnessConfig
            );
        }

        $finalItems = array_slice($candidates, 0, $request->limit);

        foreach ($finalItems as $item) {
            $recommendationItem = RecommendationItem::fromInference(
                tenantId: $request->tenantId,
                userId: $request->userId,
                itemId: $item['item_id'],
                sellerId: $item['seller_id'],
                vertical: $item['vertical'] ?? 'unknown',
                scoreValue: $item['score'],
                confidence: $item['confidence'] ?? 0.8,
                source: RecommendationSource::tryFrom($item['source']) ?? RecommendationSource::TWO_TOWER,
                scenario: $request->scenario,
                correlationId: $correlationId,
                position: $item['position'] ?? 0,
                modelVersion: $this->mlInference->getModelVersion(),
            );

            $this->repository->saveLog($recommendationItem);
        }

        return $finalItems;
    }

    private function generateHomeFeed(RecommendationRequestDTO $request, string $correlationId): array
    {
        $userFeatures = $this->repository->getUserFeatures($request->tenantId, $request->userId);
        $contextFeatures = $this->repository->getContextFeatures(
            $request->tenantId,
            $request->scenario->value,
            $request->context
        );

        $mlCandidates = $this->mlInference->getCandidates(
            $request->tenantId,
            $userFeatures->toArray(),
            limit: 100,
            vertical: $request->vertical
        );

        $trending = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: 20,
            vertical: $request->vertical
        );

        $combined = [];
        $position = 0;

        foreach ($mlCandidates as $idx => $candidate) {
            $combined[] = [
                'item_id' => $candidate['item_id'],
                'seller_id' => $candidate['seller_id'],
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'] ?? 0.9,
                'source' => RecommendationSource::TWO_TOWER->value,
                'position' => $position++,
                'vertical' => $candidate['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Personalized based on your activity',
            ];
        }

        foreach ($trending as $trend) {
            $combined[] = [
                'item_id' => $trend['item_id'],
                'seller_id' => $trend['seller_id'],
                'score' => $trend['score'] * 0.7,
                'confidence' => 0.7,
                'source' => RecommendationSource::TRENDING->value,
                'position' => $position++,
                'vertical' => $trend['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Trending now',
            ];
        }

        $ranked = $this->mlInference->rankItems(
            $request->tenantId,
            $userFeatures->toArray(),
            $combined,
            $contextFeatures->toArray()
        );

        return $ranked;
    }

    private function generateProductDetail(RecommendationRequestDTO $request, string $correlationId): array
    {
        if (!$request->itemId) {
            return [];
        }

        $similar = $this->repository->getSimilarItems($request->tenantId, $request->itemId, 12);
        $frequentlyBought = $this->repository->getFrequentlyBoughtTogether($request->tenantId, $request->itemId, 8);

        $combined = [];
        $position = 0;

        foreach ($similar as $item) {
            $combined[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $item['score'],
                'confidence' => 0.85,
                'source' => RecommendationSource::SIMILAR_ITEMS->value,
                'position' => $position++,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Similar to this item',
            ];
        }

        foreach ($frequentlyBought as $item) {
            $combined[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $item['score'] * 0.9,
                'confidence' => 0.8,
                'source' => RecommendationSource::FREQUENTLY_BOUGHT->value,
                'position' => $position++,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Frequently bought together',
            ];
        }

        return $combined;
    }

    private function generateSearchResults(RecommendationRequestDTO $request, string $correlationId): array
    {
        $userFeatures = $this->repository->getUserFeatures($request->tenantId, $request->userId);

        $candidates = $this->mlInference->getCandidates(
            $request->tenantId,
            $userFeatures->toArray(),
            limit: $request->limit * 2,
            vertical: $request->vertical
        );

        $ranked = [];
        foreach ($candidates as $idx => $candidate) {
            $ranked[] = [
                'item_id' => $candidate['item_id'],
                'seller_id' => $candidate['seller_id'],
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'] ?? 0.85,
                'source' => RecommendationSource::TWO_TOWER->value,
                'position' => $idx,
                'vertical' => $candidate['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Personalized search result',
            ];
        }

        return $ranked;
    }

    private function generateSellerPage(RecommendationRequestDTO $request, string $correlationId): array
    {
        if (!$request->sellerId) {
            return [];
        }

        $sellerFeatures = $this->repository->getSellerFeatures($request->tenantId, $request->sellerId);
        $userFeatures = $this->repository->getUserFeatures($request->tenantId, $request->userId);

        $candidates = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: $request->limit,
            vertical: $request->vertical
        );

        $filtered = array_filter($candidates, fn($item) => $item['seller_id'] === $request->sellerId);

        $ranked = [];
        foreach ($filtered as $idx => $item) {
            $ranked[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $item['score'],
                'confidence' => 0.9,
                'source' => RecommendationSource::SELLER_PROMO->value,
                'position' => $idx,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Popular from this seller',
            ];
        }

        return $ranked;
    }

    private function generateCartRecommendations(RecommendationRequestDTO $request, string $correlationId): array
    {
        $userFeatures = $this->repository->getUserFeatures($request->tenantId, $request->userId);

        $candidates = $this->mlInference->getCandidates(
            $request->tenantId,
            $userFeatures->toArray(),
            limit: 20,
            vertical: $request->vertical
        );

        $ranked = [];
        foreach ($candidates as $idx => $candidate) {
            $ranked[] = [
                'item_id' => $candidate['item_id'],
                'seller_id' => $candidate['seller_id'],
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'] ?? 0.9,
                'source' => RecommendationSource::TWO_TOWER->value,
                'position' => $idx,
                'vertical' => $candidate['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'You might also like',
            ];
        }

        return $ranked;
    }

    private function generateGeneric(RecommendationRequestDTO $request, string $correlationId): array
    {
        return $this->fallbackService->getFallbackRecommendations($request, $correlationId);
    }

    private function getCacheKey(RecommendationRequestDTO $request, string $correlationId): string
    {
        return sprintf(
            'recommendation:%d:%d:%s:%s:%s',
            $request->tenantId,
            $request->userId,
            $request->scenario->value,
            $request->vertical ?? 'all',
            md5(serialize($request->context) . $correlationId)
        );
    }

    private function generateCorrelationId(): string
    {
        return uniqid('rec_', true);
    }

    public function invalidateUserCache(int $userId): void
    {
        Cache::tags(['recommendations', 'user:' . $userId])->flush();
    }

    public function invalidateScenarioCache(int $tenantId, string $scenario): void
    {
        Cache::tags(['recommendations', 'tenant:' . $tenantId . ':' . $scenario])->flush();
    }
}
