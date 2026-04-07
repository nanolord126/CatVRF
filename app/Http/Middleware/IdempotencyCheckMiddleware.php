<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * IdempotencyCheckMiddleware — Детекция дубликатов платежей
 *
 * Production 2026 CANON
 *
 * Обеспечивает идемпотентность для критичных операций (платежи, выводы).
 * Использует Idempotency-Key header + SHA-256 hash payload.
 *
 * Дубликат = одинаковая Idempotency-Key + одинаковый payload
 * При дублировании: возврат кэшированного ответа (не повторить операцию)
 *
 * Хранение: Redis cache (payment_idempotency:{key}:{hash})
 * TTL: 24 часа (достаточно для любого платежа)
 *
 * ✓ Middleware execution order: 4th (correlation-id → auth → tenant → idempotency-check → b2c-b2b → fraud-check → rate-limit → age-verify)
 *
 * @author CatVRF Team
 * @version 2026.03.29
 */
final class IdempotencyCheckMiddleware
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly Guard $guard,
    ) {}

    /**
     * Handle the request - проверить idempotency перед обработкой
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Только для операций изменения (POST, PATCH, PUT, DELETE)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $correlationId = $request->attributes->get('correlation_id')
            ?? $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        // Получить Idempotency-Key из header
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? $request->header('idempotency-key');

        // Если нет Idempotency-Key - пропустить (не обязателен для всех операций)
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Валидировать формат (должен быть UUID или алфанумерический)
        if (!$this->isValidIdempotencyKey($idempotencyKey)) {
            return $this->response->json([
                'error' => 'Invalid Idempotency-Key format. Must be UUID or alphanumeric string.',
                'correlation_id' => $correlationId,
            ], 400);
        }

        // Вычислить SHA-256 hash payload (тело запроса)
        $payloadHash = hash('sha256', json_encode($request->all()));

        // Сформировать Redis key
        $cacheKey = "idempotency:" . $idempotencyKey . ":" . $payloadHash;

        // Проверить, есть ли уже такой запрос в кэше
        if ($this->cache->has($cacheKey)) {
            $cachedResponse = $this->cache->get($cacheKey);

            $this->logger->channel('audit')->info('Idempotency: Duplicate request detected', [
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'user_id' => $this->guard->id(),
                'correlation_id' => $correlationId,
            ]);

            // Вернуть кэшированный ответ (не повторять операцию)
            return $this->response->json($cachedResponse['body'], $cachedResponse['status']);
        }

        // Запрос новый - обработать
        $response = $next($request);

        // Если это успешный ответ (2xx или 4xx) - закэшировать
        if ($response->getStatusCode() < 500) {
            $responseData = [
                'body' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ];

            // Сохранить в cache на 24 часа
            $this->cache->put($cacheKey, $responseData, now()->addHours(24));

            $this->logger->channel('audit')->debug('Idempotency: Response cached', [
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'status' => $response->getStatusCode(),
                'ttl_hours' => 24,
                'correlation_id' => $correlationId,
            ]);
        }

        return $response;
    }

    /**
     * Валидировать формат Idempotency-Key
     *
     * Должен быть:
     * - UUID (550e8400-e29b-41d4-a716-446655440000)
     * - Или алфанумерическая строка (a-z, A-Z, 0-9, -, _)
     * - От 1 до 128 символов
     */
    private function isValidIdempotencyKey(string $key): bool
    {
        // Проверить длину
        if (strlen($key) < 1 || strlen($key) > 128) {
            return false;
        }

        // Проверить формат (UUID или алфанумерический)
        if (!preg_match('/^[a-zA-Z0-9\-_]{1,128}$/', $key)) {
            return false;
        }

        return true;
    }
}
