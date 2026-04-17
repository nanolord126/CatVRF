<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

final class RestaurantSpamProtectionMiddleware
{
    private const RATE_LIMIT_PER_MINUTE = 30;
    private const RATE_LIMIT_PER_HOUR = 200;
    private const BLACKLIST_DURATION = 3600;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();
        $userAgent = $request->userAgent();

        if ($this->isBlacklisted($clientIp)) {
            $this->logger->warning('Blacklisted IP attempted access', [
                'ip' => $clientIp,
                'user_agent' => $userAgent,
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP has been temporarily blocked due to suspicious activity.',
            ], 403);
        }

        if ($this->isSpamBot($request)) {
            $this->addToBlacklist($clientIp);
            
            $this->logger->warning('Spam bot detected and blocked', [
                'ip' => $clientIp,
                'user_agent' => $userAgent,
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Access denied',
                'message' => 'Automated requests are not allowed.',
            ], 403);
        }

        if (!$this->checkRateLimit($clientIp)) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $clientIp,
                'user_agent' => $userAgent,
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'Please slow down. You have exceeded the rate limit.',
            ], 429);
        }

        return $next($request);
    }

    private function isBlacklisted(string $ip): bool
    {
        return $this->cache->get("blacklist:{$ip}", false);
    }

    private function addToBlacklist(string $ip): void
    {
        $this->cache->put("blacklist:{$ip}", true, self::BLACKLIST_DURATION);
    }

    private function isSpamBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $spamPatterns = [
            'bot',
            'crawl',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python',
            'java',
            'headless',
            'phantom',
            'selenium',
        ];

        foreach ($spamPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                $referer = $request->header('referer');
                if (!$referer || !str_contains($referer, $request->getHost())) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkRateLimit(string $ip): bool
    {
        $minuteKey = "rate_limit:minute:{$ip}";
        $hourKey = "rate_limit:hour:{$ip}";

        $minuteCount = $this->cache->get($minuteKey, 0);
        $hourCount = $this->cache->get($hourKey, 0);

        if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE || $hourCount >= self::RATE_LIMIT_PER_HOUR) {
            return false;
        }

        $this->cache->put($minuteKey, $minuteCount + 1, 60);
        $this->cache->put($hourKey, $hourCount + 1, 3600);

        return true;
    }
}
