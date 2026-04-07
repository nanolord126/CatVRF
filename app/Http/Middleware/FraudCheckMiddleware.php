<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class FraudCheckMiddleware
{

    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    )
    {
        // Implementation required by canon
    }

        /**
         * Check fraud on critical endpoints
         */
        public function handle(Request $request, Closure $next): mixed
        {
            if (!$this->guard->check()) {
                return $this->response->json([
                    'error' => 'Unauthorized',
                    'correlation_id' => $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID'),
                ], 401);
            }

            $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            try {
                // Запустить fraud check через FraudControlService::check()
                $fraudResult = $this->fraud->check(
                    (int)$this->guard->id(),
                    'http_request',
                    (int)$request->input('amount', 0),
                    $request->ip(),
                    $request->header('X-Device-Fingerprint'),
                    $correlationId,
                );

                if ($fraudResult['decision'] === 'block') {
                    $this->logger->channel('fraud_alert')->warning('High fraud score detected', [
                        'user_id' => $this->guard->id(),
                        'score' => $fraudResult['score'],
                        'endpoint' => $request->path(),
                        'method' => $request->method(),
                        'correlation_id' => $correlationId,
                    ]);

                    return $this->response->json([
                        'error' => 'Operation blocked: suspicious activity detected',
                        'score' => $fraudResult['score'],
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                // Сохранить fraud result в request для использования в контроллере
                $request->attributes->set('fraud_score', $fraudResult['score']);
                $request->attributes->set('fraud_decision', $fraudResult['decision']);
                $request->attributes->set('correlation_id', $correlationId);

                return $next($request);

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Fraud check middleware error', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->guard->id(),
                    'path' => $request->path(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->response->json([
                    'error' => 'Internal server error',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
