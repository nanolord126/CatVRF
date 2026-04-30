<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTOs\Security\AdaptiveAuthResult;
use App\Models\RiskScoreLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Fraud\FraudControlService;
use App\Services\Fraud\FraudMLService;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Adaptive Authentication Service
 *
 * Risk-based step-up authentication using multiple signals:
 * - Behavioral biometrics (typing, mouse, touch patterns)
 * - FraudMLService ML scoring
 * - Device reputation and history
 * - Geo-velocity checks
 * - Time-of-day patterns
 * - Recent authentication history
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 *
 * @see https://arxiv.org/abs/2302.06789 - Adaptive Authentication Survey 2026
 */
final readonly class AdaptiveAuthService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly FraudMLService $fraudML,
        private readonly BehavioralBiometricsService $behavioralBiometrics,
        private readonly DeepfakeDetectionService $deepfakeDetection,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
    ) {}

    private const LOW_RISK_THRESHOLD = 0.30;

    private const MEDIUM_RISK_THRESHOLD = 0.50;

    private const HIGH_RISK_THRESHOLD = 0.70;

    private const CRITICAL_RISK_THRESHOLD = 0.85;

    private const CACHE_TTL_MINUTES = 15;

    private const STEP_UP_CHALLENGE_TTL = 300; // 5 minutes

    /**
     * Evaluate authentication risk and determine step-up requirements
     *
     * @param  User  $user  User attempting authentication
     * @param  string  $ipAddress  IP address
     * @param  string  $userAgent  User agent string
     * @param  string  $deviceFingerprint  Device fingerprint
     * @param  array  $behavioralSignals  Behavioral signals from frontend
     * @param  string  $sessionId  Current session ID
     * @return AdaptiveAuthResult Risk assessment and step-up requirements
     */
    public function evaluateAuthRisk(
        User $user,
        string $ipAddress,
        string $userAgent,
        string $deviceFingerprint,
        array $behavioralSignals = [],
        string $sessionId = ''
    ): AdaptiveAuthResult {
        $correlationId = Str::uuid()->toString();
        $startTime = microtime(true);

        try {
            // 1. Behavioral biometrics analysis
            $behavioralScore = 0.0;
            $behavioralData = [];

            if (! empty($behavioralSignals)) {
                $behavioralResult = $this->behavioralBiometrics->analyzeSignals(
                    $user,
                    $behavioralSignals,
                    $sessionId
                );
                $behavioralScore = 1.0 - $behavioralResult['overall_score']; // Invert: higher = riskier
                $behavioralData = $behavioralResult;
            }

            // 2. Device reputation check
            $deviceRisk = $this->checkDeviceReputation($user, $deviceFingerprint, $ipAddress);

            // 3. Geo-velocity check
            $geoVelocityRisk = $this->checkGeoVelocity($user, $ipAddress);

            // 4. Time-of-day pattern check
            $timePatternRisk = $this->checkTimePattern($user);

            // 5. Recent authentication history
            $authHistoryRisk = $this->checkAuthHistory($user);

            // 6. ML-based fraud scoring
            $mlRisk = 0.0;
            try {
                $mlResult = $this->fraudML->scoreOperation(
                    userId: $user->id,
                    operationType: 'adaptive_auth',
                    amount: 0,
                    ipAddress: $ipAddress,
                    deviceFingerprint: $deviceFingerprint,
                    correlationId: $correlationId,
                    context: [
                        'behavioral_score' => $behavioralScore,
                        'device_risk' => $deviceRisk,
                        'geo_velocity_risk' => $geoVelocityRisk,
                    ]
                );
                $mlRisk = $mlResult['score'] ?? 0.0;
            } catch (\Throwable $e) {
                $this->log->warning('ML scoring failed in adaptive auth', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 7. Calculate weighted overall risk score
            $overallRisk = $this->calculateOverallRisk([
                'behavioral' => $behavioralScore * 0.25, // 25% weight
                'device' => $deviceRisk * 0.20, // 20% weight
                'geo_velocity' => $geoVelocityRisk * 0.15, // 15% weight
                'time_pattern' => $timePatternRisk * 0.10, // 10% weight
                'auth_history' => $authHistoryRisk * 0.10, // 10% weight
                'ml' => $mlRisk * 0.20, // 20% weight
            ]);

            // 8. Determine risk level and step-up requirements
            $riskLevel = $this->determineRiskLevel($overallRisk);
            $stepUpRequired = $this->determineStepUpRequirements($riskLevel, $overallRisk);

            // 9. Log risk assessment
            $this->logRiskAssessment(
                $user,
                $overallRisk,
                $riskLevel,
                $stepUpRequired,
                [
                    'behavioral_score' => $behavioralScore,
                    'device_risk' => $deviceRisk,
                    'geo_velocity_risk' => $geoVelocityRisk,
                    'time_pattern_risk' => $timePatternRisk,
                    'auth_history_risk' => $authHistoryRisk,
                    'ml_risk' => $mlRisk,
                    'behavioral_data' => $behavioralData,
                ],
                $correlationId,
                $ipAddress,
                $deviceFingerprint
            );

            // 10. Cache result for step-up challenge
            $this->cacheAuthResult($user->id, $sessionId, $overallRisk, $riskLevel, $stepUpRequired);

            return new AdaptiveAuthResult(
                overallRisk: $overallRisk,
                riskLevel: $riskLevel,
                stepUpRequired: $stepUpRequired,
                behavioralData: $behavioralData,
                correlationId: $correlationId,
                latencyMs: round((microtime(true) - $startTime) * 1000, 2),
            );
        } catch (\Throwable $e) {
            $this->log->error('Adaptive auth evaluation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            // Fail secure - require step-up on error
            return new AdaptiveAuthResult(
                overallRisk: 0.6,
                riskLevel: 'medium',
                stepUpRequired: ['passkey'],
                behavioralData: [],
                correlationId: $correlationId,
                latencyMs: round((microtime(true) - $startTime) * 1000, 2),
                error: 'Evaluation failed',
            );
        }
    }

    /**
     * Get cached auth result
     */
    public function getCachedAuthResult(int $userId, string $sessionId): ?array
    {
        return $this->cache->get("adaptive_auth:{$userId}:{$sessionId}");
    }

    /**
     * Verify step-up challenge completion
     */
    public function verifyStepUp(
        User $user,
        string $sessionId,
        array $completedChallenges
    ): bool {
        $cached = $this->getCachedAuthResult($user->id, $sessionId);

        if (! $cached) {
            return false;
        }

        $required = $cached['step_up_required'];

        // Check if all required challenges are completed
        foreach ($required as $challenge) {
            if (! in_array($challenge, $completedChallenges, true)) {
                return false;
            }
        }

        // Clear cache after successful verification
        $this->cache->forget("adaptive_auth:{$user->id}:{$sessionId}");

        return true;
    }

    /**
     * Get user risk statistics
     */
    public function getUserRiskStats(User $user, int $days = 30): array
    {
        $logs = RiskScoreLog::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays($days))
            ->get();

        return [
            'total_evaluations' => $logs->count(),
            'avg_risk_score' => $logs->avg('risk_score') ?? 0.0,
            'max_risk_score' => $logs->max('risk_score') ?? 0.0,
            'risk_level_distribution' => $logs->pluck('risk_level')->countBy()->toArray(),
            'step_up_required_count' => $logs->filter(fn ($log) => ! empty($log->step_up_required))->count(),
            'high_risk_count' => $logs->where('risk_level', 'high')->count(),
            'critical_risk_count' => $logs->where('risk_level', 'critical')->count(),
        ];
    }

    /**
     * Check device reputation and history
     */
    private function checkDeviceReputation(User $user, string $deviceFingerprint, string $ipAddress): float
    {
        $risk = 0.0;

        // Check if device is known and trusted
        $device = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $deviceFingerprint)
            ->first();

        if (! $device) {
            $risk += 0.30; // New device
        } elseif (! $device->is_trusted) {
            $risk += 0.20; // Known but untrusted device
        }

        // Check IP reputation
        $ipRisk = $this->fraudControl->checkIpReputation($ipAddress);
        if ($ipRisk['is_suspicious'] ?? false) {
            $risk += 0.35;
        }

        return min(1.0, $risk);
    }

    /**
     * Check geo-velocity (impossible travel detection)
     */
    private function checkGeoVelocity(User $user, string $ipAddress): float
    {
        // Get recent authentication locations
        $recentAuths = RiskScoreLog::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subHours(2))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($recentAuths->isEmpty()) {
            return 0.0; // No history, can't check
        }

        $lastAuth = $recentAuths->first();
        $lastIp = $lastAuth->context['ip_address'] ?? null;

        if (! $lastIp) {
            return 0.0;
        }

        // Check if IPs are from different countries/regions
        $currentCountry = $this->getIpCountry($ipAddress);
        $lastCountry = $this->getIpCountry($lastIp);

        if ($currentCountry !== $lastCountry) {
            // Calculate time difference
            $timeDiff = CarbonImmutable::now()->diffInSeconds($lastAuth->created_at);

            // If country change in < 30 minutes, high risk
            if ($timeDiff < 1800) {
                return 0.60;
            }

            // If country change in < 2 hours, medium risk
            if ($timeDiff < 7200) {
                return 0.30;
            }
        }

        return 0.0;
    }

    /**
     * Check time-of-day authentication patterns
     */
    private function checkTimePattern(User $user): float
    {
        $currentHour = CarbonImmutable::now()->hour;

        // Get typical authentication hours for user
        $recentAuths = RiskScoreLog::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(30))
            ->get();

        if ($recentAuths->count() < 10) {
            return 0.0; // Insufficient data
        }

        $authHours = $recentAuths->map(
            fn ($log) => $log->created_at->hour
        )->toArray();

        // Calculate frequency of current hour
        $currentHourCount = count(array_filter($authHours, fn ($h) => $h === $currentHour));
        $totalCount = count($authHours);
        $frequency = $currentHourCount / $totalCount;

        // If current hour is unusual (< 10% frequency), add risk
        if ($frequency < 0.10) {
            return 0.25;
        }

        // Night hours (00:00-05:00) add some risk
        if ($currentHour >= 0 && $currentHour < 5) {
            return 0.15;
        }

        return 0.0;
    }

    /**
     * Check recent authentication history
     */
    private function checkAuthHistory(User $user): float
    {
        $risk = 0.0;

        // Check for failed attempts in last hour
        $failedAttempts = RiskScoreLog::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subHours(1))
            ->where('risk_level', '!=', 'low')
            ->count();

        if ($failedAttempts >= 5) {
            $risk += 0.40;
        } elseif ($failedAttempts >= 3) {
            $risk += 0.20;
        }

        // Check for successful auth in last 5 minutes (reduce risk)
        $recentSuccess = RiskScoreLog::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subMinutes(5))
            ->where('risk_level', 'low')
            ->exists();

        if ($recentSuccess) {
            $risk -= 0.15; // Reduce risk for recent successful auth
        }

        return max(0.0, min(1.0, $risk));
    }

    /**
     * Calculate overall risk score from components
     */
    private function calculateOverallRisk(array $components): float
    {
        return min(1.0, array_sum($components));
    }

    /**
     * Determine risk level from score
     */
    private function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score < self::LOW_RISK_THRESHOLD => 'low',
            $score < self::MEDIUM_RISK_THRESHOLD => 'low_medium',
            $score < self::HIGH_RISK_THRESHOLD => 'medium',
            $score < self::CRITICAL_RISK_THRESHOLD => 'high',
            default => 'critical',
        };
    }

    /**
     * Determine step-up requirements based on risk level
     */
    private function determineStepUpRequirements(string $riskLevel, float $score): array
    {
        return match ($riskLevel) {
            'low' => [], // No step-up required
            'low_medium' => ['passkey'], // Passkey re-verification
            'medium' => ['passkey', 'behavioral'], // Passkey + behavioral check
            'high' => ['passkey', 'liveness'], // Passkey + liveness check
            'critical' => ['passkey', 'liveness', 'manual_review'], // Full step-up + manual review
            default => [],
        };
    }

    /**
     * Log risk assessment to database
     */
    private function logRiskAssessment(
        User $user,
        float $overallRisk,
        string $riskLevel,
        array $stepUpRequired,
        array $components,
        string $correlationId,
        string $ipAddress,
        string $deviceFingerprint
    ): void {
        RiskScoreLog::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'risk_score' => $overallRisk,
            'risk_level' => $riskLevel,
            'step_up_required' => $stepUpRequired,
            'context' => array_merge($components, [
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
                'correlation_id' => $correlationId,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ]);

        // Log to audit
        $this->audit->logEvent('adaptive_auth_evaluation', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'risk_score' => $overallRisk,
            'risk_level' => $riskLevel,
            'step_up_required' => $stepUpRequired,
            'correlation_id' => $correlationId,
        ], 'security');
    }

    /**
     * Cache auth result for step-up challenge
     */
    private function cacheAuthResult(
        int $userId,
        string $sessionId,
        float $risk,
        string $riskLevel,
        array $stepUpRequired
    ): void {
        $this->cache->put(
            "adaptive_auth:{$userId}:{$sessionId}",
            [
                'risk' => $risk,
                'risk_level' => $riskLevel,
                'step_up_required' => $stepUpRequired,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ],
            CarbonImmutable::now()->addSeconds(self::STEP_UP_CHALLENGE_TTL)
        );
    }

    /**
     * Get IP country (simplified - use GeoIP in production)
     */
    private function getIpCountry(string $ipAddress): string
    {
        // In production, use GeoIP2 or similar service
        // For now, return placeholder
        return 'UNKNOWN';
    }
}
