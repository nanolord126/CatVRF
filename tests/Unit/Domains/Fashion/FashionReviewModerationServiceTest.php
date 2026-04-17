<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionReviewModerationService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionReviewModerationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionReviewModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionReviewModerationService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_moderate_review_returns_structure(): void
    {
        $reviewId = $this->createReview();

        $result = $this->service->moderateReview($reviewId, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('review_id', $result);
        $this->assertArrayHasKey('spam_score', $result);
        $this->assertArrayHasKey('toxicity_score', $result);
        $this->assertArrayHasKey('fake_score', $result);
        $this->assertArrayHasKey('action', $result);
    }

    public function test_detect_spam_scores_spam_keywords(): void
    {
        $comment = 'Buy now! Click here for free winner!';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('detectSpam');
        $method->setAccessible(true);

        $score = $method->invoke($this->service, $comment, 'test-123');

        $this->assertGreaterThan(0.5, $score);
    }

    public function test_detect_toxicity_scores_toxic_words(): void
    {
        $comment = 'This is a terrible and stupid product!';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('detectToxicity');
        $method->setAccessible(true);

        $score = $method->invoke($this->service, $comment, 'test-123');

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_analyze_sentiment_positive(): void
    {
        $comment = 'Great product, excellent quality, highly recommend!';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('analyzeSentiment');
        $method->setAccessible(true);

        $sentiment = $method->invoke($this->service, $comment, 'test-123');

        $this->assertEquals('positive', $sentiment);
    }

    private function createReview(array $overrides = []): int
    {
        return DB::table('fashion_reviews')->insertGetId(array_merge([
            'tenant_id' => 1,
            'fashion_product_id' => 1,
            'user_id' => 1,
            'rating' => 5,
            'comment' => 'Great product!',
            'status' => 'pending',
            'correlation_id' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
