<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Services;

use Modules\Fashion\Services\FashionReviewAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionReviewAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionReviewAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionReviewAggregationService::class);
    }

    public function test_aggregate_product_reviews(): void
    {
        $result = $this->service->aggregateProductReviews(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('average_rating', $result);
        $this->assertArrayHasKey('total_reviews', $result);
    }

    public function test_aggregate_product_reviews_rating_distribution(): void
    {
        $result = $this->service->aggregateProductReviews(1, 1);

        $this->assertArrayHasKey('rating_distribution', $result);
        $this->assertIsArray($result['rating_distribution']);
    }

    public function test_aggregate_product_reviews_sentiment(): void
    {
        $result = $this->service->aggregateProductReviews(1, 1);

        $this->assertArrayHasKey('sentiment', $result);
        $this->assertContains($result['sentiment'], ['positive', 'neutral', 'negative']);
    }

    public function test_get_helpful_reviews(): void
    {
        $result = $this->service->getHelpfulReviews(1, 1, 5);

        $this->assertIsArray($result);
    }

    public function test_mark_review_helpful(): void
    {
        $result = $this->service->markReviewHelpful(1, 1, 1);

        $this->assertIsBool($result);
    }

    public function test_moderate_review_approve(): void
    {
        $result = $this->service->moderateReview(1, true, 1);

        $this->assertIsBool($result);
    }

    public function test_moderate_review_reject(): void
    {
        $result = $this->service->moderateReview(1, false, 1);

        $this->assertIsBool($result);
    }

    public function test_get_store_review_insights(): void
    {
        $result = $this->service->getStoreReviewInsights(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('average_rating', $result);
        $this->assertArrayHasKey('recent_trend', $result);
    }
}
