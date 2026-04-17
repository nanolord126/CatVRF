<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\EventPlanning;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class EventPlanningApiController extends Controller
{

    public function __construct(
            private readonly AIEventPlannerConstructor $aiConstructor,
            private readonly EventPlanningService $planningService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Generate an AI event plan for the user.
         * POST /api/event-planning/generate-plan
         */
        public function generatePlan(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                // 1. Validation Logic (Layer 4/8: Controller Validation)
                $validated = $request->validate([
                    'guest_count' => ['required', 'integer', 'min:1', 'max:5000'],
                    'theme' => ['required', 'string', 'max:100'],
                    'budget_limit' => ['required', 'integer', 'min:1000000'], // Min 10k RUB in cents
                    'is_b2b' => ['nullable', 'boolean'],
                ]);
                // 2. Audit Entry (Canon 2026: Mandatory audit trace)
                $this->logger->channel('audit')->info('[API] Plan generation requested', [
                    'correlation_id' => $correlationId,
                    'user_id' => $request->user()?->id ?? 'anonymous',
                    'params' => $validated,
                ]);
                // 3. AI Service Invocation (Layer 7: AI/ML Constructor)
                $plan = $this->aiConstructor->generatePlan(
                    guestCount: $validated['guest_count'],
                    theme: $validated['theme'],
                    budgetLimit: $validated['budget_limit'],
                    isB2B: $validated['is_b2b'] ?? false,
                    correlationId: $correlationId
                );
                // 4. Return Structuring (Layer 8: Responder)
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $plan,
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'api_version' => '1.0.0',
                        'trace_id' => $correlationId,
                    ]
                ], 200);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                // 5. Error Handling (Canon 2026: Strict trace log)
                $this->logger->channel('audit')->error('[API] Plan generation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'An error occurred during AI plan generation. Please check our budget limits.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Create a formal project based on the AI plan.
         * POST /api/event-planning/projects/create
         */
        public function createFromPlan(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                 $validated = $request->validate([
                    'title' => ['required', 'string', 'max:255'],
                    'planned_date' => ['required', 'date', 'after:today'],
                    'guest_count' => ['required', 'integer'],
                    'budget' => ['required', 'integer'],
                    'planner_id' => ['required', 'exists:event_planners,id'],
                ]);
                 $this->logger->channel('audit')->info('[API] Creating project from AI plan', [
                    'correlation_id' => $correlationId,
                    'data' => $validated,
                ]);
                // Call Domain Service (Layer 4)
                $project = $this->planningService->createProject(
                    title: $validated['title'],
                    plannedDate: $validated['planned_date'],
                    guestCount: (int)$validated['guest_count'],
                    budgetPlanned: (int)$validated['budget'],
                    plannerId: (int)$validated['planner_id'],
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'project_uuid' => $project->uuid,
                    'message' => 'Project initialized successfully from AI plan.',
                ], 201);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('[API] Project initialization failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }
}
