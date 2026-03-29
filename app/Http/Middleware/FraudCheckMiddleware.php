<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FraudControlService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FraudCheckMiddleware — Проверка фрода перед обработкой запроса
 *
 * Production 2026 CANON
 *
 * Запускает ML fraud detection для критичных эндпоинтов:
 * - Платежи и выводы
 * - Применение промокодов
 * - Реферальные действия
 * - Реквизиты для B2B
 *
 * Используется FraudControlService::check() для ML-скоринга.
 * При score > порога блокирует с 403 Forbidden.
 *
 * ✓ Middleware execution order: 6th (correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify)
 *
 * @author CatVRF Team
 * @version 2026.03.28
 */
final class FraudCheckMiddleware
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {
    }

    /**
     * Check fraud on critical endpoints
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'correlation_id' => $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID'),
            ], 401);
        }

        $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            // Запустить fraud check через FraudControlService::check()
            $fraudResult = $this->fraudControlService->check(
                (int)auth()->id(),
                'http_request',
                (int)$request->input('amount', 0),
                $request->ip(),
                $request->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('High fraud score detected', [
                    'user_id' => auth()->id(),
                    'score' => $fraudResult['score'],
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
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
            Log::channel('audit')->error('Fraud check middleware error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'path' => $request->path(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}

