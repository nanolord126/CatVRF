<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Api\ApiVerticalMiddlewareService;
use App\Services\Api\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApplyVerticalMiddleware
{
    public function __construct(
        private readonly ApiVerticalMiddlewareService $verticalService,
        private readonly ApiResponseService $apiResponse
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vertical = $this->verticalService->detectVertical($request);

        // Check rate limit for this vertical
        $rateLimitResult = $this->verticalService->checkRateLimit($request, $vertical);

        if (!$rateLimitResult['allowed']) {
            return $this->apiResponse->rateLimitExceeded(
                $rateLimitResult['retry_after'] ?? 60
            );
        }

        // Add rate limit headers to response
        $response = $next($request);

        if (method_exists($response, 'headers')) {
            $headers = $this->verticalService->getRateLimiter()->getRateLimitHeaders($rateLimitResult);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
