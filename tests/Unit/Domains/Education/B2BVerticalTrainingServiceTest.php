<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use App\Domains\Education\Models\CorporateContract;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\VerticalCourse;
use App\Domains\Education\Services\B2BVerticalTrainingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class B2BVerticalTrainingServiceTest extends TestCase
{
    use RefreshDatabase;

    private B2BVerticalTrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new B2BVerticalTrainingService(
            $this->app->make(\App\Domains\Education\Services\EducationManagementService::class),
            Log::channel(),
        );
    }

    public function test_get_courses_for_vertical(): void
    {
        // Arrange
        $course = Course::factory()->create();
        VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
            'target_role' => 'master',
            'difficulty_level' => 'beginner',
        ]);

        // Act
        $courses = $this->service->getCoursesForVertical('beauty');

        // Assert
        $this->assertCount(1, $courses);
        $this->assertEquals('beauty', $courses->first()->vertical);
    }

    public function test_get_courses_for_vertical_with_filters(): void
    {
        // Arrange
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        
        VerticalCourse::factory()->create([
            'course_id' => $course1->id,
            'vertical' => 'beauty',
            'target_role' => 'master',
            'difficulty_level' => 'beginner',
        ]);
        
        VerticalCourse::factory()->create([
            'course_id' => $course2->id,
            'vertical' => 'beauty',
            'target_role' => 'manager',
            'difficulty_level' => 'intermediate',
        ]);

        // Act
        $courses = $this->service->getCoursesForVertical('beauty', 'master', 'beginner');

        // Assert
        $this->assertCount(1, $courses);
        $this->assertEquals('master', $courses->first()->target_role);
        $this->assertEquals('beginner', $courses->first()->difficulty_level);
    }

    public function test_get_required_courses_for_vertical(): void
    {
        // Arrange
        $course = Course::factory()->create();
        VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
            'is_required' => true,
        ]);
        
        VerticalCourse::factory()->create([
            'vertical' => 'beauty',
            'is_required' => false,
        ]);

        // Act
        $requiredCourses = $this->service->getRequiredCoursesForVertical('beauty');

        // Assert
        $this->assertCount(1, $requiredCourses);
        $this->assertTrue($requiredCourses->first()->is_required);
    }

    public function test_create_vertical_course(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $data = [
            'course_id' => $course->id,
            'vertical' => 'beauty',
            'target_role' => 'master',
            'difficulty_level' => 'intermediate',
            'duration_hours' => 20,
            'is_required' => true,
        ];

        // Act
        $verticalCourse = $this->service->createVerticalCourse($data, 'test-correlation-id');

        // Assert
        $this->assertDatabaseHas('vertical_courses', [
            'uuid' => $verticalCourse->uuid,
            'course_id' => $course->id,
            'vertical' => 'beauty',
            'target_role' => 'master',
            'difficulty_level' => 'intermediate',
            'duration_hours' => 20,
            'is_required' => true,
        ]);
    }

    public function test_update_vertical_course(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $verticalCourse = VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
            'difficulty_level' => 'beginner',
        ]);

        // Act
        $updatedCourse = $this->service->updateVerticalCourse($verticalCourse, [
            'difficulty_level' => 'advanced',
            'duration_hours' => 40,
        ]);

        // Assert
        $this->assertEquals('advanced', $updatedCourse->difficulty_level);
        $this->assertEquals(40, $updatedCourse->duration_hours);
    }

    public function test_delete_vertical_course(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $verticalCourse = VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
        ]);

        // Act
        $result = $this->service->deleteVerticalCourse($verticalCourse);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('vertical_courses', [
            'id' => $verticalCourse->id,
        ]);
    }

    public function test_get_recommended_courses_for_role(): void
    {
        // Arrange
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        
        VerticalCourse::factory()->create([
            'course_id' => $course1->id,
            'vertical' => 'beauty',
            'target_role' => 'master',
            'difficulty_level' => 'beginner',
        ]);
        
        VerticalCourse::factory()->create([
            'course_id' => $course2->id,
            'vertical' => 'beauty',
            'target_role' => 'manager',
            'difficulty_level' => 'intermediate',
        ]);

        // Act
        $courses = $this->service->getRecommendedCoursesForRole('beauty', 'master');

        // Assert
        $this->assertCount(1, $courses);
        $this->assertEquals('master', $courses->first()->target_role);
    }

    public function test_get_employee_progress_for_vertical(): void
    {
        // Arrange
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
        ]);
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'progress_percent' => 50,
        ]);

        // Act
        $progress = $this->service->getEmployeeProgressForVertical($user, 'beauty');

        // Assert
        $this->assertEquals('beauty', $progress['vertical']);
        $this->assertEquals(1, $progress['total_courses']);
        $this->assertEquals(0, $progress['completed_courses']);
        $this->assertEquals(1, $progress['in_progress_courses']);
        $this->assertEquals(50.0, $progress['average_progress_percent']);
    }

    public function test_get_company_progress_for_vertical(): void
    {
        // Arrange
        $tenantId = 1;
        $user1 = User::factory()->create(['tenant_id' => $tenantId]);
        $user2 = User::factory()->create(['tenant_id' => $tenantId]);
        $course = Course::factory()->create();
        
        VerticalCourse::factory()->create([
            'course_id' => $course->id,
            'vertical' => 'beauty',
        ]);
        
        Enrollment::factory()->create([
            'user_id' => $user1->id,
            'course_id' => $course->id,
            'tenant_id' => $tenantId,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
        
        Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $course->id,
            'tenant_id' => $tenantId,
            'progress_percent' => 30,
        ]);

        // Act
        $progress = $this->service->getCompanyProgressForVertical($tenantId, 'beauty');

        // Assert
        $this->assertCount(2, $progress);
        $this->assertEquals('beauty', $progress->first()['vertical']);
    }
}
