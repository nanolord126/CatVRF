<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class EnsureApiVersion
{
    /**
     * @var array<string>
     */
    private array $supportedVersions = ['v1', 'v2'];

    public function handle(Request $request, Closure $next): mixed
    {
        $version = $this->getApiVersion($request);

        if (!in_array($version, $this->supportedVersions, true)) {
            return response()->json([
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
}
