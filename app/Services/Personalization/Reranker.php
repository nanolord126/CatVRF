<?php

declare(strict_types=1);

namespace App\Services\Personalization;

use Illuminate\Support\Collection;

final class Reranker
{
    private const MODEL_ENDPOINT = 'http://localhost:8002/rank';

    public function __construct(
        private readonly FeatureStore $featureStore,
    ) {
    }

    /**
     * Rerank candidates using XGBoost model
     *
     * @return Collection<int, mixed>
     */
    public function rank(Collection $candidates, array $userFeatures, string $vertical): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        // Try ML model first
        try {
            $ranked = $this->mlRerank($candidates, $userFeatures, $vertical);
            if ($ranked->isNotEmpty()) {
                return $ranked;
            }
        } catch (\Exception $e) {
            // Fallback to rule-based reranking
        }

        return $this->ruleBasedRerank($candidates, $userFeatures, $vertical);
    }

    /**
     * ML-based reranking using XGBoost
     *
     * @return Collection<int, mixed>
     */
    private function mlRerank(Collection $candidates, array $userFeatures, string $vertical): Collection
    {
        $features = [];

        foreach ($candidates as $candidate) {
            $itemFeatures = $this->featureStore->extractForItem($candidate);

            $features[] = [
                'user_features' => $userFeatures,
                'item_features' => $itemFeatures,
                'context_features' => [
                    'vertical' => $vertical,
                    'time_of_day' => now()->hour,
                    'day_of_week' => now()->dayOfWeek,
                    'season' => $this->getSeason(),
                ],
            ];
        }

        $response = \Illuminate\Support\Facades\Http::timeout(2)->post(self::MODEL_ENDPOINT, [
            'features' => $features,
        ]);

        if (!$response->successful()) {
            throw new \Exception('ML reranking failed');
        }

        $scores = $response->json('scores', []);

        // Merge scores with candidates
        foreach ($candidates as $index => $candidate) {
            $candidate->rerank_score = $scores[$index] ?? $candidate->similarity_score ?? 0;
            $candidate->recommendation_score = $candidate->rerank_score;
        }

        return $candidates->sortByDesc('rerank_score')->values();
    }

    /**
     * Rule-based reranking (fallback)
     *
     * @return Collection<int, mixed>
     */
    private function ruleBasedRerank(Collection $candidates, array $userFeatures, string $vertical): Collection
    {
        foreach ($candidates as $candidate) {
            $score = $candidate->similarity_score ?? 0;

            // Add contextual factors
            $score += $this->calculateContextualScore($candidate, $userFeatures, $vertical);

            // Add personalization factors
            $score += $this->calculatePersonalizationScore($candidate, $userFeatures);

            // Add business rules
            $score += $this->calculateBusinessRuleScore($candidate, $userFeatures);

            $candidate->rerank_score = $score;
            $candidate->recommendation_score = $score;
        }

        return $candidates->sortByDesc('rerank_score')->values();
    }

    /**
     * Calculate contextual score
     */
    private function calculateContextualScore(object $candidate, array $userFeatures, string $vertical): float
    {
        $score = 0.0;

        // Time of day relevance
        $hour = now()->hour;
        if ($hour >= 9 && $hour <= 12) {
            $score += 0.1; // Morning boost
        } elseif ($hour >= 17 && $hour <= 20) {
            $score += 0.15; // Evening boost
        }

        // Seasonal relevance
        $season = $this->getSeason();
        if ($season === 'summer' && $vertical === 'grooming') {
            $score += 0.1; // More grooming in summer
        }

        return $score;
    }

    /**
     * Calculate personalization score
     */
    private function calculatePersonalizationScore(object $candidate, array $userFeatures, float): float
    {
        $score = 0.0;

        // Price sensitivity
        if (isset($userFeatures['behavioral']['avg_session_value'])) {
            $avgValue = $userFeatures['behavioral']['avg_session_value'];
            $itemPrice = $candidate->price ?? 0;

            if ($itemPrice <= $avgValue * 1.2) {
                $score += 0.2; // Within price range
            } elseif ($itemPrice <= $avgValue * 1.5) {
                $score += 0.1; // Slightly above but acceptable
            }
        }

        // Category preference
        if (isset($userFeatures['preferences']['favorite_categories'])) {
            $favoriteCats = $userFeatures['preferences']['favorite_categories'];
            if (in_array($candidate->category_id ?? null, $favoriteCats)) {
                $score += 0.3; // Strong preference match
            }
        }

        // Loyalty bonus
        if (isset($userFeatures['historical']['loyalty_points'])) {
            $loyaltyPoints = $userFeatures['historical']['loyalty_points'];
            if ($loyaltyPoints > 1000) {
                $score += 0.1; // Loyal customer bonus
            }
        }

        return $score;
    }

    /**
     * Calculate business rule score
     */
    private function calculateBusinessRuleScore(object $candidate, array $userFeatures): float
    {
        $score = 0.0;

        // Promotional boost
        if (isset($candidate->is_promotional) && $candidate->is_promotional) {
            $score += 0.2;
        }

        // New item discovery
        if (isset($candidate->created_at) && $candidate->created_at->diffInDays(now()) < 30) {
            $score += 0.15;
        }

        // High availability
        if (isset($candidate->availability_score) && $candidate->availability_score > 0.8) {
            $score += 0.1;
        }

        // High rating
        if (isset($candidate->avg_rating) && $candidate->avg_rating >= 4.5) {
            $score += 0.15;
        }

        return $score;
    }

    private function getSeason(): string
    {
        $month = now()->month;

        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }
}
