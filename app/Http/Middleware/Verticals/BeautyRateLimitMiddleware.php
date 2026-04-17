<?php declare(strict_types=1);

namespace App\Http\Middleware\Verticals;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class BeautyRateLimitMiddleware
{
    private const B2C_LIMIT = 30;
    private const B2B_LIMIT = 100;
    private const WINDOW_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        $isB2b = $request->has('inn') && $request->has('business_card_id');
        
        if (!$userId) {
            return $next($request);
        }

        $limit = $isB2b ? self::B2B_LIMIT : self::B2C_LIMIT;
        $key = "beauty_rate_limit:{$userId}:{$isB2b}:" . now()->format('Y-m-d-H-i');

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            Log::channel('audit')->warning('beauty.rate_limit.exceeded', [
                'user_id' => $userId,
                'is_b2b' => $isB2b,
                'current' => $current,
                'limit' => $limit,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => self::WINDOW_SECONDS - (now()->second % 60),
            ], 429);
        }

        Cache::put($key, $current + 1, self::WINDOW_SECONDS);

        Log::channel('audit')->debug('beauty.rate_limit.checked', [
            'user_id' => $userId,
            'is_b2b' => $isB2b,
            'current' => $current + 1,
            'limit' => $limit,
        ]);

        return $next($request);
    }
}
