<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\Services\CourseEnrollmentService;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use Illuminate\Support\Facades\DB;
use Mockery;

final class CourseEnrollmentServiceTest extends TestCase
{
    private CourseEnrollmentService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private IdempotencyService $idempotency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = Mockery::mock(FraudControlService::class);
        $this->audit = Mockery::mock(AuditService::class);
        $this->idempotency = Mockery::mock(IdempotencyService::class);

        $this->service = new CourseEnrollmentService(
            $this->audit,
            $this->fraud,
            $this->idempotency,
            Mockery::mock(\App\Domains\Education\Services\EducationMilestonePaymentService::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_enroll_with_split_payment_b2c(): void
    {
        DB::table('courses')->insert([
            'id' => 1,
            'uuid' => 'course-uuid-1',
            'tenant_id' => tenant()->id,
            'teacher_id' => 1,
            'title' => 'Test Course',
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
            'status' => 'published',
        ]);

        $this->fraud->shouldReceive('check')->once();
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();

        $result = $this->service->enrollWithSplitPayment(1, 1, null, 'test-correlation');

        $this->assertArrayHasKey('enrollment_id', $result);
        $this->assertEquals('b2c', $result['mode']);
        $this->assertEquals(1000.0, $result['total_price_rub']);
        $this->assertEquals(150.0, $result['marketplace_share_rub']);
        $this->assertEquals(850.0, $result['teacher_share_rub']);
    }

    public function test_enroll_with_split_payment_b2b(): void
    {
        DB::table('courses')->insert([
            'id' => 1,
            'uuid' => 'course-uuid-1',
            'tenant_id' => tenant()->id,
            'teacher_id' => 1,
            'title' => 'Test Course',
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
            'status' => 'published',
        ]);

        $this->fraud->shouldReceive('check')->once();
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();

        $result = $this->service->enrollWithSplitPayment(1, 1, 1, 'test-correlation');

        $this->assertEquals('b2b', $result['mode']);
        $this->assertEquals(800.0, $result['total_price_rub']);
    }

    public function test_update_progress(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => 1,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'active',
            'progress_percent' => 0,
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->updateProgress(1, 50, 'test-correlation');

        $enrollment = DB::table('enrollments')->where('id', 1)->first();
        $this->assertEquals(50, $enrollment->progress_percent);
    }

    public function test_update_progress_to_completion(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => 1,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'active',
            'progress_percent' => 0,
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->updateProgress(1, 100, 'test-correlation');

        $enrollment = DB::table('enrollments')->where('id', 1)->first();
        $this->assertEquals('completed', $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_cancel_enrollment(): void
    {
        DB::table('enrollments')->insert([
            'id' => 1,
            'uuid' => 'enroll-uuid-1',
            'tenant_id' => tenant()->id,
            'user_id' => 1,
            'course_id' => 1,
            'mode' => 'b2c',
            'status' => 'active',
            'progress_percent' => 0,
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->cancelEnrollment(1, 'User request', 'test-correlation');

        $enrollment = DB::table('enrollments')->where('id', 1)->first();
        $this->assertEquals('cancelled', $enrollment->status);
        $this->assertEquals('User request', $enrollment->cancellation_reason);
    }
}
