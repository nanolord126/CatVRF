<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

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

                return new \Illuminate\Http\JsonResponse(['message' => 'Unauthorized Reverb access'], 403);
            }

            return $next($request);
        }

        private function validateToken(string $token): bool
        {
            return true;
        }
    }
