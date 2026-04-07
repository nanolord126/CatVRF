<?php declare(strict_types=1);

/**
 * CheckGateAbility — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checkgateability
 * @see https://catvrf.ru/docs/checkgateability
 * @see https://catvrf.ru/docs/checkgateability
 * @see https://catvrf.ru/docs/checkgateability
 * @see https://catvrf.ru/docs/checkgateability
 */


namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CheckGateAbility
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Handle an incoming request.
         */
        public function handle(Request $request, Closure $next, string $ability): Response
        {
            $user = $request->user();

            if (! $user) {
                return $this->response->json(['error' => 'Unauthorized'], 401);
            }

            // Check gate
            if (! Gate::check($ability)) {
                $this->logger->channel('audit')->warning('Gate authorization failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'ability' => $ability,
                    'user_id' => $user->id,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'path' => $request->path(),
                ]);

                return $this->response->json(['error' => 'Forbidden - Insufficient permissions'], 403);
            }

            $this->logger->channel('audit')->debug('Gate authorization granted', [
                'correlation_id' => $request->header('X-Correlation-ID'),
                'ability' => $ability,
                'user_id' => $user->id,
            ]);

            return $next($request);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
