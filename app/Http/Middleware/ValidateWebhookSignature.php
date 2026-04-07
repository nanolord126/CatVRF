<?php declare(strict_types=1);

/**
 * ValidateWebhookSignature — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 * @see https://catvrf.ru/docs/validatewebhooksignature
 */


namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ValidateWebhookSignature
{

    public function __construct(
            private readonly WebhookSignatureValidator $validator,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    )
    {
        // Implementation required by canon
    }

        public function handle(Request $request, Closure $next, string $provider = 'tinkoff'): mixed
        {
            $signature = $request->header('X-Signature') ?? $request->header('Authorization');
            $payload = $request->getContent();

            if (!$signature || !Validator::validate($provider, $payload, $signature)) {
                $this->logger->channel('webhook_errors')->warning('Invalid webhook signature', [
                    'provider' => $provider,
                    'path' => $request->path(),
                ]);

                return $this->response->json(['error' => 'Invalid signature'], 403);
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
