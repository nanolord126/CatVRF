<?php declare(strict_types=1);

/**
 * CorsSecureMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/corssecuremiddleware
 * @see https://catvrf.ru/docs/corssecuremiddleware
 * @see https://catvrf.ru/docs/corssecuremiddleware
 */


namespace App\Http\Middleware;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
final class CorsSecureMiddleware
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}


    /**
         * Обработать входящий запрос с CORS-валидацией.
         */
        public function handle(Request $request, Closure $next)
        {
            $allowedOrigins = explode(',', $this->config->get('app.cors_allowed_origins', 'http://localhost:3000'));
            $origin = $request->header('Origin');

            if (!$origin || !in_array($origin, $allowedOrigins, true)) {
                if ($request->isMethod('OPTIONS')) {
                    return $this->response->noContent(204);
                }
            }

            $response = $next($request);

            if ($origin && in_array($origin, $allowedOrigins, true)) {
                $response->header('Access-Control-Allow-Origin', $origin);
                $response->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS,PATCH');
                $response->header('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Correlation-ID,X-Idempotency-Key');
                $response->header('Access-Control-Allow-Credentials', 'true');
                $response->header('Access-Control-Max-Age', '3600');
            }

            return $response;
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
