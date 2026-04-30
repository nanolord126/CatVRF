<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApiVersioning
{
    public function __construct()
    {
    }

    /**
     * Handle an incoming request with API version detection
     *
     * Supports version detection via:
     * 1. URL path (/api/v1/..., /api/v2/...)
     * 2. Accept header (application/vnd.catvrf.v1+json)
     * 3. X-API-Version header
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->detectVersion($request);

        if (!$version) {
            Log::channel('security')->warning('API version not detected, defaulting to v1', [
                'path' => $request->path(),
                'accept' => $request->header('Accept'),
                'x-api-version' => $request->header('X-API-Version'),
            ]);

            $version = 'v1';
        }

        // Store version in request for later use
        $request->attributes->set('api_version', $version);

        Log::channel('audit')->debug('API version detected', [
            'version' => $version,
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Detect API version from request
     *
     * Priority:
     * 1. URL path (/api/v1/)
     * 2. Accept header (application/vnd.catvrf.v1+json)
     * 3. X-API-Version header
     *
     * @return string|null
     */
    private function detectVersion(Request $request): ?string
    {
        // Check URL path first (highest priority)
        $path = $request->path();
        if (preg_match('#/api/v(\d+)/#', $path, $matches)) {
            return 'v' . $matches[1];
        }

        // Check Accept header
        $accept = $request->header('Accept');
        if ($accept && preg_match('#application/vnd\.catvrf\.v(\d+)\+json#', $accept, $matches)) {
            return 'v' . $matches[1];
        }

        // Check X-API-Version header
        $headerVersion = $request->header('X-API-Version');
        if ($headerVersion) {
            // Normalize version (1 -> v1, v1 -> v1)
            if (preg_match('/^v?(\d+)$/', $headerVersion, $matches)) {
                return 'v' . $matches[1];
            }
        }

        return null;
    }
}
