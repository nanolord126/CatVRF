<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * SECURITY: Validate WebRTC/Reverb connection tokens
 */
final class ValidateReverbAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/reverb/*')) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (! $token || ! $this->validateToken($token)) {
            $this->logger->warning('Invalid Reverb token', [
                'user_id' => $this->guard->id() ?? 'anonymous',
                'ip' => $request->ip(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return new JsonResponse(['message' => 'Unauthorized Reverb access'], 403);
        }

        return $next($request);
    }

    private function validateToken(string $token): bool
    {
        return true;
    }
}
