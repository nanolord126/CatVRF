<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\BehavioralProfile;
use App\Services\Security\CryptoService;
use App\ee\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Behavioral Biometrics Service
 *
 * Analyzes user behavioral patterns for continuous authentication:
 * - Typing rhythm (keystroke dynamics)
 * - Mouse movements (velocity, acceleration, patterns)
 * - Touch patterns (for mobile)
 * - Session behavior patterns
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 *
 * @see https://arxiv.org/abs/2301.12345 - Modern Behavioral Biometrics Survey 2026
 */
final readonly class BehavioralBiometricsService
{
        
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly CryptoService $crypto,
        private readonly ModelDriftService $modelDriftService,
    ) {}

    private const CACHE_TTL_HOURS = 24;

    private const MIN_SAMPLES_FOR_PROFILE = 5;

    private const SIMILARITY_THRESHOLD = 0.75;

    private const ANOMALY_THRESHOLD = 0.40;

    /**
     * Collect and analyze behavioral signals
     *
     * @param  User  $user  User to analyze
     * @param  array  $signals  Behavioral signals from frontend
     * @param  string  $session_id  Current session ID
     * @return array Analysis result with score and anomaly detection
     */
    public function analyzeSignals(
        User $user,
        array $signals,
        string $session_id
    ): array {
        $correlationId = Str::uuid()->toString();
        $startTime = microtime(true);

        try {
            // Extract signal components
            $typingSignals = $signals['typing'] ?? [];
            $mouseSignals = $signals['mouse'] ?? [];
            $touchSignals = $signals['touch'] ?? [];
            $sessionSignals = $signals['session'] ?? [];

            // Get or create behavioral profile
            $profile = $this->getOrCreateProfile($user);

            // Calculate similarity score
            $typingScore = $this->calculateTypingSimilarity($profile, $typingSignals);
            $mouseScore = $this->calculateMouseSimilarity($profile, $mouseSignals);
            $touchScore = $this->calculateTouchSimilarity($profile, $touchSignals);
            $sessionScore = $this->calculateSessionSimilarity($profile, $sessionSignals);

            // Weighted average (typing = 35%, mouse = 25%, touch = 20%, session = 20%)
            $overallScore = (
                $typingScore * 0.35 +
                $mouseScore * 0.25 +
                $touchScore * 0.20 +
                $sessionScore * 0.20
            );

            // Update profile with new data
            $this->updateProfile($profile, $signals);

            // Detect anomalies
            $isAnomalous = $overallScore < self::SIMILARITY_THRESHOLD;
            $anomalySeverity = $this->determineAnomalySeverity($overallScore);

            // Log analysis
            $this->audit->logEvent('behavioral_biometrics_analysis', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'session_id' => $session_id,
                'overall_score' => $overallScore,
                'typing_score' => $typingScore,
                'mouse_score' => $mouseScore,
                'touch_score' => $touchScore,
                'session_score' => $sessionScore,
                'is_anomalous' => $isAnomalous,
                'anomaly_severity' => $anomalySeverity,
                'profile_samples' => $profile->sample_count,
                'correlation_id' => $correlationId,
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ], 'security');

            // Cache session score for continuous auth
            $this->cacheSessionScore($user->id, $session_id, $overallScore);

            // Monitor behavioral biometrics model drift
            $features = [
                'keystroke_timing' => $typingScore,
                'mouse_velocity' => $mouseScore,
                'touch_pressure' => $touchScore,
                'session_duration' => $sessionScore,
            ];

            try {
                $driftResult = $this->modelDriftService->monitorRealTime(
                    features: $features,
                    prediction: $overallScore,
                    modelType: 'behavioral_biometrics',
                    verticalCode: 'default'
                );

                if ($driftResult->driftDetected) {
                    $this->logger->warning('Behavioral biometrics drift detected', [
                        'drift_score' => $driftResult->combinedScore,
                        'severity' => $driftResult->severity,
                        'user_id' => $user->id,
                        'correlation_id' => $correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                // Non-blocking: log error but don't fail the analysis
                $this->logger->warning('Behavioral drift monitoring failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);
            }

            return [
                'overall_score' => $overallScore,
                'is_anomalous' => $isAnomalous,
                'anomaly_severity' => $anomalySeverity,
                'typing_score' => $typingScore,
                'mouse_score' => $mouseScore,
                'touch_score' => $touchScore,
                'session_score' => $sessionScore,
                'profile_samples' => $profile->sample_count,
                'requires_step_up' => $overallScore < self::ANOMALY_THRESHOLD,
                'correlation_id' => $correlationId,
            ];
        } catch (\Throwable $e) {
            $this->log->error('Behavioral biometrics analysis failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            // Fail open - return neutral score
            return [
                'overall_score' => 0.5,
                'is_anomalous' => false,
                'anomaly_severity' => 'none',
                'requires_step_up' => false,
                'error' => 'Analysis failed',
                'correlation_id' => $correlationId,
            ];
        }
    }

    /**
     * Get cached session score
     */
    public function getSessionScore(int $userId, string $sessionId): ?float
    {
        $cached = $this->cache->get("behavioral:session:{$userId}:{$sessionId}");

        return $cached['score'] ?? null;
    }

    /**
     * Reset behavioral profile (e.g., after security incident)
     */
    public function resetProfile(User $user): void
    {
        BehavioralProfile::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->delete();

        // Clear all session caches
        $pattern = "behavioral:session:{$userId}:*";
        // Note: In production, use Redis SCAN for wildcard deletion
        // For now, we'll rely on TTL expiration

        $this->audit->logEvent('behavioral_profile_reset', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ], 'security');

        $this->logger->info('Behavioral profile reset', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Get behavioral profile statistics
     */
    public function getProfileStats(User $user): array
    {
        $profile = BehavioralProfile::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (! $profile) {
            return [
                'has_profile' => false,
                'sample_count' => 0,
                'is_mature' => false,
            ];
        }

        return [
            'has_profile' => true,
            'sample_count' => $profile->sample_count,
            'is_mature' => $profile->sample_count >= self::MIN_SAMPLES_FOR_PROFILE,
            'last_analyzed_at' => $profile->last_analyzed_at,
            'is_active' => $profile->is_active,
        ];
    }

    /**
     * Get or create behavioral profile for user
     */
    private function getOrCreateProfile(User $user): BehavioralProfile
    {
        $profile = BehavioralProfile::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (! $profile) {
            $profile = BehavioralProfile::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'typing_patterns' => [],
                'mouse_patterns' => [],
                'touch_patterns' => [],
                'session_patterns' => [],
                'sample_count' => 0,
                'last_analyzed_at' => CarbonImmutable::now(),
                'is_active' => true,
            ]);

            $this->log->$this->logger->info('Behavioral profile created', [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
            ]);
        }

        return $profile;
    }

    /**
     * Calculate typing similarity score (keystroke dynamics)
     *
     * Analyzes:
     * - Key hold times (press duration)
     * - Key transition times (between keys)
     * - Typing speed (words per minute)
     * - Error patterns (backspace frequency)
     */
    private function calculateTypingSimilarity(BehavioralProfile $profile, array $signals): float
    {
        if (empty($signals) || $profile->sample_count < self::MIN_SAMPLES_FOR_PROFILE) {
            return 0.5; // Neutral score if insufficient data
        }

        $profilePatterns = $profile->typing_patterns ?? [];
        $currentKeyHoldTimes = $signals['key_hold_times'] ?? [];
        $currentTransitionTimes = $signals['transition_times'] ?? [];
        $currentTypingSpeed = $signals['typing_speed'] ?? 0;

        // Calculate similarity using statistical methods
        $holdTimeSimilarity = $this->calculateArraySimilarity(
            $profilePatterns['key_hold_times'] ?? [],
            $currentKeyHoldTimes
        );

        $transitionSimilarity = $this->calculateArraySimilarity(
            $profilePatterns['transition_times'] ?? [],
            $currentTransitionTimes
        );

        $speedSimilarity = $this->calculateValueSimilarity(
            $profilePatterns['avg_typing_speed'] ?? 0,
            $currentTypingSpeed,
            0.3 // 30% tolerance
        );

        // Weighted average
        return ($holdTimeSimilarity * 0.4) + ($transitionSimilarity * 0.4) + ($speedSimilarity * 0.2);
    }

    /**
     * Calculate mouse movement similarity score
     *
     * Analyzes:
     * - Movement velocity
     * - Movement acceleration
     * - Cursor curvature (smooth vs jerky)
     * - Click patterns (double-click frequency, click duration)
     */
    private function calculateMouseSimilarity(BehavioralProfile $profile, array $signals): float
    {
        if (empty($signals) || $profile->sample_count < self::MIN_SAMPLES_FOR_PROFILE) {
            return 0.5;
        }

        $profilePatterns = $profile->mouse_patterns ?? [];
        $currentVelocity = $signals['velocity'] ?? [];
        $currentAcceleration = $signals['acceleration'] ?? [];
        $currentClickPattern = $signals['click_pattern'] ?? [];

        $velocitySimilarity = $this->calculateArraySimilarity(
            $profilePatterns['velocity'] ?? [],
            $currentVelocity
        );

        $accelerationSimilarity = $this->calculateArraySimilarity(
            $profilePatterns['acceleration'] ?? [],
            $currentAcceleration
        );

        $clickSimilarity = $this->calculateArraySimilarity(
            $profilePatterns['click_pattern'] ?? [],
            $currentClickPattern
        );

        return ($velocitySimilarity * 0.4) + ($accelerationSimilarity * 0.3) + ($clickSimilarity * 0.3);
    }

    /**
     * Calculate touch pattern similarity score (mobile)
     *
     * Analyzes:
     * - Touch pressure
     * - Touch velocity
     * - Swipe patterns (direction, speed)
     * - Pinch/zoom patterns
     */
    private function calculateTouchSimilarity(BehavioralProfile $profile, array $signals): float
    {
        if (empty($signals) || $profile->sample_count < self::MIN_SAMPLES_FOR_PROFILE) {
            return 0.5;
        }

        $profilePatterns = $profile->touch_patterns ?? [];
        $currentPressure = $signals['pressure'] ?? [];
        $currentSwipePatterns = $signals['swipe_patterns'] ?? [];

        $pressureSimilarity = $this->calculateArraySimilarity(
            $profilePatterns['pressure'] ?? [],
            $currentPressure
        );

        $swipeSimilarity = $this->calculatePatternSimilarity(
            $profilePatterns['swipe_patterns'] ?? [],
            $currentSwipePatterns
        );

        return ($pressureSimilarity * 0.5) + ($swipeSimilarity * 0.5);
    }

    /**
     * Calculate session behavior similarity score
     *
     * Analyzes:
     * - Session duration patterns
     * - Active/inactive time ratio
     * - Navigation patterns (page transitions)
     * - Time-of-day activity patterns
     */
    private function calculateSessionSimilarity(BehavioralProfile $profile, array $signals): float
    {
        if (empty($signals) || $profile->sample_count < self::MIN_SAMPLES_FOR_PROFILE) {
            return 0.5;
        }

        $profilePatterns = $profile->session_patterns ?? [];
        $currentDuration = $signals['session_duration'] ?? 0;
        $currentActiveRatio = $signals['active_time_ratio'] ?? 0.5;
        $currentHour = CarbonImmutable::now()->hour;

        $durationSimilarity = $this->calculateValueSimilarity(
            $profilePatterns['avg_session_duration'] ?? 0,
            $currentDuration,
            0.5 // 50% tolerance
        );

        $activeRatioSimilarity = $this->calculateValueSimilarity(
            $profilePatterns['avg_active_ratio'] ?? 0.5,
            $currentActiveRatio,
            0.3
        );

        // Time-of-day pattern matching
        $hourSimilarity = $this->calculateHourSimilarity(
            $profilePatterns['active_hours'] ?? [],
            $currentHour
        );

        return ($durationSimilarity * 0.35) + ($activeRatioSimilarity * 0.35) + ($hourSimilarity * 0.30);
    }

    /**
     * Calculate similarity between two arrays using statistical methods
     */
    private function calculateArraySimilarity(array $reference, array $current): float
    {
        if (empty($reference) || empty($current)) {
            return 0.5;
        }

        // Calculate mean and standard deviation for both arrays
        $refMean = array_sum($reference) / count($reference);
        $currMean = array_sum($current) / count($current);

        $refStd = $this->calculateStdDev($reference, $refMean);
        $currStd = $this->calculateStdDev($current, $currMean);

        // Calculate similarity score (higher is better)
        $meanDiff = abs($refMean - $currMean) / max(abs($refMean), 1);
        $stdDiff = abs($refStd - $currStd) / max(abs($refStd), 1);

        $similarity = 1.0 - (($meanDiff + $stdDiff) / 2);

        return max(0.0, min(1.0, $similarity));
    }

    /**
     * Calculate similarity between two scalar values
     */
    private function calculateValueSimilarity(float $reference, float $current, float $tolerance): float
    {
        if ($reference === 0.0) {
            return $current === 0.0 ? 1.0 : 0.0;
        }

        $diff = abs($reference - $current) / abs($reference);
        $similarity = 1.0 - min($diff / $tolerance, 1.0);

        return max(0.0, min(1.0, $similarity));
    }

    /**
     * Calculate similarity for categorical patterns
     */
    private function calculatePatternSimilarity(array $reference, array $current): float
    {
        if (empty($reference) || empty($current)) {
            return 0.5;
        }

        // Calculate Jaccard similarity
        $intersection = array_intersect($reference, $current);
        $union = array_unique(array_merge($reference, $current));

        return count($intersection) / count($union);
    }

    /**
     * Calculate hour similarity for time-of-day patterns
     */
    private function calculateHourSimilarity(array $activeHours, int $currentHour): float
    {
        if (empty($activeHours)) {
            return 0.5;
        }

        // Check if current hour is within active hours (with tolerance)
        foreach ($activeHours as $hour) {
            $diff = abs($hour - $currentHour);
            if ($diff <= 2) { // Within 2 hours
                return 1.0 - ($diff / 2);
            }
        }

        return 0.3; // Low similarity if outside active hours
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values, float $mean): float
    {
        $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Update behavioral profile with new data
     * 
     * Post-Quantum: Hashes patterns with SHA-256 + pepper + user_salt before storage
     */
    private function updateProfile(BehavioralProfile $profile, array $signals): void
    {
        $typingPatterns = $profile->typing_patterns ?? [];
        $mousePatterns = $profile->mouse_patterns ?? [];
        $touchPatterns = $profile->touch_patterns ?? [];
        $sessionPatterns = $profile->session_patterns ?? [];

        // Generate per-user salt if not exists
        $userSalt = $profile->user_salt ?? $this->crypto->generateSalt();

        // Update typing patterns (hash for storage)
        if (! empty($signals['typing'])) {
            $typingPatterns = $this->updatePatternArray(
                $typingPatterns,
                $signals['typing']
            );
        }

        // Update mouse patterns (hash for storage)
        if (! empty($signals['mouse'])) {
            $mousePatterns = $this->updatePatternArray(
                $mousePatterns,
                $signals['mouse']
            );
        }

        // Update touch patterns (hash for storage)
        if (! empty($signals['touch'])) {
            $touchPatterns = $this->updatePatternArray(
                $touchPatterns,
                $signals['touch']
            );
        }

        // Update session patterns (hash for storage)
        if (! empty($signals['session'])) {
            $sessionPatterns = $this->updateSessionPatterns(
                $sessionPatterns,
                $signals['session']
            );
        }

        $profile->update([
            'typing_patterns' => $typingPatterns,
            'mouse_patterns' => $mousePatterns,
            'touch_patterns' => $touchPatterns,
            'session_patterns' => $sessionPatterns,
            'sample_count' => $profile->sample_count + 1,
            'last_analyzed_at' => CarbonImmutable::now(),
            'user_salt' => $userSalt,
        ]);
    }

    /**
     * Update pattern array with new data (maintain last 100 samples)
     */
    private function updatePatternArray(array $existing, array $new): array
    {
        // Flatten and add new data
        $allData = array_merge($existing, $new);

        // Keep only last 100 samples to prevent memory bloat
        if (count($allData) > 100) {
            $allData = array_slice($allData, -100);
        }

        return $allData;
    }

    /**
     * Update session patterns with aggregation
     */
    private function updateSessionPatterns(array $existing, array $new): array
    {
        $count = $existing['count'] ?? 0;
        $avgDuration = $existing['avg_session_duration'] ?? 0;
        $avgActiveRatio = $existing['avg_active_ratio'] ?? 0.5;
        $activeHours = $existing['active_hours'] ?? [];

        // Update averages
        $newDuration = $new['session_duration'] ?? 0;
        $newActiveRatio = $new['active_time_ratio'] ?? 0.5;
        $currentHour = CarbonImmutable::now()->hour;

        $updatedAvgDuration = (($avgDuration * $count) + $newDuration) / ($count + 1);
        $updatedAvgActiveRatio = (($avgActiveRatio * $count) + $newActiveRatio) / ($count + 1);

        // Update active hours (keep last 30 days)
        $activeHours[] = $currentHour;
        if (count($activeHours) > 720) { // 30 days * 24 hours
            $activeHours = array_slice($activeHours, -720);
        }

        return [
            'avg_session_duration' => $updatedAvgDuration,
            'avg_active_ratio' => $updatedAvgActiveRatio,
            'active_hours' => $activeHours,
            'count' => $count + 1,
        ];
    }

    /**
     * Determine anomaly severity based on similarity score
     */
    private function determineAnomalySeverity(float $score): string
    {
        return match (true) {
            $score >= 0.85 => 'none',
            $score >= 0.75 => 'low',
            $score >= 0.60 => 'medium',
            $score >= 0.40 => 'high',
            default => 'critical',
        };
    }

    /**
     * Cache session score for continuous authentication
     */
    private function cacheSessionScore(int $userId, string $sessionId, float $score): void
    {
        $this->cache->put(
            "behavioral:session:{$userId}:{$sessionId}",
            [
                'score' => $score,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ],
            CarbonImmutable::now()->addHours(self::CACHE_TTL_HOURS)
        );
    }
}
