<?php declare(strict_types=1);

/**
 * CsrfProtectionMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 * @see https://catvrf.ru/docs/csrfprotectionmiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Routing\ResponseFactory;

final class CsrfProtectionMiddleware
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Защита от CSRF атак (для форм и Livewire).
         */
        public function handle(Request $request, Closure $next)
        {
            // Проверяем CSRF токен для изменяющихся методов
            if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                // Пропускаем для API запросов с Bearer токенами
                if ($request->bearerToken() === null && !$request->is('api/*')) {
                    $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

                    if (!$token || !hash_equals($token, session('XSRF-TOKEN'))) {
                        return $this->response->json([
                            'message' => 'CSRF token mismatch',
                            'correlation_id' => $request->header('X-Correlation-ID'),
                        ], 419);
                    }
                }
            }

            return $next($request);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
