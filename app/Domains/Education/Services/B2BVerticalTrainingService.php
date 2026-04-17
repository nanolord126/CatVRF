<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Events\EmployeeEnrolledInVerticalCourse;
use App\Domains\Education\Events\VerticalCourseCreated;
use App\Domains\Education\Events\VerticalCourseDeleted;
use App\Domains\Education\Events\VerticalCourseUpdated;
use App\Domains\Education\Models\CorporateContract;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\VerticalCourse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * B2BVerticalTrainingService - сервис для управления B2B обучением по бизнес-вертикалям
 * 
 * Обеспечивает:
 * - Получение доступных курсов для конкретной вертикали
 * - Зачисление сотрудников на курсы по вертикали
 * - Отслеживание прогресса обучения по вертикали
 * - Управление обязательными курсами для вертикали
 */
final readonly class B2BVerticalTrainingService
{
    public function __construct(
        private readonly EducationManagementService $educationManagement,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить все курсы для конкретной вертикали
     */
    public function getCoursesForVertical(string $vertical, ?string $targetRole = null, ?string $difficultyLevel = null): Collection
    {
        $query = VerticalCourse::query()->forVertical($vertical);

        if ($targetRole) {
            $query->byTargetRole($targetRole);
        }

        if ($difficultyLevel) {
            $query->byDifficulty($difficultyLevel);
        }

        return $query->with('course')->get();
    }

    /**
     * Получить обязательные курсы для вертикали
     */
    public function getRequiredCoursesForVertical(string $vertical): Collection
    {
        return VerticalCourse::query()
            ->forVertical($vertical)
            ->required()
            ->with('course')
            ->get();
    }

    /**
     * Зачислить сотрудника на все обязательные курсы вертикали
     */
    public function enrollEmployeeInRequiredCourses(
        User $employee,
        string $vertical,
        CorporateContract $contract,
        string $correlationId
    ): Collection {
        $this->logger->info('B2B Vertical Training: Enrolling employee in required courses', [
            'employee_id' => $employee->id,
            'vertical' => $vertical,
            'contract_id' => $contract->id,
            'correlation_id' => $correlationId,
        ]);

        $requiredCourses = $this->getRequiredCoursesForVertical($vertical);
        $enrollments = collect();

        foreach ($requiredCourses as $verticalCourse) {
            try {
                $enrollment = $this->educationManagement->enrollUserUnderContract(
                    $employee,
                    $contract,
                    $verticalCourse->course,
                    $correlationId
                );
                $enrollments->push($enrollment);
            } catch (\Exception $e) {
                $this->logger->error('B2B Vertical Training: Failed to enroll employee in course', [
                    'employee_id' => $employee->id,
                    'course_id' => $verticalCourse->course_id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return $enrollments;
    }

    /**
     * Зачислить сотрудника на конкретный курс вертикали
     */
    public function enrollEmployeeInCourse(
        User $employee,
        Course $course,
        CorporateContract $contract,
        string $correlationId
    ): Enrollment {
        $this->logger->info('B2B Vertical Training: Enrolling employee in course', [
            'employee_id' => $employee->id,
            'course_id' => $course->id,
            'contract_id' => $contract->id,
            'correlation_id' => $correlationId,
        ]);

        $enrollment = $this->educationManagement->enrollUserUnderContract(
            $employee,
            $contract,
            $course,
            $correlationId
        );

        // Получаем VerticalCourse для события
        $verticalCourse = VerticalCourse::where('course_id', $course->id)->first();
        
        if ($verticalCourse) {
            Event::dispatch(new EmployeeEnrolledInVerticalCourse(
                $employee,
                $verticalCourse,
                $enrollment,
                $correlationId
            ));
        }

        return $enrollment;
    }

    /**
     * Получить прогресс обучения сотрудника по вертикали
     */
    public function getEmployeeProgressForVertical(User $employee, string $vertical): array
    {
        $verticalCourses = $this->getCoursesForVertical($vertical);
        $enrollments = Enrollment::query()
            ->where('user_id', $employee->id)
            ->whereIn('course_id', $verticalCourses->pluck('course_id'))
            ->get();

        $totalCourses = $verticalCourses->count();
        $completedCourses = $enrollments->whereNotNull('completed_at')->count();
        $averageProgress = $enrollments->avg('progress_percent') ?? 0;

        return [
            'vertical' => $vertical,
            'total_courses' => $totalCourses,
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $enrollments->whereNull('completed_at')->where('progress_percent', '>', 0)->count(),
            'not_started_courses' => $totalCourses - $enrollments->count(),
            'average_progress_percent' => round($averageProgress, 2),
            'completion_rate_percent' => $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 2) : 0,
        ];
    }

    /**
     * Получить всех сотрудников компании с их прогрессом по вертикали
     */
    public function getCompanyProgressForVertical(int $tenantId, string $vertical): Collection
    {
        $verticalCourses = $this->getCoursesForVertical($vertical);
        $courseIds = $verticalCourses->pluck('course_id');

        $enrollments = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('course_id', $courseIds)
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $employees = User::where('tenant_id', $tenantId)->get();

        return $employees->map(function ($employee) use ($vertical, $enrollments, $verticalCourses) {
            $employeeEnrollments = $enrollments->get($employee->id, collect());
            
            $totalCourses = $verticalCourses->count();
            $completedCourses = $employeeEnrollments->whereNotNull('completed_at')->count();
            $averageProgress = $employeeEnrollments->avg('progress_percent') ?? 0;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_email' => $employee->email,
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'in_progress_courses' => $employeeEnrollments->whereNull('completed_at')->where('progress_percent', '>', 0)->count(),
                'not_started_courses' => $totalCourses - $employeeEnrollments->count(),
                'average_progress_percent' => round($averageProgress, 2),
                'completion_rate_percent' => $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 2) : 0,
            ];
        $v)r;icalCose= 
    }

    /**
     * Создать курс для конкретной вертикали
     */
    public function createVerticalCourse(array $data, string $correlationId): VerticalCourse
    {
        $this->logger->info('B2B Vertical Training: Creating vertical course', [
            'vertical' => $data['vertical'],
            'course_id' => $data['course_id'],
            'correlation_id' => $correlationId,
        ]);
);

        Event::dispatch(new VerticalCourseCreated($verticalCourse, $correlationId);

        return $verticalCourse
        return VerticalCourse::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $data['tenant_id'] ?? (tenant('id') ?? 0),
            'course_id' => $data['course_id'],
            'vertical' => $data['vertical'],
            'target_role' => $data['target_role'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
            'duration_hours' => $data['duration_hours'] ?? 0,
            'is_required' => $data['is_required'] ?? false,
            'prerequisites' => $data['prerequisites'] ?? null,
            'learning_objectives' => $data['learning_objectives'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обновить курс вертикали
     */
    public function updateVerticalCourse(VerticalCourse $verticalCourse, array $data): VerticalCourse
    {
        $originalData = $verticalCourse->toArray();
        
        if ($result) {
            Event::dispatch(new VerticalCourseDeleted($verticalCourse));
        }

        $verticalCourse->update($data);

        $changes = array_diff_assoc($verticalCourse->toArray(), $originalData);

        $this->logger->info('B2B Vertical Training: Updated vertical course', [
            'vertical_course_id' => $verticalCourse->id,
            'vertical' => $verticalCourse->vertical,
            'changes' => $changes,
        ]);

        Event::dispatch(new VerticalCourseUpdated($verticalCourse, $changes));

        return $verticalCourse->fresh();
    }

    /**
     * Удалить курс вертикали
     */
    public function deleteVerticalCourse(VerticalCourse $verticalCourse): bool
    {
        $vertical = $verticalCourse->vertical;
        $courseId = $verticalCourse->course_id;

        $result = $verticalCourse->delete();

        $this->logger->info('B2B Vertical Training: Deleted vertical course', [
            'vertical' => $vertical,
            'course_id' => $courseId,
        ]);

        return $result;
    }

    /**
     * Получить рекомендуемые курсы для сотрудника на основе роли
     */
    public function getRecommendedCoursesForRole(string $vertical, string $role): Collection
    {
        return VerticalCourse::query()
            ->forVertical($vertical)
            ->byTargetRole($role)
            ->with('course')
            ->orderBy('difficulty_level')
            ->get();
    }
}
