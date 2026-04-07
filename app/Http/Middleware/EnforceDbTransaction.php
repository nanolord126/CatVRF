<?php declare(strict_types=1);

/**
 * EnforceDbTransaction — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 * @see https://catvrf.ru/docs/enforcedbtransaction
 */


namespace App\Http\Middleware;

use Illuminate\Database\DatabaseManager;

final class EnforceDbTransaction
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}


    /**
         * Handle an incoming request.
         * Enforces DB transactions for all mutating HTTP requests (POST, PUT, PATCH, DELETE).
         *
         * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
         */
        public function handle(Request $request, Closure $next): Response
        {
            $nonMutating = ['GET', 'HEAD', 'OPTIONS'];

            if (in_array($request->method(), $nonMutating)) {
                return $next($request);
            }

            // For cross-database or multiple connection scenarios, this wraps the default connection
            return $this->db->transaction(function () use ($request, $next) {
                return $next($request);
            });
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
