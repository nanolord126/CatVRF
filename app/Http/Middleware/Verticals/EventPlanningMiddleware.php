<?php

declare(strict_types=1);

namespace App\Http\Middleware\Verticals;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EventPlanningMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? uniqid('eventplanning-', true);
        
        Log::channel('audit')->info('EventPlanning API Request', [
            'correlation_id' => $correlationId,
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
