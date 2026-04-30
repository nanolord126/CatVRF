<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SessionRiskScore;
use App\Models\BehavioralDataPoint;
use App\Services\Behavioral\BehavioralBiometricsService;
use Carbon\CarbonImmutable;

final readonly class RiskScoreEngine
{
    private const TYPING_SPEED_THRESHOLD = 300; // Characters per minute threshold for anomaly detection

    public function __construct(
        private readonly BehavioralBiometricsService $behavioralDetector,
    ) {}

    /**
     * Calculate composite risk score
     */
    public function calculateRisk(string $sessionId): array
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore) {
            return ['score' => 0, 'level' => 'low', 'factor_scores' => []];
        }

        // Get individual factor scores
        $factorScores = $this->getFactorScores($sessionId, $riskScore->user_id);

        // Calculate composite score (weighted average)
        $compositeScore = $this->calculateCompositeScore($factorScores);

        // Determine risk level
        $riskLevel = $this->determineRiskLevel($compositeScore);

        return [
            'score' => $compositeScore,
            'level' => $riskLevel,
            'factor_scores' => $factorScores,
            'behavioral_anomalies' => $factorScores['behavioral']['anomalies'] ?? null,
            'geographic_anomalies' => $factorScores['geographic']['anomalies'] ?? null,
            'device_anomalies' => $factorScores['device']['anomalies'] ?? null,
        ];
    }

    /**
     * Get individual risk factor scores
     */
    public function getFactorScores(string $sessionId, int $userId): array
    {
        // Behavioral Anomaly (40% weight)
        $behavioralScore = $this->calculateBehavioralScore($sessionId, $userId);

        // Geographic Anomaly (20% weight)
        $geographicScore = $this->calculateGeographicScore($sessionId, $userId);

        // Device Anomaly (15% weight)
        $deviceScore = $this->calculateDeviceScore($sessionId, $userId);

        // Temporal Anomaly (10% weight)
        $temporalScore = $this->calculateTemporalScore($sessionId, $userId);

        // Velocity Anomaly (10% weight)
        $velocityScore = $this->calculateVelocityScore($sessionId, $userId);

        // Trust Decay (5% weight)
        $trustDecayScore = $this->calculateTrustDecayScore($sessionId);

        return [
            'behavioral' => $behavioralScore,
            'geographic' => $geographicScore,
            'device' => $deviceScore,
            'temporal' => $temporalScore,
            'velocity' => $velocityScore,
            'trust_decay' => $trustDecayScore,
        ];
    }

    /**
     * Calculate composite score from factor scores
     */
    private function calculateCompositeScore(array $factorScores): int
    {
        $weights = [
            'behavioral' => 0.40,
            'geographic' => 0.20,
            'device' => 0.15,
            'temporal' => 0.10,
            'velocity' => 0.10,
            'trust_decay' => 0.05,
        ];

        $composite = 0;
        foreach ($factorScores as $factor => $score) {
            $composite += ($score['score'] ?? 0) * ($weights[$factor] ?? 0);
        }

        return (int) round($composite);
    }

    /**
     * Determine risk level from score
     */
    private function determineRiskLevel(int $score): string
    {
        $thresholds = config('continuous_auth.risk_thresholds', [
            'medium' => 30,
            'high' => 60,
            'critical' => 80,
        ]);

        return match (true) {
            $score >= $thresholds['critical'] => 'critical',
            $score >= $thresholds['high'] => 'high',
            $score >= $thresholds['medium'] => 'medium',
            default => 'low',
        };
    }

    /**
     * Calculate behavioral anomaly score
     */
    private function calculateBehavioralScore(string $sessionId, int $userId): array
    {
        // Get recent behavioral data points
        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(30)
            ->notExpired()
            ->get();

        if ($dataPoints->isEmpty()) {
            return ['score' => 0, 'anomalies' => []];
        }

        // Analyze typing patterns
        $typingAnomalies = $this->analyzeTypingPatterns($dataPoints, $userId);

        // Analyze mouse dynamics
        $mouseAnomalies = $this->analyzeMouseDynamics($dataPoints, $userId);

        // Analyze touch gestures
        $touchAnomalies = $this->analyzeTouchGestures($dataPoints, $userId);

        $allAnomalies = array_merge($typingAnomalies, $mouseAnomalies, $touchAnomalies);

        // Calculate score based on anomaly count and severity
        $score = min(count($allAnomalies) * 20, 100);

        return [
            'score' => $score,
            'anomalies' => $allAnomalies,
        ];
    }

    /**
     * Calculate geographic anomaly score
     */
    private function calculateGeographicScore(string $sessionId, int $userId): array
    {
        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(30)
            ->notExpired()
            ->get();

        if ($dataPoints->isEmpty()) {
            return ['score' => 0, 'anomalies' => []];
        }

        $ipAddresses = $dataPoints->pluck('ip_address')->unique()->filter()->values();
        $anomalies = [];

        if ($ipAddresses->count() > 1) {
            $anomalies[] = 'multiple_ip_addresses';
            $score = 50;
        } else {
            $score = 0;
        }

        // Check for IP change from baseline
        $baselineIp = $this->getBaselineIpAddress($userId);
        if ($baselineIp && ! $ipAddresses->contains($baselineIp)) {
            $anomalies[] = 'ip_change_from_baseline';
            $score = max($score, 70);
        }

        return [
            'score' => $score,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Calculate device anomaly score
     */
    private function calculateDeviceScore(string $sessionId, int $userId): array
    {
        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(30)
            ->notExpired()
            ->get();

        if ($dataPoints->isEmpty()) {
            return ['score' => 0, 'anomalies' => []];
        }

        $userAgents = $dataPoints->pluck('user_agent')->unique()->filter()->values();
        $anomalies = [];

        if ($userAgents->count() > 1) {
            $anomalies[] = 'multiple_user_agents';
            $score = 50;
        } else {
            $score = 0;
        }

        // Check for device fingerprint change
        $fingerprints = $dataPoints->pluck('device_fingerprint')->unique()->filter()->values();
        if ($fingerprints->count() > 1) {
            $anomalies[] = 'device_fingerprint_change';
            $score = max($score, 70);
        }

        return [
            'score' => $score,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Calculate temporal anomaly score
     */
    private function calculateTemporalScore(string $sessionId, int $userId): array
    {
        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(30)
            ->notExpired()
            ->get();

        if ($dataPoints->isEmpty()) {
            return ['score' => 0, 'anomalies' => []];
        }

        $anomalies = [];
        $score = 0;

        // Check for unusual time of activity (2 AM - 6 AM)
        $currentHour = CarbonImmutable::now()->hour;
        if ($currentHour >= 2 && $currentHour < 6) {
            $anomalies[] = 'unusual_time_activity';
            $score = 30;
        }

        return [
            'score' => $score,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Calculate velocity anomaly score
     */
    private function calculateVelocityScore(string $sessionId, int $userId): array
    {
        $dataPoints = BehavioralDataPoint::forSession($sessionId)
            ->recent(5) // Last 5 minutes
            ->notExpired()
            ->get();

        if ($dataPoints->isEmpty()) {
            return ['score' => 0, 'anomalies' => []];
        }

        $anomalies = [];
        $score = 0;

        // Check for rapid succession of actions
        if ($dataPoints->count() > 10) {
            $anomalies[] = 'rapid_action_velocity';
            $score = 40;
        }

        return [
            'score' => $score,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Calculate trust decay score
     */
    private function calculateTrustDecayScore(string $sessionId): array
    {
        if (! config('continuous_auth.trust_decay.enabled', true)) {
            return ['score' => 0, 'anomalies' => []];
        }

        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore || ! $riskScore->trust_reset_at) {
            return ['score' => 0, 'anomalies' => []];
        }

        $hoursSinceReset = $riskScore->trust_reset_at->diffInHours(CarbonImmutable::now());
        $decayRate = config('continuous_auth.trust_decay.decay_rate', 0.1);

        // Calculate trust decay: Trust = 100 * e^(-λ * time)
        $currentTrust = 100 * exp(-$decayRate * $hoursSinceReset);
        $trustDecay = 100 - $currentTrust;

        return [
            'score' => (int) round($trustDecay),
            'anomalies' => $trustDecay > 50 ? ['high_trust_decay'] : [],
        ];
    }

    /**
     * Analyze typing patterns for anomalies
     */
    private function analyzeTypingPatterns($dataPoints, int $userId): array
    {
        $anomalies = [];

        foreach ($dataPoints as $point) {
            if ($point->typing_pattern) {
                // Compare with baseline (simplified)
                $pattern = $point->typing_pattern;
                if (isset($pattern['speed']) && $pattern['speed'] > self::TYPING_SPEED_THRESHOLD) {
                    $anomalies[] = 'unusually_fast_typing';
                }
                if (isset($pattern['errors']) && $pattern['errors'] > 5) {
                    $anomalies[] = 'high_typing_error_rate';
                }
            }
        }

        return $anomalies;
    }

    /**
     * Analyze mouse dynamics for anomalies
     */
    private function analyzeMouseDynamics($dataPoints, int $userId): array
    {
        $anomalies = [];

        foreach ($dataPoints as $point) {
            if ($point->mouse_dynamics) {
                $dynamics = $point->mouse_dynamics;
                if (isset($dynamics['jitter']) && $dynamics['jitter'] > 50) {
                    $anomalies[] = 'high_mouse_jitter';
                }
            }
        }

        return $anomalies;
    }

    /**
     * Analyze touch gestures for anomalies
     */
    private function analyzeTouchGestures($dataPoints, int $userId): array
    {
        $anomalies = [];

        foreach ($dataPoints as $point) {
            if ($point->touch_gestures) {
                $gestures = $point->touch_gestures;
                if (isset($gestures['pressure']) && $gestures['pressure'] > 100) {
                    $anomalies[] = 'unusual_touch_pressure';
                }
            }
        }

        return $anomalies;
    }

    /**
     * Get baseline IP address for user
     */
    private function getBaselineIpAddress(int $userId): ?string
    {
        // Get most common IP from recent sessions
        return BehavioralDataPoint::forUser($userId)
            ->recent(1440) // Last 24 hours
            ->notExpired()
            ->pluck('ip_address')
            ->mode()
            ->first();
    }
}
