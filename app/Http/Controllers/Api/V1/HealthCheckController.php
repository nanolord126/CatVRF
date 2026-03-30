<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HealthCheckController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Get overall system health status
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function __invoke(Request $request): JsonResponse
        {
            $timestamp = now()->toIso8601String();
            $status = 'ok';
            $components = [];
            // Check database connectivity
            try {
                DB::connection()->getPdo();
                $components['database'] = ['status' => 'ok', 'checked_at' => $timestamp];
            } catch (\Exception $e) {
                $components['database'] = ['status' => 'error', 'message' => 'Connection failed', 'checked_at' => $timestamp];
                $status = 'degraded';
            }
            // Check Redis connectivity (rate limiting, cache)
            try {
                Redis::ping();
                $components['redis'] = ['status' => 'ok', 'checked_at' => $timestamp];
            } catch (\Exception $e) {
                $components['redis'] = ['status' => 'error', 'message' => 'Connection failed', 'checked_at' => $timestamp];
                $status = 'degraded';
            }
            // Check Cache system
            try {
                Cache::put('health_check_test', 'ok', 60);
                $cached = Cache::get('health_check_test');
                if ($cached === 'ok') {
                    $components['cache'] = ['status' => 'ok', 'checked_at' => $timestamp];
                } else {
                    throw new \Exception('Cache get/put failed');
                }
            } catch (\Exception $e) {
                $components['cache'] = ['status' => 'error', 'message' => 'Get/put failed', 'checked_at' => $timestamp];
                $status = 'degraded';
            }
            // Check Sanctum token model
            try {
                if (class_exists(\App\Models\PersonalAccessToken::class)) {
                    $tokenCount = \App\Models\PersonalAccessToken::count();
                    $components['sanctum'] = [
                        'status' => 'ok',
                        'active_tokens' => $tokenCount,
                        'checked_at' => $timestamp
                    ];
                } else {
                    throw new \Exception('PersonalAccessToken model not found');
                }
            } catch (\Exception $e) {
                $components['sanctum'] = ['status' => 'error', 'message' => $e->getMessage(), 'checked_at' => $timestamp];
                $status = 'degraded';
            }
            // Check API services
            try {
                if (class_exists(\App\Services\Security\TenantAwareRateLimiter::class)) {
                    $components['rate_limiter'] = ['status' => 'ok', 'checked_at' => $timestamp];
                }
                if (class_exists(\App\Services\Payment\PaymentIdempotencyService::class)) {
                    $components['idempotency'] = ['status' => 'ok', 'checked_at' => $timestamp];
                }
                if (class_exists(\App\Services\Webhook\WebhookSignatureValidator::class)) {
                    $components['webhook_validator'] = ['status' => 'ok', 'checked_at' => $timestamp];
                }
            } catch (\Exception $e) {
                $components['api_services'] = ['status' => 'error', 'message' => $e->getMessage(), 'checked_at' => $timestamp];
                $status = 'degraded';
            }
            // Response code based on status
            $httpCode = $status === 'ok' ? 200 : 503;
            return response()->json([
                'status' => $status,
                'timestamp' => $timestamp,
                'components' => $components,
                'environment' => config('app.env'),
                'version' => '1.0.0',
            ], $httpCode);
        }
}
