<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;

/**
 * BaseApiController — Базовый контроллер для всех API-контроллеров
 *
 * Включает общие методы:
 * - $this->isB2C() / $this->isB2B()
 * - $this->auditLog()
 * - $this->successResponse() / $this->errorResponse()
 * - $this->getCorrelationId()
 *
 * Middleware применяются в Routes.
 *
 * PRODUCTION-READY 2026 CANON
 *
 * @author CatVRF Team
 *
 * @version 2026.03.27
 */
abstract class BaseApiController extends Controller
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,) {}

    /**
     * Получить correlation_id для запроса
     */
    protected function getCorrelationId(): string
    {
        return $this->request->get('correlation_id')
            ?? $this->request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();
    }

    /**
     * Проверить режим B2C/B2B из middleware
     */
    protected function isB2C(): bool
    {
        return $this->request->get('b2c_mode') === true;
    }

    protected function isB2B(): bool
    {
        return $this->request->get('b2b_mode') === true;
    }

    protected function getModeType(): string
    {
        return (string) $this->request->get('mode_type', 'b2c');
    }

    /**
     * Лог аудита с correlation_id
     */
    protected function auditLog(string $action, array $data = []): void
    {
        $this->logger->channel('audit')->$this->logger->info($action, array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'user_id' => $this->guard->id(),
            'ip_address' => $this->request->ip(),
            'tenant_id' => filament()->getTenant()?->id,
            'mode' => $this->getModeType(),
        ], $data));
    }

    /**
     * Ответ успеха с correlation_id
     */
    protected function successResponse(mixed $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }

    /**
     * Ответ ошибки с correlation_id
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }

    /**
     * Лог фрода с full stack trace
     */
    protected function fraudLog(string $reason, array $context = []): void
    {
        $this->logger->channel('fraud_alert')->warning("Fraud attempt: {$reason}", array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'user_id' => $this->guard->id(),
            'ip_address' => $this->request->ip(),
            'endpoint' => $this->request->path(),
            'trace' => debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ], $context));
    }
}
