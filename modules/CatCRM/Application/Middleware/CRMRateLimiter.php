<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CRMRateLimiter
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, (int) $maxAttempts)) {
            return $this->buildResponse($key, (int) $maxAttempts);
        }

        $this->limiter->hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set(
            'X-RateLimit-Remaining',
            (string) $this->limiter->remaining($key, (int) $maxAttempts)
        );

        return $response;
    }

    private function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->ip() . '|' . $request->user()?->id . '|' . $request->route()?->getName()
        );
    }

    private function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }
}
