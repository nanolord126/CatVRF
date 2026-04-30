<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Services\Security\ContinuousAuthService;
use App\Services\Security\TrustDecayEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Log\LogManager;
use App\Models\BehavioralDataPoint;
use App\Models\SessionRiskScore;

final class ContinuousAuthController extends Controller
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuthManager $auth,
        private readonly ContinuousAuthService $continuousAuth,
        private readonly TrustDecayEngine $trustDecay,
        private readonly LogManager $log,) {}

    /**
     * Get current session risk score
     */
    public function getSessionRisk(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $riskData = $this->continuousAuth->getSessionRisk($sessionId);

        if (! $riskData) {
            return new JsonResponse([
                'error' => 'session_not_monitored',
                'message' => 'This session is not being monitored for continuous authentication',
            ], 404);
        }

        return new JsonResponse([
            'session_id' => $riskData['session_id'],
            'user_id' => $riskData['user_id'],
            'overall_risk_score' => $riskData['overall_risk_score'],
            'risk_level' => $riskData['risk_level'],
            'trust_score' => $riskData['trust_score'],
            'challenge_required' => $riskData['challenge_required'],
            'challenge_type' => $riskData['challenge_type'],
            'terminated' => $riskData['terminated'],
            'last_scored_at' => $riskData['last_scored_at'],
        ]);
    }

    /**
     * Get behavioral data points (for debugging)
     */
    public function getBehavioralData(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(60)
            ->notExpired()
            ->orderByDesc('collected_at')
            ->limit(100)
            ->get();

        return new JsonResponse([
            'session_id' => $sessionId,
            'data_points_count' => $dataPoints->count(),
            'data_points' => $dataPoints->map(function ($point) {
                return [
                    'action' => $point->action,
                    'collected_at' => $point->collected_at,
                    'ip_address' => $point->ip_address,
                ];
            }),
        ]);
    }

    /**
     * Trigger manual challenge (for testing)
     */
    public function triggerChallenge(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        if (! config('continuous_auth.enabled', false)) {
            return new JsonResponse(['error' => 'continuous_auth_disabled'], 400);
        }

        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore) {
            return new JsonResponse(['error' => 'session_not_monitored'], 404);
        }

        // Force challenge required
        $riskScore->update([
            'challenge_required' => true,
            'challenge_type' => 'passkey_liveness',
            'challenge_triggered_at' => CarbonImmutable::now(),
        ]);

        $this->log->$this->logger->info('Manual challenge triggered', [
            'session_id' => $sessionId,
            'user_id' => $userId,
        ]);

        return new JsonResponse([
            'message' => 'Challenge triggered successfully',
            'challenge_type' => 'passkey_liveness',
        ]);
    }

    /**
     * Submit challenge response
     */
    public function verifyChallenge(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $validated = $request->validate([
            'passed' => 'required|boolean',
            'challenge_type' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $result = $this->continuousAuth->executeChallenge($sessionId);

        if ($result['passed']) {
            return new JsonResponse([
                'message' => 'Challenge passed successfully',
                'session_restored' => true,
            ]);
        } else {
            return new JsonResponse([
                'error' => 'challenge_failed',
                'message' => 'Challenge verification failed',
                'reason' => $result['reason'] ?? 'unknown',
            ], 403);
        }
    }

    /**
     * Get trust score
     */
    public function getTrustScore(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $trustScore = $this->trustDecay->calculateTrust($sessionId);
        $requiresReAuth = $this->trustDecay->requiresReAuth($sessionId);
        $timeUntilReAuth = $this->trustDecay->getTimeUntilReAuth($sessionId);

        return new JsonResponse([
            'session_id' => $sessionId,
            'trust_score' => $trustScore,
            'requires_re_auth' => $requiresReAuth,
            'time_until_re_auth_minutes' => $timeUntilReAuth,
        ]);
    }

    /**
     * Start monitoring for current session
     */
    public function startMonitoring(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        if (! config('continuous_auth.enabled', false)) {
            return new JsonResponse(['error' => 'continuous_auth_disabled'], 400);
        }

        // Check if already monitoring
        if ($this->continuousAuth->isMonitoring($sessionId)) {
            return new JsonResponse([
                'message' => 'Already monitoring this session',
                'session_id' => $sessionId,
            ]);
        }

        $this->continuousAuth->startMonitoring($sessionId, $userId);

        return new JsonResponse([
            'message' => 'Monitoring started successfully',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Stop monitoring for current session
     */
    public function stopMonitoring(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $this->continuousAuth->stopMonitoring($sessionId);

        return new JsonResponse([
            'message' => 'Monitoring stopped successfully',
            'session_id' => $sessionId,
        ]);
    }
}
