<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
     * SECURITY: XSS Protection for Chat Messages
     */
final class SanitizeChatInput
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
