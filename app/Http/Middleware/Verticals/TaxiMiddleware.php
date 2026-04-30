<?php

declare(strict_types=1);

namespace App\Http\Middleware\Verticals;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

final class TaxiMiddleware
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') ?? uniqid('taxi-', true);
        
        $this->log->channel('audit')->info('Taxi API Request', [
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
