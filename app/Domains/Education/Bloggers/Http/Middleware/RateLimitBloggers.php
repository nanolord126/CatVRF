<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class RateLimitBloggers
{
    public function __construct(
        private readonly Request $request,
        private readonly Guard $guard,private readonly \Illuminate\Cache\CacheManager $cache,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger) {}


    public function handle(Request $request, Closure $next): Response
        {
            $userId = $this->guard->id();
            if (! $userId) {
                return $next($request);
            }

            // Rate limit by operation type
            $operation = $this->getOperationType($request);

            if (! $operation) {
                return $next($request);
            }

            $limit = $this->getLimit($operation);
            $window = $this->getWindow($operation);

            $key = "rate_limit:{$operation}:{$userId}
