<?php

namespace App\Services\Infrastructure;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Cache\RateLimiting\Limit;

class RateLimiterService
{
    /**
     * Configure global and tenant-aware rate limits.
     */
    public static function configure(): void
    {
        // 1. Глобальный API лимит (Zero Trust 2026)
        RateLimiterFacade::for('api.v1', function (Request $request) {
            $tenantId = $request->header('X-Tenant-Id') ?: 'global';
            
            return [
                // Лимит на IP (защита от DDoS)
                Limit::perMinute(5000)->by($request->ip())->response(function () {
                    return response('IP Rate Limit Exceeded', 429);
                }),
                
                // Лимит на Тенанта (Resource Scoping)
                Limit::perMinute(20000)->by($tenantId)->response(function () {
                    return response('Tenant Quota Exceeded', 429);
                }),
            ];
        });

        // 2. Лимит для AI-запросов (OpenAI/Embeddings) - дорогостоящие операции
        RateLimiterFacade::for('ai.inference', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        // 3. Лимит для платежных транзакций (Protect against brute-force)
        RateLimiterFacade::for('transactions', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
