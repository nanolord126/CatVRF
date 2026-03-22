<?php declare(strict_types=1);

namespace Tests\Unit\ShortTermRentals;

use App\Domains\ShortTermRentals\Services\ApartmentReviewService;
use App\Services\FraudControlService;
use Tests\TestCase;
use Mockery;

final class ApartmentReviewServiceTest extends TestCase
{
    public function test_create_review_calls_fraud_check(): void
    {
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->once();

        $service = new ApartmentReviewService($fraudMock);
        
        $data = [
            'tenant_id' => 1,
            'user_id' => 1,
            'apartment_id' => 1,
            'rating' => 5,
            'comment' => 'Great apartment!',
        ];

        $service->createReview($data, 'correlation-123');
        $this->assertTrue(true);
    }
}
