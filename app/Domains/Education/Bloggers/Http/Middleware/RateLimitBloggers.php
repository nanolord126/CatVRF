<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

final class RateLimitBloggers
{
    public function __construct(
        private readonly Guard $guard,
        private readonly \Illuminate\Cache\CacheManager $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $this->guard->id();
        if (!$userId) {
            return $next($request);
        }

        $operation = $this->getOperationType($request);

        if (!$operation) {
            return $next($request);
        }

        $limit = $this->getLimit($operation);
        $window = $this->getWindow($operation);

        $key = "rate_limit:{$operation}:{$userId}";
        $attempts = (int) $this->cache->get($key, 0);

        if ($attempts >= $limit) {
            $this->logger->warning('Blogger rate limit exceeded', [
                'user_id' => $userId,
                'operation' => $operation,
                'attempts' => $attempts,
                'limit' => $limit,
                'correlation_id' => $request->header('X-Correlation-ID', ''),
            ]);

            return new Response(
                json_encode(['error' => 'Too many requests', 'retry_after' => $window]),
                429,
                ['Content-Type' => 'application/json', 'Retry-After' => (string) $window]
            );
        }

        $this->cache->put($key, $attempts + 1, $window);

        return $next($request);
    }

    private function getOperationType(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        if (str_contains($path, 'posts') && $method === 'POST') {
            return 'blogger_post_create';
        }
        if (str_contains($path, 'comments') && $method === 'POST') {
            return 'blogger_comment_create';
        }

        return null;
    }

    private function getLimit(string $operation): int
    {
        return match ($operation) {
            'blogger_post_create' => 10,
            'blogger_comment_create' => 30,
            default => 60,
        };
    }

    private function getWindow(string $operation): int
    {
        return match ($operation) {
            'blogger_post_create' => 3600,
            'blogger_comment_create' => 600,
            default => 60,
        };
    }
}