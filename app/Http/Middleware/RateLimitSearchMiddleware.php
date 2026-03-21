<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\RateLimitException;
use App\Services\Security\RateLimiterService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RateLimitSearchMiddleware
{
    public function __construct(
        private RateLimiterService $rateLimiter,
    ) {}
    
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        $userId = auth()->id() ?? 0;
        
        $isHeavy = $request->boolean('with_recommendations') 
                || $request->boolean('with_embeddings');
        
        if (!$this->rateLimiter->checkSearch($userId, $isHeavy, $correlationId)) {
            throw new RateLimitException();
        }
        
        return $next($request);
    }
}
