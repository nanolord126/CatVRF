<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Enrich Request Context Middleware
 * Production 2026 CANON
 *
 * Adds context metadata to every request:
 * - IP address
 * - User agent
 * - Device fingerprint
 * - Request start time
 * - Tenant information
 *
 * This data is available throughout the request lifecycle
 * and included in audit logs
 *
 * @author CatVRF Team
 * @version 2026.03.25
 */
final class EnrichRequestContextMiddleware
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
        // Store request context
        $request->attributes->set('ip_address', $request->ip());
        $request->attributes->set('user_agent', $request->userAgent());
        $request->attributes->set('request_started_at', now());
        $request->attributes->set('method', $request->method());
        $request->attributes->set('path', $request->path());

        // Device fingerprint (if provided)
        $deviceFingerprint = $request->header('X-Device-Fingerprint');
        if ($deviceFingerprint) {
            $request->attributes->set('device_fingerprint', $deviceFingerprint);
        }

        // User context
        if ($request->user()) {
            $request->attributes->set('user_id', $request->user()->id);
        }

        // Continue request
        $response = $next($request);

        // Add response timing
        $duration = now()->diffInMilliseconds($request->get('request_started_at'));
        $response->header('X-Response-Time', $duration . 'ms');

        return $response;
    }
}
