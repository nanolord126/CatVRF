declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Correlation ID Middleware
 * Production 2026 CANON
 *
 * Injects correlation_id header into every request:
 * - Uses X-Correlation-ID header if provided
 * - Generates UUID if missing
 * - Passes to response headers
 * - Enables full request tracing through all layers
 *
 * @author CatVRF Team
 * @version 2026.03.25
 */
final class CorrelationIdMiddleware
{
    /**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get correlation_id from header or generate new
        $correlationId = $request->header('X-Correlation-ID') 
            ?? $request->header('x-correlation-id')
            ?? (string)Str::uuid();

        // Validate format (UUID)
        if (!Str::isUuid($correlationId)) {
            $correlationId = (string)Str::uuid();
        }

        // Store in request for access throughout the stack
        $request->attributes->set('correlation_id', $correlationId);

        // Continue to next middleware/controller
        $response = $next($request);

        // Add correlation_id to response headers
        $response->header('X-Correlation-ID', $correlationId);
        $response->header('X-Request-ID', $correlationId);

        return $response;
    }
}
