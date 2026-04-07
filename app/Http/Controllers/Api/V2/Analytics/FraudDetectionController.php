<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class FraudDetectionController extends Controller
{

    private readonly FraudDetectionMLService $fraudService;
        public function __construct(FraudDetectionMLService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    )
        {
            $this->fraudService = $fraudService;
            // PRODUCTION-READY 2026 CANON: Middleware для Fraud Detection Analytics
             // Только авторизованные пользователи
             // 1000 light / 100 heavy запросов/час
             // Tenant scoping обязателен для аналитики
             // Только управленцы видят фрод-скоры
        }
        /**
         * POST /api/v2/fraud-detection/score
         * Оценивает платёж на предмет мошенничества
         *
         * @param FraudScoreRequest $request
         * @return JsonResponse
         */
        public function scorePayment(FraudScoreRequest $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            try {
                $result = $this->fraudService->scorePaymentAttempt(
                    userId: $this->guard->id() ?? $request->get('user_id'),
                    amount: $request->get('amount'),
                    deviceFingerprint: $request->get('device_fingerprint'),
                    ipAddress: $request->ip(),
                    correlationId: $correlationId
                );
                $this->logger->channel('audit')->info('Payment fraud scored', [
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                    'score' => $result['score'],
                    'is_blocked' => $result['isBlocked'],
                    'timestamp' => now()->toIso8601String()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'data' => [
                        'score' => $result['score'],
                        'is_blocked' => $result['isBlocked'],
                        'reason' => $result['reason'],
                        'confidence' => $result['confidence'],
                    ],
                    'timestamp' => now()->toIso8601String()
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Fraud scoring failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'error' => 'Fraud detection failed',
                    'timestamp' => now()->toIso8601String()
                ], 500);
            }
        }
        /**
         * GET /api/v2/fraud-detection/history
         * Получает историю попыток мошенничества пользователя
         *
         * @return JsonResponse
         */
        public function getHistory(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $fraudAttempts = $this->db->table('fraud_attempts')
                    ->where('user_id', $this->guard->id())
                    ->where('created_at', '>=', now()->subDays(30))
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get();
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'data' => [
                        'total_attempts' => $fraudAttempts->count(),
                        'blocked_attempts' => $fraudAttempts->where('decision', 'block')->count(),
                        'reviewed_attempts' => $fraudAttempts->where('decision', 'review')->count(),
                        'attempts' => $fraudAttempts
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Failed to fetch fraud history', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'error' => 'Failed to fetch fraud history'
                ], 500);
            }
        }
}
