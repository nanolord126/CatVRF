<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Consulting;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ConsultingApiController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Unified response format with correlation_id.
         */
        protected function respond(array $data, int $status = 200, string $correlationId = ''): JsonResponse
        {
            return $this->response->json(array_merge($data, [
                'correlation_id' => $correlationId ?: (string) Str::uuid(),
            ]), $status);
        }
        /**
         * Match experts based on user requirements.
         */
        public function match(Request $request, ConsultingMatcherService $matcher): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $requirements = $request->validate([
                'industry' => 'required|string',
                'max_hourly_rate' => 'nullable|integer',
                'min_experience_years' => 'nullable|integer',
                'skills' => 'nullable|array',
            ]);
            $this->logger->channel('audit')->info('Consulting Match Request', array_merge($requirements, ['correlation_id' => $correlationId]));
            $matches = $matcher->matchConsultant($requirements, (int) tenant()->id);
            return $this->respond(['matches' => $matches], 200, $correlationId);
        }
        /**
         * List sessions for the authenticated user (consultant or client).
         */
        public function sessions(Request $request): JsonResponse
        {
            $userId = $request->user()?->id ?? 0;
            $sessions = ConsultingSession::query()
                ->where('client_id', $userId)
                ->orWhere('consultant_id', $userId)
                ->with(['consultant', 'service'])
                ->orderByDesc('scheduled_at')
                ->get();
            return $this->respond(['sessions' => $sessions]);
        }
        /**
         * Log and end a consulting session.
         */
        public function sessionComplete(Request $request, int $id, ConsultingProjectService $service): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $data = $request->validate([
                'duration_minutes' => 'required|integer|min:1',
                'session_notes' => 'required|string',
            ]);
            try {
                $service->fulfillSession($id, $data['duration_minutes'], $data['session_notes']);
                return $this->respond(['status' => 'success', 'message' => 'Session fulfilled.'], 200, $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error("Session fulfillment failed: " . $e->getMessage());
                return $this->respond(['error' => $e->getMessage()], 400, $correlationId);
            }
        }
        /**
         * Get AI business strategy for a client.
         */
        public function strategy(Request $request, ConsultingAIAdvisorService $advisor): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $goals = $request->validate(['goals' => 'required|array']);
            $userId = $request->user()?->id ?? 0;
            $strategy = $advisor->generateStrategy($userId, $goals['goals']);
            return $this->respond(['strategy' => $strategy], 200, $correlationId);
        }
        /**
         * Enroll a business group into a subscription retainer (B2B).
         */
        public function enrollB2B(Request $request, ConsultingB2BService $b2b): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $data = $request->validate([
                'business_group_id' => 'required|integer',
                'firm_id' => 'required|integer',
                'service_id' => 'required|integer',
            ]);
            try {
                $contract = $b2b->enrollBusinessRetainer(
                    $data['business_group_id'],
                    $data['firm_id'],
                    $data['service_id']
                );
                return $this->respond(['contract' => $contract], 201, $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->respond(['error' => $e->getMessage()], 400, $correlationId);
            }
        }
        /**
         * Submit a review for a consulting expert.
         */
        public function review(Request $request, ConsultingRatingService $ratingService): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $data = $request->validate([
                'session_id' => 'required|integer',
                'rating' => 'required|integer|min:1|max:100',
                'comment' => 'required|string',
            ]);
            try {
                $review = $ratingService->submitSessionReview(
                    $data['session_id'],
                    $data['rating'],
                    $data['comment'],
                    (int) $request->user()?->id
                );
                return $this->respond(['review' => $review], 201, $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->respond(['error' => $e->getMessage()], 400, $correlationId);
            }
        }
        /**
         * Predict budget forecast (AI).
         */
        public function budgetForecast(Request $request, ConsultingAIAdvisorService $advisor): JsonResponse
        {
            $userId = $request->user()?->id ?? 0;
            $forecast = $advisor->predictConsultingBudget($userId);
            return $this->respond(['forecast_rub' => $forecast / 100]);
        }
}
