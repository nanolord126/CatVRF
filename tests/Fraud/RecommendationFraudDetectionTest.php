<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Recommendation\Domain\Entities\Recommendation;
use Modules\Recommendation\Domain\Entities\RecommendationClick;
use Modules\FraudDetection\Domain\Enums\FraudType;
use Modules\FraudDetection\Domain\Enums\FraudSeverity;
use Illuminate\Support\Facades\Cache;

final class RecommendationFraudDetectionTest extends BaseFraudTest
{
    public function test_recommendation_manipulation(): void
    {
        $sellerId = 1;
        $productId = 100;

        // Artificially boost product in recommendations
        for ($i = 0; $i < 100; $i++) {
            RecommendationClick::create([
                'seller_id' => $sellerId,
                'product_id' => $productId,
                'user_id' => $i + 1000,
                'device_fingerprint' => 'fp_rec_bot',
                'clicked_at' => now(),
                'converted' => false,
            ]);
        }

        $this->fraudControl->checkRecommendationManipulation([
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'click_count' => 100,
            'conversion_rate' => 0,
            'device_fingerprint' => 'fp_rec_bot',
        ]);

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::RecommendationFraud,
            FraudSeverity::High
        );
    }

    public function test_embedding_manipulation(): void
    {
        $productId = 1;

        $this->fraudControl->checkEmbeddingFraud([
            'product_id' => $productId,
            'original_embedding' => [0.1, 0.2, 0.3],
            'modified_embedding' => [0.9, 0.9, 0.9], // Drastically changed
            'modified_by' => 1,
            'reason' => null,
        ]);

        $this->assertFraudAlertCreated(
            'product',
            $productId,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }

    public function test_collaborative_filtering_poisoning(): void
    {
        $attackerId = 1;
        $targetProductId = 100;

        for ($i = 0; $i < 50; $i++) {
            $this->fraudControl->checkRatingManipulation([
                'user_id' => $attackerId,
                'product_id' => $targetProductId,
                'rating' => 5,
                'device_fingerprint' => 'fp_collab_bot',
                'interaction_count' => 50,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $attackerId,
            FraudType::RecommendationFraud,
            FraudSeverity::High
        );
    }

    public function test_popularity_manipulation(): void
    {
        $productId = 1;

        for ($i = 0; $i < 200; $i++) {
            $this->fraudControl->checkPopularityManipulation([
                'product_id' => $productId,
                'view_count_increment' => 1000,
                'device_fingerprint' => 'fp_pop_bot',
                'unique_visitors' => 1,
            ]);
        }

        $this->assertFraudAlertCreated(
            'product',
            $productId,
            FraudType::ViewManipulation,
            FraudSeverity::High
        );
    }

    public function test_personalization_bypass(): void
    {
        $userId = 1;

        $this->fraudControl->checkPersonalizationBypass([
            'user_id' => $userId,
            'requested_recommendations' => 1000,
            'unique_products_shown' => 10,
            'time_window_minutes' => 5,
            'suspicious_pattern' => true,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::RecommendationFraud,
            FraudSeverity::Medium
        );
    }
}
