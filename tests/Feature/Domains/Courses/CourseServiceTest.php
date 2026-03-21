<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Courses;

use App\Domains\Courses\Models\Course;
use App\Domains\Courses\Models\Enrollment;
use App\Domains\Courses\Services\CourseService;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * CourseServiceTest — Feature-тесты вертикали Образование/Курсы.
 */
final class CourseServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private CourseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CourseService::class);
    }

    public function test_enrollment_created_successfully(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 25_000_00,
            'status'    => 'active',
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $enrollment = $this->service->enrollStudent([
            'course_id'      => $course->id,
            'student_id'     => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertNotNull($enrollment->uuid);
        $this->assertSame('active', $enrollment->status);
    }

    public function test_commission_14_percent_on_enrollment(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 10_000_00,
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $enrollment = $this->service->enrollStudent([
            'course_id'      => $course->id,
            'student_id'     => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $expectedCommission = (int)(10_000_00 * 0.14);
        $this->assertSame($expectedCommission, $enrollment->commission_amount);
    }

    public function test_certificate_issued_on_100_percent_progress(): void
    {
        $course = Course::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'lesson_count' => 5,
        ]);

        $enrollment = Enrollment::factory()->create([
            'course_id'  => $course->id,
            'student_id' => $this->user->id,
            'tenant_id'  => $this->tenant->id,
            'progress'   => 0,
            'status'     => 'active',
        ]);

        // Complete all lessons
        for ($lesson = 1; $lesson <= 5; $lesson++) {
            $this->service->markLessonComplete($enrollment->id, $lesson, Str::uuid()->toString());
        }

        $enrollment->refresh();
        $this->assertSame(100, $enrollment->progress);
        $this->assertNotNull($enrollment->certificate_issued_at);
        $this->assertSame('completed', $enrollment->status);
    }

    public function test_duplicate_enrollment_prevented(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 10_000_00,
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $this->service->enrollStudent([
            'course_id'      => $course->id,
            'student_id'     => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->enrollStudent([
            'course_id'      => $course->id,
            'student_id'     => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }

    public function test_instructor_payout_after_course_completion(): void
    {
        $course = Course::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'price'        => 10_000_00,
            'instructor_id' => $this->user->id,
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $enrollment = Enrollment::factory()->create([
            'course_id'  => $course->id,
            'student_id' => $this->user->id,
            'tenant_id'  => $this->tenant->id,
            'status'     => 'completed',
            'progress'   => 100,
        ]);

        $this->service->processInstructorPayout($enrollment->id, Str::uuid()->toString());

        $this->assertDatabaseHas('balance_transactions', [
            'type' => 'payout',
        ]);
    }
}
