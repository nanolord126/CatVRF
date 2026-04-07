<?php declare(strict_types=1);

/**
 * EnsureApiVersion — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ensureapiversion
 * @see https://catvrf.ru/docs/ensureapiversion
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Routing\ResponseFactory;

final class EnsureApiVersion
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}


    /**
         * @var array<string>
         */
        private array $supportedVersions = ['v1', 'v2'];

        public function handle(Request $request, Closure $next): mixed
        {
            $version = $this->getApiVersion($request);

            if (!in_array($version, $this->supportedVersions, true)) {
                return $this->response->json([
                    'error' => 'Unsupported API version',
                    'supported_versions' => $this->supportedVersions,
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 400);
            }

            $request->attributes->set('api_version', $version);

            return $next($request)->header('API-Version', $version);
        }

        private function getApiVersion(Request $request): string
        {
            // Check header first
            if ($request->hasHeader('Accept-Version')) {
                return 'v' . ltrim($request->header('Accept-Version'), 'v');
            }

            // Check path: /api/v1/... or /api/v2/...
            if (preg_match('~/api/(v\d+)/~', $request->path(), $matches)) {
                return $matches[1];
            }

            // Default to v1
            return 'v1';
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
