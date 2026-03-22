<?php declare(strict_types=1);

namespace Tests\Unit\Courses;

use App\Domains\Courses\Services\CourseReviewService;
use App\Services\FraudControlService;
use Tests\TestCase;
use Mockery;

final class CourseReviewServiceTest extends TestCase
{
    public function test_create_review_calls_fraud_check(): void
    {
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->once();

        $service = new CourseReviewService($fraudMock);
        
        $data = [
            'tenant_id' => 1,
            'user_id' => 1,
            'course_id' => 1,
            'rating' => 5,
            'comment' => 'Great course!',
        ];

        $service->createReview($data, 'correlation-123');
        $this->assertTrue(true);
    }
}
