<?php

declare(strict_types=1);

namespace App\Services\Personalization;

use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Modules\Contraindications\Application\Services\ContraindicationService;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class PersonalizationService
{
    use WithAuditLogging;

    private const CACHE_TTL_MINUTES = 30;
    private const CANDIDATE_MULTIPLIER = 3;

    public function __construct(
        private readonly FeatureStore $featureStore,
        private readonly CandidateGenerator $candidateGenerator,
        private readonly Reranker $reranker,
        private readonly ContraindicationService $contraindicationService,
        private readonly RecommendationExplainer $explainer,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get personalized recommendations for a user
     *
     * @return Collection<int, Recommendation>
     */
    public function recommendForClient(
        \App\Models\User $user,
        string $vertical,
        int $limit = 8
    ): Collection {
        $cacheKey = "personalization:user:{$user->id}:{$vertical}:{$limit}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_MINUTES, function () use ($user, $vertical, $limit) {
            try {
                // Extract user features
                $userFeatures = $this->featureStore->extractForUser($user, $vertical);

                // Generate candidate recommendations (broad set)
                $candidates = $this->candidateGenerator->getTopK(
                    $userFeatures,
                    $vertical,
                    $limit * self::CANDIDATE_MULTIPLIER
                );

                if ($candidates->isEmpty()) {
                    $this->logger->warning('No candidates generated', [
                        'user_id' => $user->id,
                        'vertical' => $vertical,
                    ]);
                    return collect();
                }

                // Rerank candidates with XGBoost + context
                $ranked = $this->reranker->rank(
                    $candidates,
                    $userFeatures,
                    $vertical
                );

                // Filter by allergies/contraindications
                $safe = $this->contraindicationService->filterSafeRecommendations(
                    $ranked,
                    $user->id,
                    null,
                    Scope::from($vertical)
                );

                // Add explanations to top results
                $withExplanations = $safe->take($limit)->map(function ($item) use ($user, $vertical) {
                    $recommendation = new Recommendation(
                        id: $item->id,
                        type: get_class($item),
                        name: $item->name ?? $item->title ?? 'Unknown',
                        score: $item->recommendation_score ?? 0.0,
                        confidence: $item->confidence ?? 0.5,
                        vertical: $vertical,
                        explanation: $this->explainer->explain($item, $user),
                    );

                    return $recommendation;
                });

                // Log recommendation for feedback loop
                $this->logRecommendations($user->id, $withExplanations, $vertical);

                return $withExplanations;
            } catch (\Exception $e) {
                $this->logger->error('Personalization failed', [
                    'user_id' => $user->id,
                    'vertical' => $vertical,
                    'error' => $e->getMessage(),
                ]);

                // Fallback to popular items
                return $this->getFallbackRecommendations($vertical, $limit);
            }
        });
    }

    /**
     * Get personalized recommendations for a pet
     *
     * @return Collection<int, Recommendation>
     */
    public function recommendForPet(
        object $pet,
        string $vertical,
        int $limit = 6
    ): Collection {
        $cacheKey = "personalization:pet:{$pet->id}:{$vertical}:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL_MINUTES, function () use ($pet, $vertical, $limit) {
            try {
                // Extract pet features
                $petFeatures = $this->featureStore->extractForPet($pet, $vertical);

                // Generate candidates
                $candidates = $this->candidateGenerator->getTopK(
                    $petFeatures,
                    $vertical,
                    $limit * self::CANDIDATE_MULTIPLIER
                );

                if ($candidates->isEmpty()) {
                    return collect();
                }

                // Rerank with pet-specific context
                $ranked = $this->reranker->rank($candidates, $petFeatures, $vertical);

                // Filter by allergies/contraindications
                $safe = $this->contraindicationService->filterSafeRecommendations(
                    $ranked,
                    $pet->owner->id ?? null,
                    $pet->id,
                    Scope::from($vertical)
                );

                // Add explanations
                $withExplanations = $safe->take($limit)->map(function ($item) use ($pet, $vertical) {
                    return new Recommendation(
                        id: $item->id,
                        type: get_class($item),
                        name: $item->name ?? $item->title ?? 'Unknown',
                        score: $item->recommendation_score ?? 0.0,
                        confidence: $item->confidence ?? 0.5,
                        vertical: $vertical,
                        explanation: $this->explainer->explain($item, $pet),
                    );
                });

                $this->logRecommendations($pet->id, $withExplanations, $vertical, 'pet');

                return $withExplanations;
            } catch (\Exception $e) {
                Log::error('Pet personalization failed', [
                    'pet_id' => $pet->id,
                    'vertical' => $vertical,
                    'error' => $e->getMessage(),
                ]);

                return $this->getFallbackRecommendations($vertical, $limit);
            }
        });
    }

    /**
     * Get similar items based on a reference item
     *
     * @return Collection<int, Recommendation>
     */
    public function getSimilarItems(
        object $referenceItem,
        string $vertical,
        int $limit = 5
    ): Collection {
        try {
            $itemFeatures = $this->featureStore->extractForItem($referenceItem);

            // Find similar items using item-item similarity
            $similar = $this->candidateGenerator->getSimilarItems(
                $itemFeatures,
                $vertical,
                $limit + 1 // +1 to exclude the reference item
            );

            // Remove reference item
            $filtered = $similar->filter(fn ($item) => $item->id !== $referenceItem->id);

            return $filtered->take($limit)->map(function ($item) use ($vertical) {
                return new Recommendation(
                    id: $item->id,
                    type: get_class($item),
                    name: $item->name ?? $item->title ?? 'Unknown',
                    score: $item->similarity_score ?? 0.0,
                    confidence: $item->confidence ?? 0.5,
                    vertical: $vertical,
                    explanation: ['reason' => 'similar_to_reference'],
                );
            });
        } catch (\Exception $e) {
            Log::error('Similar items failed', [
                'item_id' => $referenceItem->id,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Explain why a recommendation was made
     */
    public function explainRecommendation(
        Recommendation $recommendation,
        object $subject
    ): array {
        return $this->explainer->explainDetailed($recommendation, $subject);
    }

    /**
     * Record user feedback on recommendations
     */
    public function recordFeedback(
        int $userId,
        int $itemId,
        string $action, // 'click', 'book', 'purchase', 'dismiss'
        ?array $context = null
    ): void {
        $feedback = [
            'user_id' => $userId,
            'item_id' => $itemId,
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        // Store in feedback log for model retraining
        dispatch(new \App\Jobs\RecordRecommendationFeedback($feedback));

        // Update real-time feature store
        $this->featureStore->updateUserFeatures($userId, $action);
    }

    /**
     * Get trending items in a vertical
     *
     * @return Collection<int, Recommendation>
     */
    public function getTrending(string $vertical, int $limit = 10): Collection
    {
        $cacheKey = "personalization:trending:{$vertical}:{$limit}";

        return Cache::remember($cacheKey, 60, function () use ($vertical, $limit) {
            return $this->featureStore->getTrendingItems($vertical, $limit)
                ->map(function ($item) use ($vertical) {
                    return new Recommendation(
                        id: $item->id,
                        type: get_class($item),
                        name: $item->name ?? $item->title ?? 'Unknown',
                        score: $item->trending_score ?? 0.0,
                        confidence: 0.8,
                        vertical: $vertical,
                        explanation: ['reason' => 'trending'],
                    );
                });
        });
    }

    /**
     * Get personalized home page recommendations
     */
    public function getHomePageRecommendations(
        \App\Models\User $user,
        array $verticals = ['beauty', 'grooming', 'food', 'fitness']
    ): array {
        $recommendations = [];

        foreach ($verticals as $vertical) {
            $recommendations[$vertical] = $this->recommendForClient($user, $vertical, 4);
        }

        return $recommendations;
    }

    private function logRecommendations(
        int $subjectId,
        Collection $recommendations,
        string $vertical,
        string $subjectType = 'user'
    ): void {
        $logData = [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'vertical' => $vertical,
            'count' => $recommendations->count(),
            'items' => $recommendations->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'score' => $r->score,
            ])->toArray(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info('Recommendations generated', $logData);
    }

    private function getFallbackRecommendations(string $vertical, int $limit): Collection
    {
        // Return popular items as fallback
        return $this->featureStore->getPopularItems($vertical, $limit)
            ->map(function ($item) use ($vertical) {
                return new Recommendation(
                    id: $item->id,
                    type: get_class($item),
                    name: $item->name ?? $item->title ?? 'Unknown',
                    score: 0.5,
                    confidence: 0.3,
                    vertical: $vertical,
                    explanation: ['reason' => 'popular_fallback'],
                );
            });
    }
}
