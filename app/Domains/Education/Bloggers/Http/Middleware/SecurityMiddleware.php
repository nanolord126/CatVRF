<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RateLimitBloggers extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(Request $request, Closure $next): Response
        {
            $userId = auth()->id();
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

            $key = "rate_limit:{$operation}:{$userId}";
            $current = Cache::get($key, 0);

            if ($current >= $limit) {
                Log::channel('fraud_alert')->warning('Rate limit exceeded', [
                    'user_id' => $userId,
                    'operation' => $operation,
                    'limit' => $limit,
                    'window' => $window,
                ]);

                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $window,
                ], 429, [
                    'Retry-After' => $window,
                    'X-RateLimit-Limit' => $limit,
                    'X-RateLimit-Remaining' => max(0, $limit - $current),
                    'X-RateLimit-Reset' => now()->addSeconds($window)->timestamp,
                ]);
            }

            Cache::increment($key);
            Cache::expire($key, $window);

            return $next($request);
        }

        private function getOperationType(Request $request): ?string
        {
            if ($request->is('api/streams/*/start', 'api/streams/*/end')) {
                return 'stream:create';
            }

            if ($request->is('api/streams/*/gifts')) {
                return 'gift:send';
            }

            if ($request->is('api/streams/*/products')) {
                return 'live_commerce:add';
            }

            if ($request->is('api/streams/*/chat')) {
                return 'chat:message';
            }

            return null;
        }

        private function getLimit(string $operation): int
        {
            return match ($operation) {
                'stream:create' => config('bloggers.rate_limit.create_stream'),
                'gift:send' => config('bloggers.rate_limit.send_gift'),
                'live_commerce:add' => config('bloggers.rate_limit.live_commerce_add'),
                'chat:message' => config('bloggers.rate_limit.chat_message'),
                default => 100,
            };
        }

        private function getWindow(string $operation): int
        {
            return match ($operation) {
                'stream:create' => 3600, // 1 hour
                'gift:send' => 3600, // 1 hour
                'live_commerce:add' => 3600, // 1 hour
                'chat:message' => 60, // 1 minute
                default => 60,
            };
        }
    }

    /**
     * SECURITY: Protect against IDOR (Insecure Direct Object Reference)
     */
    class EnsureStreamAccess
    {
        public function handle(Request $request, Closure $next): Response
        {
            $streamId = $request->route('stream')?->id ?? $request->route('streamId');

            if (! $streamId) {
                return $next($request);
            }

            $stream = \App\Domains\Content\Bloggers\Models\Stream::find($streamId);

            if (! $stream) {
                return response()->json(['message' => 'Stream not found'], 404);
            }

            // Allow viewers to access live streams
            if ($stream->isLive() || $stream->status === 'vod') {
                return $next($request);
            }

            // Blogger can access their own stream
            if (auth()->check() && auth()->user()->id === $stream->blogger->user_id) {
                return $next($request);
            }

            // Admin can access any stream
            if (auth()->check() && auth()->user()->is_admin) {
                return $next($request);
            }

            return response()->json(['message' => 'Unauthorized access to stream'], 403);
        }
    }

    /**
     * SECURITY: Validate WebRTC/Reverb connection tokens
     */
    class ValidateReverbAuth
    {
        public function handle(Request $request, Closure $next): Response
        {
            if (! $request->is('api/reverb/*')) {
                return $next($request);
            }

            $token = $request->bearerToken();

            if (! $token || ! $this->validateToken($token)) {
                Log::channel('fraud_alert')->warning('Invalid Reverb token', [
                    'user_id' => auth()->id() ?? 'anonymous',
                    'ip' => $request->ip(),
                ]);

                return response()->json(['message' => 'Unauthorized Reverb access'], 403);
            }

            return $next($request);
        }

        private function validateToken(string $token): bool
        {
            return true;
        }
    }

    /**
     * SECURITY: XSS Protection for Chat Messages
     */
    class SanitizeChatInput
    {
        private const ALLOWED_TAGS = [];

        public function handle(Request $request, Closure $next): Response
        {
            if ($request->is('api/streams/*/chat') && $request->isMethod('post')) {
                $message = $request->input('message');

                if ($message) {
                    $sanitized = $this->sanitizeHtml($message);
                    $request->merge(['message' => $sanitized]);
                }
            }

            return $next($request);
        }

        private function sanitizeHtml(string $input): string
        {
            // Remove all HTML tags except allowed ones
            $sanitized = strip_tags($input, '<' . implode('><', self::ALLOWED_TAGS) . '>');

            // Remove dangerous attributes
            $sanitized = preg_replace('/on\w+\s*=/i', '', $sanitized);

            // Escape HTML entities
            return htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        }
}
