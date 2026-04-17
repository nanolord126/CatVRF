<?php declare(strict_types=1);

namespace App\Domains\Education\Http\Controllers;

use App\Domains\Education\Models\CorporateContract;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\VerticalCourse;
use App\Domains\Education\Services\B2BVerticalTrainingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class B2BVerticalTrainingController extends Controller
{
    public function __construct(
        private readonly B2BVerticalTrainingService $trainingService,
    ) {}

    /**
     * Получить курсы для конкретной вертикали
     * 
     * GET /api/v1/education/b2b/verticals/{vertical}/courses
     */
    public function getCoursesForVertical(Request $request, string $vertical): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_role' => 'string|nullable',
            'difficulty_level' => 'string|nullable|in:beginner,intermediate,advanced',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $courses = $this->trainingService->getCoursesForVertical(
            $vertical,
            $request->input('target_role'),
            $request->input('difficulty_level')
        );

        return response()->json([
            'vertical' => $vertical,
            'courses' => $courses,
            'total' => $courses->count(),
        ]);
    }

    /**
     * Получить обязательные курсы для вертикали
     * 
     * GET /api/v1/education/b2b/verticals/{vertical}/courses/required
     */
    public function getRequiredCoursesForVertical(string $vertical): JsonResponse
    {
        $courses = $this->trainingService->getRequiredCoursesForVertical($vertical);

        return response()->json([
            'vertical' => $vertical,
            'required_courses' => $courses,
            'total' => $courses->count(),
        ]);
    }

    /**
     * Получить рекомендуемые курсы для роли
     * 
     * GET /api/v1/education/b2b/verticals/{vertical}/roles/{role}/recommendations
     */
    public function getRecommendedCoursesForRole(string $vertical, string $role): JsonResponse
    {
        $courses = $this->trainingService->getRecommendedCoursesForRole($vertical, $role);

        return response()->json([
            'vertical' => $vertical,
            'role' => $role,
            'recommended_courses' => $courses,
            'total' => $courses->count(),
        ]);
    }

    /**
     * Зачислить сотрудника на все обязательные курсы вертикали
     * 
     * POST /api/v1/education/b2b/verticals/{vertical}/enroll-employee
     */
    public function enrollEmployeeInRequiredCourses(Request $request, string $vertical): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'contract_id' => 'required|integer|exists:corporate_contracts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = \App\Models\User::findOrFail($request->input('employee_id'));
        $contract = CorporateContract::findOrFail($request->input('contract_id'));
        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

        $enrollments = $this->trainingService->enrollEmployeeInRequiredCourses(
            $employee,
            $vertical,
            $contract,
            $correlationId
        );

        return response()->json([
            'message' => 'Employee enrolled in required courses',
            'vertical' => $vertical,
            'employee_id' => $employee->id,
            'enrollments' => $enrollments,
            'total_enrolled' => $enrollments->count(),
        ], 201);
    }

    /**
     * Зачислить сотрудника на конкретный курс вертикали
     * 
     * POST /api/v1/education/b2b/verticals/{vertical}/courses/{course}/enroll
     */
    public function enrollEmployeeInCourse(Request $request, string $vertical, int $courseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'contract_id' => 'required|integer|exists:corporate_contracts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = \App\Models\User::findOrFail($request->input('employee_id'));
        $course = Course::findOrFail($courseId);
        $contract = CorporateContract::findOrFail($request->input('contract_id'));
        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

        $enrollment = $this->trainingService->enrollEmployeeInCourse(
            $employee,
            $course,
            $contract,
            $correlationId
        );

        return response()->json([
            'message' => 'Employee enrolled in course',
            'vertical' => $vertical,
            'course_id' => $course->id,
            'employee_id' => $employee->id,
            'enrollment' => $enrollment,
        ], 201);
    }

    /**
     * Получить прогресс обучения сотрудника по вертикали
     * 
     * GET /api/v1/education/b2b/verticals/{vertical}/employees/{employee}/progress
     */
    public function getEmployeeProgressForVertical(string $vertical, int $employeeId): JsonResponse
    {
        $employee = \App\Models\User::findOrFail($employeeId);

        $progress = $this->trainingService->getEmployeeProgressForVertical($employee, $vertical);

        return response()->json($progress);
    }

    /**
     * Получить прогресс всех сотрудников компании по вертикали
     * 
     * GET /api/v1/education/b2b/verticals/{vertical}/company/progress
     */
    public function getCompanyProgressForVertical(Request $request, string $vertical): JsonResponse
    {
        $tenantId = $request->input('tenant_id', tenant('id') ?? 0);

        $progress = $this->trainingService->getCompanyProgressForVertical($tenantId, $vertical);

        return response()->json([
            'vertical' => $vertical,
            'tenant_id' => $tenantId,
            'employees' => $progress,
            'total_employees' => $progress->count(),
        ]);
    }

    /**
     * Создать курс для конкретной вертикали
     * 
     * POST /api/v1/education/b2b/vertical-courses
     */
    public function createVerticalCourse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|integer|exists:courses,id',
            'vertical' => 'required|string',
            'target_role' => 'string|nullable',
            'difficulty_level' => 'string|nullable|in:beginner,intermediate,advanced',
            'duration_hours' => 'integer|nullable|min:0',
            'is_required' => 'boolean|nullable',
            'prerequisites' => 'array|nullable',
            'learning_objectives' => 'array|nullable',
            'metadata' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

        $verticalCourse = $this->trainingService->createVerticalCourse(
            $request->all(),
            $correlationId
        );

        return response()->json([
            'message' => 'Vertical course created',
            'vertical_course' => $verticalCourse,
        ], 201);
    }

    /**
     * Обновить курс вертикали
     * 
     * PUT /api/v1/education/b2b/vertical-courses/{id}
     */
    public function updateVerticalCourse(Request $request, int $id): JsonResponse
    {
        $verticalCourse = VerticalCourse::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'target_role' => 'string|nullable',
            'difficulty_level' => 'string|nullable|in:beginner,intermediate,advanced',
            'duration_hours' => 'integer|nullable|min:0',
            'is_required' => 'boolean|nullable',
            'prerequisites' => 'array|nullable',
            'learning_objectives' => 'array|nullable',
            'metadata' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updatedCourse = $this->trainingService->updateVerticalCourse(
            $verticalCourse,
            $request->all()
        );

        return response()->json([
            'message' => 'Vertical course updated',
            'vertical_course' => $updatedCourse,
        ]);
    }

    /**
     * Удалить курс вертикали
     * 
     * DELETE /api/v1/education/b2b/vertical-courses/{id}
     */
    public function deleteVerticalCourse(int $id): JsonResponse
    {
        $verticalCourse = VerticalCourse::findOrFail($id);

        $result = $this->trainingService->deleteVerticalCourse($verticalCourse);

        return response()->json([
            'message' => 'Vertical course deleted',
            'deleted' => $result,
        ]);
    }
}
