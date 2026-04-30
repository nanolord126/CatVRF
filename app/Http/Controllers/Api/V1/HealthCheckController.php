<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Http\Controllers\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use App\Models\PersonalAccessToken;
use App\Services\Payment\PaymentIdempotencyService;
use App\Services\Security\TenantAwareRateLimiter;
use App\Services\Webhook\WebhookSignatureValidator;

final class HealthCheckController extends Controller
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly ResponseFactory $response,
        private readonly LogManager $logger,
        private readonly RedisFactory $redis,
    ) {}

    /**
     * Get overall system health status
     */
    public function __invoke(Request $request): JsonResponse
    {
        $timestamp = CarbonImmutable::now()->toIso8601String();
        $status = 'ok';
        $components = [];
        // Check database connectivity
        try {
            $this->db->connection()->getPdo();
            $components['database'] = ['status' => 'ok', 'checked_at' => $timestamp];
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json(['error' => $e->getMessage(), 'correlation_id' => $request->header('X-Correlation-ID')], 500);
        }
        // Check Redis connectivity (rate limiting, cache)
        try {
            $this->redis->connection()->ping();
            $components['redis'] = ['status' => 'ok', 'checked_at' => $timestamp];
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json(['error' => $e->getMessage(), 'correlation_id' => $request->header('X-Correlation-ID')], 500);
        }
        // Check Cache system
        try {
            $this->cache->put('health_check_test', 'ok', 60);
            $cached = $this->cache->get('health_check_test');
            if ($cached === 'ok') {
                $components['cache'] = ['status' => 'ok', 'checked_at' => $timestamp];
            } else {
                throw new \RuntimeException('Cache get/put failed');
            }
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json(['error' => $e->getMessage(), 'correlation_id' => $request->header('X-Correlation-ID')], 500);
        }
        // Check Sanctum token model
        try {
            if (class_exists(PersonalAccessToken::class)) {
                $tokenCount = PersonalAccessToken::count();
                $components['sanctum'] = [
                    'status' => 'ok',
                    'active_tokens' => $tokenCount,
                    'checked_at' => $timestamp,
                ];
            } else {
                throw new \RuntimeException('PersonalAccessToken model not found');
            }
        } catch (\Exception $e) {
            $components['sanctum'] = ['status' => 'error', 'message' => $e->getMessage(), 'checked_at' => $timestamp];
            $status = 'degraded';
        }
        // Check API services
        try {
            if (class_exists(TenantAwareRateLimiter::class)) {
                $components['rate_limiter'] = ['status' => 'ok', 'checked_at' => $timestamp];
            }
            if (class_exists(PaymentIdempotencyService::class)) {
                $components['idempotency'] = ['status' => 'ok', 'checked_at' => $timestamp];
            }
            if (class_exists(WebhookSignatureValidator::class)) {
                $components['webhook_validator'] = ['status' => 'ok', 'checked_at' => $timestamp];
            }
        } catch (\Exception $e) {
            $components['api_services'] = ['status' => 'error', 'message' => $e->getMessage(), 'checked_at' => $timestamp];
            $status = 'degraded';
        }
        // Response code based on status
        $httpCode = $status === 'ok' ? 200 : 503;

        return $this->response->json([
            'status' => $status,
            'timestamp' => $timestamp,
            'components' => $components,
            'environment' => $this->config->get('app.env'),
            'version' => '1.0.0',
        ], $httpCode);
    }
}
