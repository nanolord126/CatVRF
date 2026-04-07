<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\PersonalDevelopment;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class PersonalDevelopmentApiController extends Controller
{

    /**
         * Конструктор с инициализацией корреляционного ID.
         */
        public function __construct(
        private readonly Request $request,
            private PersonalDevelopmentService $pdService,
            private AIGrowthConstructor $aiConstructor,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}
        /**
         * GET /api/v1/pd/programs
         *
         * Получить список доступных программ (с фильтрацией по B2B/B2C).
         */
        public function indexPrograms(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $programs = Program::where('tenant_id', tenant('id'))
                ->when($request->boolean('corporate'), fn($q) => $q->where('is_corporate', true))
                ->orderBy('id', 'desc')
                ->paginate(15);
            return $this->response->json([
                'success' => true,
                'data' => $programs,
                'meta' => ['correlation_id' => $correlationId],
            ]);
        }
        /**
         * POST /api/v1/pd/enroll
         *
         * Записаться на программу обучения.
         */
        public function enroll(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $validated = $request->validate([
                'program_uuid' => 'required|uuid|exists:pd_programs,uuid',
            ]);
            try {
                $program = Program::where('uuid', $validated['program_uuid'])->firstOrFail();
                $enrollment = $this->pdService->enrollToProgram($program, $request->user());
                return $this->response->json([
                    'success' => true,
                    'data' => $enrollment,
                    'meta' => ['correlation_id' => $correlationId],
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('PD API Enrollment Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => false,
                    'error' => 'Не удалось произвести зачисление. Проверьте баланс или обратитесь в поддержку.',
                ], 422);
            }
        }
        /**
         * POST /api/v1/pd/ai-roadmap
         *
         * Сгенерировать персональный план развития через AI-конструктор.
         */
        public function generateAiRoadmap(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            // 1. Fraud Check (по токену/IP на лимиты генераций)
            app(\App\Services\FraudControlService::class)->check(
                userId: (int) ($this->guard->id() ?? 0),
                operationType: 'pd_ai_generation',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            );
            $validated = $request->validate([
                'focus' => 'required|string|in:career,finance,health,soft_skills',
                'time_commitment' => 'required|string|in:low,medium,high',
            ]);
            try {
                $roadmap = $this->aiConstructor->generateRoadmap(
                    userId: (int) $request->user()->id,
                    goals: $validated
                );
                return $this->response->json([
                    'success' => true,
                    'data' => $roadmap,
                    'meta' => ['correlation_id' => $correlationId],
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('PD AI Roadmap Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => false,
                    'error' => 'Ошибка генерации AI-плана. Попробуйте позже.',
                ], 500);
            }
        }
}
