<?php declare(strict_types=1);

namespace Tests\Unit\Flowers;

use App\Domains\Flowers\Services\FlowerReviewService;
use App\Services\FraudControlService;
use Tests\TestCase;
use Mockery;

final class FlowerReviewServiceTest extends TestCase
{
    public function test_create_review_calls_fraud_check(): void
    {
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->once();

        $service = new FlowerReviewService($fraudMock);
        
        $data = [
            'tenant_id' => 1,
            'user_id' => 1,
            'order_id' => 1,
            'rating' => 5,
            'comment' => 'Great flowers!',
        ];

        $service->createReview($data, 'correlation-123');
        $this->assertTrue(true);
    }
}
