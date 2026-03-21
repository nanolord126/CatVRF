<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\UserActivityService;

/**
 * Middleware: Track user activity in real-time
 * 
 * @package App\Http\Middleware
 */
final class TrackUserActivityMiddleware
{
    public function __construct(
        private readonly UserActivityService $activityService,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Track activity after request completes
        if (auth()->check()) {
            $this->activityService->recordActivity(
                userId: auth()->id(),
                tenantId: filament()->getTenant()?->id ?? 0,
                activity: $request->method() . ' ' . $request->path(),
                metadata: [
                    'status' => $response->status(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        }

        return $response;
    }
}
