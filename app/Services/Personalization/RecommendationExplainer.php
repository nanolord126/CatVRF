<?php

declare(strict_types=1);

namespace App\Services\Personalization;

use App\Services\Personalization\DTOs\Recommendation;

final class RecommendationExplainer
{
    /**
     * Explain why a recommendation was made
     *
     * @return array<string, mixed>
     */
    public function explain(object $item, object $subject): array
    {
        $explanation = [
            'primary_reason' => $this->getPrimaryReason($item, $subject),
            'factors' => $this->getExplanationFactors($item, $subject),
            'confidence' => $this->calculateExplanationConfidence($item, $subject),
        ];

        return $explanation;
    }

    /**
     * Get detailed explanation with SHAP-like values
     *
     * @return array<string, mixed>
     */
    public function explainDetailed(Recommendation $recommendation, object $subject): array
    {
        $factors = $this->getDetailedFactors($recommendation, $subject);

        return [
            'recommendation_id' => $recommendation->id,
            'recommendation_name' => $recommendation->name,
            'score' => $recommendation->score,
            'confidence' => $recommendation->confidence,
            'primary_reason' => $recommendation->getPrimaryReason(),
            'factors' => $factors,
            'subject_type' => $subject instanceof \App\Models\User ? 'user' : 'pet',
            'subject_id' => $subject->id,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get primary reason for recommendation
     */
    private function getPrimaryReason(object $item, object $subject): string
    {
        // Check various factors to determine primary reason
        if ($this->isCategoryMatch($item, $subject)) {
            return 'matches_your_preferences';
        }

        if ($this->isPopular($item)) {
            return 'popular_choice';
        }

        if ($this->isSimilarToHistory($item, $subject)) {
            return 'similar_to_previous_choices';
        }

        if ($this->isNew($item)) {
            return 'new_arrival';
        }

        if ($this->isPromotional($item)) {
            return 'special_offer';
        }

        return 'personalized_for_you';
    }

    /**
     * Get explanation factors
     *
     * @return array<int, array{factor: string, impact: string, value: float}>
     */
    private function getExplanationFactors(object $item, object $subject): array
    {
        $factors = [];

        // Category match
        if ($this->isCategoryMatch($item, $subject)) {
            $factors[] = [
                'factor' => 'category_preference',
                'impact' => 'positive',
                'value' => 0.3,
                'description' => 'Matches your preferred categories',
            ];
        }

        // Price fit
        if ($this->isPriceFit($item, $subject)) {
            $factors[] = [
                'factor' => 'price_range',
                'impact' => 'positive',
                'value' => 0.2,
                'description' => 'Within your typical price range',
            ];
        }

        // Similarity to history
        if ($this->isSimilarToHistory($item, $subject)) {
            $factors[] = [
                'factor' => 'historical_similarity',
                'impact' => 'positive',
                'value' => 0.25,
                'description' => 'Similar to services you\'ve used before',
            ];
        }

        // Popularity
        if ($this->isPopular($item)) {
            $factors[] = [
                'factor' => 'popularity',
                'impact' => 'positive',
                'value' => 0.15,
                'description' => 'Popular among other customers',
            ];
        }

        // Rating
        if (isset($item->avg_rating) && $item->avg_rating >= 4.5) {
            $factors[] = [
                'factor' => 'high_rating',
                'impact' => 'positive',
                'value' => 0.1,
                'description' => 'Highly rated by customers',
            ];
        }

        // Seasonal relevance
        if ($this->isSeasonallyRelevant($item)) {
            $factors[] = [
                'factor' => 'seasonal_relevance',
                'impact' => 'positive',
                'value' => 0.1,
                'description' => 'Perfect for current season',
            ];
        }

        return $factors;
    }

    /**
     * Get detailed factors with SHAP-like values
     *
     * @return array<int, array{feature: string, contribution: float, description: string}>
     */
    private function getDetailedFactors(Recommendation $recommendation, object $subject): array
    {
        // Simulated SHAP-like values
        $factors = [
            [
                'feature' => 'category_preference',
                'contribution' => 0.32,
                'description' => 'Matches your preferred service categories',
            ],
            [
                'feature' => 'price_affinity',
                'contribution' => 0.18,
                'description' => 'Price aligns with your spending patterns',
            ],
            [
                'feature' => 'behavioral_match',
                'contribution' => 0.24,
                'description' => 'Based on your recent activity',
            ],
            [
                'feature' => 'popularity_boost',
                'contribution' => 0.12,
                'description' => 'Popular among similar users',
            ],
            [
                'feature' => 'seasonal_context',
                'contribution' => 0.08,
                'description' => 'Relevant for current time of year',
            ],
            [
                'feature' => 'rating_quality',
                'contribution' => 0.06,
                'description' => 'High customer satisfaction score',
            ],
        ];

        // Sort by absolute contribution
        usort($factors, fn ($a, $b) => abs($b['contribution']) <=> abs($a['contribution']));

        return $factors;
    }

    private function calculateExplanationConfidence(object $item, object $subject): float
    {
        $confidence = 0.5;

        if ($this->isCategoryMatch($item, $subject)) {
            $confidence += 0.2;
        }

        if ($this->isSimilarToHistory($item, $subject)) {
            $confidence += 0.15;
        }

        if (isset($item->avg_rating) && $item->avg_rating >= 4.5) {
            $confidence += 0.1;
        }

        return min(0.95, $confidence);
    }

    private function isCategoryMatch(object $item, object $subject): bool
    {
        $favoriteCats = \Illuminate\Support\Facades\DB::table('user_category_preferences')
            ->where('user_id', $subject->id)
            ->pluck('category_id')
            ->toArray();

        return in_array($item->category_id ?? null, $favoriteCats);
    }

    private function isPopular(object $item): bool
    {
        return isset($item->popularity_score) && $item->popularity_score > 0.7;
    }

    private function isSimilarToHistory(object $item, object $subject): bool
    {
        $similarItems = \Illuminate\Support\Facades\DB::table('bookings')
            ->where('user_id', $subject->id)
            ->where('bookable_type', get_class($item))
            ->where('bookable_category_id', $item->category_id ?? 0)
            ->exists();

        return $similarItems;
    }

    private function isNew(object $item): bool
    {
        return isset($item->created_at) && $item->created_at->diffInDays(now()) < 30;
    }

    private function isPromotional(object $item): bool
    {
        return isset($item->is_promotional) && $item->is_promotional;
    }

    private function isPriceFit(object $item, object $subject): bool
    {
        $avgValue = \Illuminate\Support\Facades\DB::table('bookings')
            ->where('user_id', $subject->id)
            ->avg('total_price') ?? 0;

        $itemPrice = $item->price ?? 0;

        return $itemPrice <= $avgValue * 1.5;
    }

    private function isSeasonallyRelevant(object $item): bool
    {
        $season = match (now()->month) {
            3, 4, 5 => 'spring',
            6, 7, 8 => 'summer',
            9, 10, 11 => 'autumn',
            default => 'winter',
        };

        return isset($item->seasonal_tags) && in_array($season, $item->seasonal_tags);
    }
}
