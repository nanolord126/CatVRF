<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
     * SECURITY: Protect against IDOR (Insecure Direct Object Reference)
     */
final class EnsureStreamAccess
{
        public function handle(Request $request, Closure $next): Response
        {
            $streamId = $request->route('stream')?->id ?? $request->route('streamId');

            if (! $streamId) {
                return $next($request);
            }

            $stream = \App\Domains\Content\Bloggers\Models\Stream::find($streamId);

            if (! $stream) {
                return new \Illuminate\Http\JsonResponse(['message' => 'Stream not found'], 404);
            }

            // Allow viewers to access live streams
            if ($stream->isLive() || $stream->status === 'vod') {
                return $next($request);
            }

            // Blogger can access their own stream
            if ($this->guard->check() && $this->guard->user()->id === $stream->blogger->user_id) {
                return $next($request);
            }

            // Admin can access any stream
            if ($this->guard->check() && $this->guard->user()->is_admin) {
                return $next($request);
            }

            return new \Illuminate\Http\JsonResponse(['message' => 'Unauthorized access to stream'], 403);
        }
    }
