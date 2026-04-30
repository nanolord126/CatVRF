<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\ContinuousAuthService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;

final class ContinuousAuthMiddleware
{
    public function __construct(
        private readonly ContinuousAuthService $continuousAuth,
        private readonly AuthManager $auth,
        private readonly LogManager $log,
        private readonly Repository $config,
        private readonly ResponseFactory $responseFactory,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->config->get('continuous_auth.enabled', false)) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (!$userId) {
            return $next($request);
        }

        // Skip if not monitoring this session
        if (!$this->continuousAuth->isMonitoring($sessionId)) {
            return $next($request);
        }

        // Check if session is terminated
        if ($this->continuousAuth->isTerminated($sessionId)) {
            $this->auth->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->responseFactory->json([
                'error' => 'session_terminated',
                'message' => 'Your session has been terminated due to security concerns. Please login again.',
            ], 403);
        }

        // Collect behavioral data
        $this->continuousAuth->collectBehavioralData($sessionId, [
            'timestamp' => CarbonImmutable::now(),
            'action' => $request->route()?->getName() ?? $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'typing_pattern' => $request->header('X-Typing-Pattern') ? json_decode($request->header('X-Typing-Pattern'), true) : null,
            'mouse_dynamics' => $request->header('X-Mouse-Dynamics') ? json_decode($request->header('X-Mouse-Dynamics'), true) : null,
            'touch_gestures' => $request->header('X-Touch-Gestures') ? json_decode($request->header('X-Touch-Gestures'), true) : null,
            'device_fingerprint' => $request->header('X-Device-Fingerprint') ? json_decode($request->header('X-Device-Fingerprint'), true) : null,
        ]);

        // Check if challenge required
        if ($this->continuousAuth->requiresChallenge($sessionId)) {
            // In shadow mode, just log the challenge requirement
            if ($this->config->get('continuous_auth.shadow_mode', false)) {
                $this->log->info('Challenge required (shadow mode)', [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                ]);
                return $next($request);
            }

            // In production, return challenge required response
            $sessionRisk = $this->continuousAuth->getSessionRisk($sessionId);

            return $this->responseFactory->json([
                'error' => 'challenge_required',
                'message' => 'Additional verification required',
                'challenge_type' => $sessionRisk['challenge_type'] ?? 'passkey_only',
                'session_id' => $sessionId,
            ], 403);
        }

        return $next($request);
    }
}
