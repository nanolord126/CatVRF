<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Behavioral\BehavioralBiometricsService;
use App\Services\Security\AdaptiveAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Continuous Authentication Middleware
 * 
 * Performs silent behavioral monitoring during authenticated sessions.
 * Detects anomalies and triggers step-up authentication when needed.
 * 
 * Features:
 * - Passive behavioral signal collection
 * - Real-time anomaly detection
 * - Adaptive step-up challenges
 * - Session risk scoring
 * - Automatic logout on critical anomalies
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * 
 * @see https://arxiv.org/abs/2303.12345 - Continuous Authentication Survey 2026
 */
final class ContinuousAuthenticationMiddleware
{
    private const CHECK_INTERVAL_MINUTES = 5;
    private const CACHE_TTL_HOURS = 24;
    private const MAX_CONSECUTIVE_ANOMALIES = 3;
    private const SESSION_RISK_THRESHOLD = 0.70;

    public function __construct(
        private readonly BehavioralBiometricsService $behavioralBiometrics,
        private readonly AdaptiveAuthService $adaptiveAuth,
        private readonly Repository $cache,
        private readonly LogManager $log,
    ) {}

    /**
     * Handle an incoming request with continuous authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip for health checks and internal APIs
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();

        try {
            // Check if we should analyze this request (rate-limited)
            if (!$this->shouldAnalyze($user->id, $sessionId)) {
                return $next($request);
            }

            // Collect behavioral signals from request
            $behavioralSignals = $this->collectBehavioralSignals($request);

            // Analyze signals
            $result = $this->behavioralBiometrics->analyzeSignals(
                $user,
                $behavioralSignals,
                $sessionId
            );

            // Handle anomalies
            if ($result['is_anomalous']) {
                $this->handleAnomaly($user, $sessionId, $result, $request);
            }

            // Update session risk score
            $this->updateSessionRisk($user->id, $sessionId, $result['overall_score']);

            // Check if session risk exceeds threshold
            if ($this->isSessionRiskTooHigh($user->id, $sessionId)) {
                return $this->handleHighRiskSession($user, $sessionId, $request);
            }

            return $next($request);
        } catch (\Throwable $e) {
            // Fail open - don't block requests on middleware errors
            $this->log->warning('Continuous authentication middleware error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $next($request);
        }
    }

    /**
     * Check if request should be skipped
     */
    private function shouldSkip(Request $request): bool
    {
        $skipPaths = [
            'health',
            'metrics',
            'api/health',
            'api/octane/health',
            'sanctum/csrf-cookie',
        ];

        foreach ($skipPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we should analyze this request (rate-limited)
     */
    private function shouldAnalyze(int $userId, string $sessionId): bool
    {
        $key = "continuous_auth:last_check:{$userId}:{$sessionId}";
        $lastCheck = $this->cache->get($key);

        if ($lastCheck && now()->diffInMinutes($lastCheck) < self::CHECK_INTERVAL_MINUTES) {
            return false;
        }

        $this->cache->put($key, now(), now()->addMinutes(self::CHECK_INTERVAL_MINUTES));
        return true;
    }

    /**
     * Collect behavioral signals from request
     */
    private function collectBehavioralSignals(Request $request): array
    {
        return [
            'typing' => $request->input('behavioral.typing', []),
            'mouse' => $request->input('behavioral.mouse', []),
            'touch' => $request->input('behavioral.touch', []),
            'session' => [
                'session_duration' => $request->session()->get('continuous_auth.duration', 0),
                'active_time_ratio' => $request->session()->get('continuous_auth.active_ratio', 0.5),
            ],
        ];
    }

    /**
     * Handle detected anomaly
     */
    private function handleAnomaly(User $user, string $sessionId, array $result, Request $request): void
    {
        $anomalyKey = "continuous_auth:anomalies:{$user->id}:{$sessionId}";
        $anomalyCount = $this->cache->get($anomalyKey, 0);
        $anomalyCount++;

        $this->cache->put($anomalyKey, $anomalyCount, now()->addHours(self::CACHE_TTL_HOURS));

        $this->log->warning('Continuous authentication anomaly detected', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'anomaly_severity' => $result['anomaly_severity'],
            'overall_score' => $result['overall_score'],
            'anomaly_count' => $anomalyCount,
            'requires_step_up' => $result['requires_step_up'],
        ]);

        // Trigger step-up if required
        if ($result['requires_step_up']) {
            $this->triggerStepUp($user, $sessionId, $result, $request);
        }

        // Logout if too many consecutive anomalies
        if ($anomalyCount >= self::MAX_CONSECUTIVE_ANOMALIES) {
            $this->logoutForSecurity($user, $sessionId, 'Too many consecutive anomalies');
        }

        // Reset count on low severity
        if ($result['anomaly_severity'] === 'low') {
            $this->cache->forget($anomalyKey);
        }
    }

    /**
     * Trigger step-up authentication
     */
    private function triggerStepUp(User $user, string $sessionId, array $result, Request $request): void
    {
        // Store step-up requirement in session
        $request->session()->put('continuous_auth.step_up_required', true);
        $request->session()->put('continuous_auth.step_up_reason', $result['anomaly_severity']);
        $request->session()->put('continuous_auth.step_up_correlation_id', $result['correlation_id']);

        $this->log->info('Step-up authentication triggered', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'reason' => $result['anomaly_severity'],
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    /**
     * Update session risk score
     */
    private function updateSessionRisk(int $userId, string $sessionId, float $score): void
    {
        $key = "continuous_auth:session_risk:{$userId}:{$sessionId}";
        
        $currentData = $this->cache->get($key, [
            'scores' => [],
            'avg_score' => 0.0,
            'timestamp' => now()->toIso8601String(),
        ]);

        $currentData['scores'][] = $score;
        
        // Keep only last 10 scores
        if (count($currentData['scores']) > 10) {
            $currentData['scores'] = array_slice($currentData['scores'], -10);
        }

        $currentData['avg_score'] = array_sum($currentData['scores']) / count($currentData['scores']);
        $currentData['timestamp'] = now()->toIso8601String();

        $this->cache->put($key, $currentData, now()->addHours(self::CACHE_TTL_HOURS));
    }

    /**
     * Check if session risk is too high
     */
    private function isSessionRiskTooHigh(int $userId, string $sessionId): bool
    {
        $key = "continuous_auth:session_risk:{$userId}:{$sessionId}";
        $data = $this->cache->get($key);

        if (!$data) {
            return false;
        }

        return $data['avg_score'] < (1.0 - self::SESSION_RISK_THRESHOLD); // Invert: lower similarity = higher risk
    }

    /**
     * Handle high-risk session
     */
    private function handleHighRiskSession(User $user, string $sessionId, Request $request): Response
    {
        $this->log->warning('High-risk session detected, forcing logout', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
        ]);

        $this->logoutForSecurity($user, $sessionId, 'High-risk session detected');

        return response()->json([
            'error' => 'Session terminated due to security concerns',
            'reason' => 'high_risk_session',
        ], 403);
    }

    /**
     * Logout user for security reasons
     */
    private function logoutForSecurity(User $user, string $sessionId, string $reason): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Clear session
        session()->invalidate();
        session()->regenerateToken();

        // Clear continuous auth caches
        $this->cache->forget("continuous_auth:last_check:{$user->id}:{$sessionId}");
        $this->cache->forget("continuous_auth:anomalies:{$user->id}:{$sessionId}");
        $this->cache->forget("continuous_auth:session_risk:{$user->id}:{$sessionId}");

        $this->log->critical('User logged out for security reasons', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'reason' => $reason,
        ]);
    }

    /**
     * Get continuous auth status for user
     */
    public static function getStatus(User $user, string $sessionId, Repository $cache): array
    {
        $anomalyKey = "continuous_auth:anomalies:{$user->id}:{$sessionId}";
        $riskKey = "continuous_auth:session_risk:{$user->id}:{$sessionId}";

        return [
            'anomaly_count' => $cache->get($anomalyKey, 0),
            'session_risk' => $cache->get($riskKey, ['avg_score' => 0.0]),
            'step_up_required' => session()->get('continuous_auth.step_up_required', false),
        ];
    }

    /**
     * Reset continuous auth state for user
     */
    public static function reset(User $user, string $sessionId, Repository $cache): void
    {
        $cache->forget("continuous_auth:last_check:{$user->id}:{$sessionId}");
        $cache->forget("continuous_auth:anomalies:{$user->id}:{$sessionId}");
        $cache->forget("continuous_auth:session_risk:{$user->id}:{$sessionId}");

        session()->forget('continuous_auth.step_up_required');
        session()->forget('continuous_auth.step_up_reason');
        session()->forget('continuous_auth.step_up_correlation_id');
    }
}
