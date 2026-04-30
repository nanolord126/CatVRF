<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Illuminate\Http\Request;
use App\Domains\Content\Bloggers\Models\Stream;
use Illuminate\Http\JsonResponse;

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

        $stream = Stream::find($streamId);

        if (! $stream) {
            return new JsonResponse(['message' => 'Stream not found'], 404);
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

        return new JsonResponse(['message' => 'Unauthorized access to stream'], 403);
    }
}
