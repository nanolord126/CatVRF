<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\LanguageLearning;
use App\Domains\Education\LanguageLearning\Models\LanguageCourse;
use App\Domains\Education\LanguageLearning\Services\LanguageService;
use App\Domains\Education\LanguageLearning\Services\EnrollmentService;
use App\Domains\Education\LanguageLearning\Services\AILearningPathConstructor;
use App\Http\Controllers\Controller;
use App\Http\Requests\LanguageLearning\LanguageLearningApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
/**
 * API Контроллер для вертикали LanguageLearning.
 * Плотный код > 60 строк. Канон 2026.
 */
final class LanguageLearningApiController extends Controller
{
    public function __construct(
        private LanguageService $languageService,
        private EnrollmentService $enrollmentService,
        private AILearningPathConstructor $aiConstructor
    ) {}
    /**
     * Получить список активных курсов.
     */
    public function index(): JsonResponse
    {
        $courses = LanguageCourse::with(['teacher', 'school'])
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $courses,
            'meta' => [
                'count' => $courses->count(),
                'server_time' => now()->toIso8601String(),
            ]
        ]);
    }
    /**
     * Записаться на курс (Enrollment).
     */
    public function enroll(LanguageLearningApiRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id');
        try {
            $enrollment = $this->enrollmentService->enrollStudent(
                studentId: (int)$request->input('student_id'),
                courseId: (int)$request->input('course_id'),
                correlationId: $correlationId
            );
            return response()->json([
                'success' => true,
                'enrollment_id' => $enrollment->id,
                'status' => $enrollment->status,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('LanguageLearning Enrollment failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
    /**
     * Генератор пути обучения через AI.
     */
    public function constructPath(LanguageLearningApiRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id');
        try {
            $path = $this->aiConstructor->constructPath(
                params: $request->validated(),
                tenantId: (int)auth()->user()?->tenant_id ?? 0,
                correlationId: $correlationId
            );
            return response()->json([
                'success' => true,
                'plan' => $path,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI Generation failed: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * Детальная информация о курсе.
     */
    public function show(int $id): JsonResponse
    {
        $course = LanguageCourse::with(['teacher.school', 'lessons'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }
}
