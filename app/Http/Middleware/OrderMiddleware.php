<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;

use App\Services\FraudControlService;
use App\Services\Security\RateLimiterService;

final class OrderMiddleware
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
        private readonly FraudControlService $fraud,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            if (!$this->guard->check()) {
                return $this->response->json([
                    'error' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 401);
            }

            $userId = (int) $this->guard->id();

            $rateLimitResult = $this->rateLimiter->checkPaymentInit(
                tenantId: $userId,
                userId: $userId,
                correlationId: $correlationId,
            );

            if (!$rateLimitResult) {
                return $this->response->json([
                    'error' => 'Rate limit exceeded',
                    'correlation_id' => $correlationId,
                ], 429);
            }

            $inn = $request->input('inn') ?? $request->header('X-Inn');
            $businessCardId = $request->input('business_card_id') ?? $request->header('X-Business-Card-Id');

            $isB2B = !empty($inn) && !empty($businessCardId);

            $request->merge([
                'b2c_mode' => !$isB2B,
                'b2b_mode' => $isB2B,
                'mode_type' => $isB2B ? 'b2b' : 'b2c',
            ]);

            if ($isB2B) {
                $hasAccess = app('db')->table('business_groups')
                    ->where('tenant_id', $userId)
                    ->where('inn', $inn)
                    ->where('business_card_id', $businessCardId)
                    ->exists();

                if (!$hasAccess) {
                    $this->logger->channel('fraud_alert')->warning('Unauthorized B2B order attempt', [
                        'user_id' => $userId,
                        'inn' => '***' . substr($inn, -4),
                        'path' => $request->path(),
                        'correlation_id' => $correlationId,
                    ]);

                    return $this->response->json([
                        'error' => 'Unauthorized B2B access',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
            }

            $amount = (int) $request->input('total', 0);

            $fraudResult = $this->fraud->check(
                userId: $userId,
                operationType: 'order_request',
                amount: $amount,
                ipAddress: $request->ip(),
                deviceFingerprint: $request->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            $request->attributes->set('fraud_score', $fraudResult['score']);
            $request->attributes->set('fraud_decision', $fraudResult['decision']);
            $request->attributes->set('correlation_id', $correlationId);

            $this->logger->channel('audit')->debug('Order middleware passed', [
                'user_id' => $userId,
                'mode' => $request->get('mode_type'),
                'fraud_score' => $fraudResult['score'],
                'correlation_id' => $correlationId,
            ]);

            return $next($request);

        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Order middleware error', [
                'error' => $e->getMessage(),
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
