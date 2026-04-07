<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\LanguageLearning;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class LanguageLearningApiController extends Controller
{

    public function __construct(
            private LanguageService $languageService,
            private EnrollmentService $enrollmentService,
            private AILearningPathConstructor $aiConstructor,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
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
            return $this->response->json([
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
                return $this->response->json([
                    'success' => true,
                    'enrollment_id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('LanguageLearning Enrollment failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
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
                    tenantId: (int)$this->guard->user()?->tenant_id ?? 0,
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'success' => true,
                    'plan' => $path,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
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
            return $this->response->json([
                'success' => true,
                'data' => $course,
            ]);
        }
}
