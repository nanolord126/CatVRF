<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * CorrelationIdMiddleware — Инжекция correlation_id в каждый запрос
 *
 * Production 2026 CANON
 *
 * Инжектирует correlation_id в каждый запрос:
 * - Использует X-Correlation-ID header если предоставлен
 * - Генерирует UUID если отсутствует
 * - Возвращает в response headers
 * - Включает полное трейсирование запроса через все слои
 *
 * ✓ Middleware execution order: 1st (correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify)
 *
 * @author CatVRF Team
 * @version 2026.03.28
 */
final class CorrelationIdMiddleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Получить correlation_id из header или сгенерировать новый
        $correlationId = $request->header('X-Correlation-ID')
            ?? $request->header('x-correlation-id')
            ?? (string)Str::uuid();

        // Валидировать формат (UUID)
        if (!Str::isUuid($correlationId)) {
            $correlationId = (string)Str::uuid();
        }

        // Сохранить в request для доступа во всем стеке
        $request->attributes->set('correlation_id', $correlationId);

        // Логировать только для debug уровня
        Log::channel('audit')->debug('Correlation ID injected', [
            'correlation_id' => $correlationId,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        // Продолжить к следующему middleware/контроллеру
        $response = $next($request);

        // Добавить correlation_id в response headers
        $response->header('X-Correlation-ID', $correlationId);
        $response->header('X-Request-ID', $correlationId);

        return $response;
    }
}
