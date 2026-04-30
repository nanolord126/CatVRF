<?php

declare(strict_types=1);

namespace App\Services\Behavioral;

use Psr\Log\LoggerInterface;

use App\Models\BehavioralBaseline;
use App\Models\BehavioralSample;
use App\Models\User;
use App\Services\PersonalData\ConsentEngine;
use App\Services\PersonalData\PersonalDataAccessAudit;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use App\Enums\ConsentType;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

/**
 * Behavioral Biometrics Service
 *
 * Orchestrates behavioral biometrics analysis for continuous authentication.
 * Combines typing, mouse, touch, and session patterns for anomaly detection.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: ContinuousAuthenticationMiddleware depends on this service
 */
final readonly class BehavioralBiometricsService
{
    use WithAuditLogging;

    private const ANOMALY_THRESHOLD = 0.3;

    private const STEP_UP_THRESHOLD = 0.5;

    private const CRITICAL_THRESHOLD = 0.7;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TypingPatternService $typing,
        private readonly MouseDynamicsService $mouse,
        private readonly TouchGestureService $touch,
        private readonly MultiModalFusionService $fusion,
        private readonly LogManager $log,
        private readonly ConsentEngine $consentEngine,
        private readonly PersonalDataAccessAudit $audit,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Analyze behavioral signals from request
     *
     * @param  User  $user  User to analyze
     * @param  array  $signals  Behavioral signals (typing, mouse, touch, session)
     * @param  string  $sessionId  Session ID
     * @return array Analysis result with anomaly detection
     */
    public function analyzeSignals(
        User $user,
        array $signals,
        string $sessionId
    ): array {
        // 152-FZ: Check behavioral biometric consent before analysis
        if (! config('personal-data.testing_mode', false)) {
            $this->consentEngine->requireConsent($user, ConsentType::BIOMETRIC_BEHAVIORAL, 'behavioral_analysis');
        }

        $correlationId = uniqid('behavioral_', true);

        // Analyze each modality
        $componentScores = [];

        if (! empty($signals['typing'])) {
            $componentScores['typing'] = $this->typing->analyze($user, $signals['typing']);
        }

        if (! empty($signals['mouse'])) {
            $componentScores['mouse'] = $this->mouse->analyze($user, $signals['mouse']);
        }

        if (! empty($signals['touch'])) {
            $componentScores['touch'] = $this->touch->analyze($user, $signals['touch']);
        }

        // Session-level analysis (simple for now)
        $componentScores['session'] = $this->analyzeSession($user, $sessionId, $signals);

        // Determine available modalities
        $availableModalities = array_keys(array_filter($componentScores, fn ($s) => isset($s['score']) && $s['score'] !== null));

        // Handle missing modalities gracefully
        $adjustedScores = $this->fusion->handleMissingModalities($componentScores, $availableModalities);

        // Calculate optimal weights based on confidence
        $weights = $this->fusion->calculateWeights($componentScores);

        // Fuse modalities
        $fusionResult = $this->fusion->fuse($componentScores, $weights);

        // Determine anomaly status
        $isAnomalous = $fusionResult['fused_score'] < self::ANOMALY_THRESHOLD;
        $anomalySeverity = $this->determineAnomalySeverity($fusionResult['fused_score']);
        $requiresStepUp = $fusionResult['fused_score'] < self::STEP_UP_THRESHOLD;

        // Store analysis result
        $this->storeAnalysisResult($user, $sessionId, $componentScores, $fusionResult, $correlationId);

        $result = [
            'overall_score' => $fusionResult['fused_score'],
            'is_anomalous' => $isAnomalous,
            'anomaly_severity' => $anomalySeverity,
            'requires_step_up' => $requiresStepUp,
            'correlation_id' => $correlationId,
            'component_scores' => $adjustedScores,
            'fusion_weights' => $weights,
            'confidence' => $fusionResult['confidence'],
            'available_modalities' => $availableModalities,
            'analysis_timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];

        $this->logger->info('Behavioral analysis completed', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'overall_score' => $fusionResult['fused_score'],
            'is_anomalous' => $isAnomalous,
            'requires_step_up' => $requiresStepUp,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    /**
     * Build baseline for user (enrollment)
     *
     * @param  User  $user  User to build baseline for
     * @param  array  $samples  Behavioral samples for enrollment
     * @return bool Success
     */
    public function buildBaseline(User $user, array $samples): bool
    {
        // 152-FZ: Check behavioral biometric consent before building baseline
        if (! config('personal-data.testing_mode', false)) {
            $this->consentEngine->requireConsent($user, ConsentType::BIOMETRIC_BEHAVIORAL, 'baseline_enrollment');
        }

        try {
            $this->audit->logBiometricCollection($user, 'behavioral', 'baseline_enrollment');

            foreach ($samples as $sample) {
                $type = $sample['type'] ?? '';
                $data = $sample['data'] ?? [];

                switch ($type) {
                    case 'typing':
                        $this->typing->storeSample($user, $data);
                        break;
                    case 'mouse':
                        $this->mouse->storeSample($user, $data);
                        break;
                    case 'touch':
                        $this->touch->storeSample($user, $data);
                        break;
                }
            }

            $this->log->$this->logger->info('Baseline building completed', [
                'user_id' => $user->id,
                'sample_count' => count($samples),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log->error('Baseline building failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get baseline for user
     *
     * @param  User  $user  User to get baseline for
     * @return array Baseline data
     */
    public function getBaseline(User $user): array
    {
        $baselines = BehavioralBaseline::where('user_id', $user->id)->get();

        $result = [];

        foreach ($baselines as $baseline) {
            $result[$baseline->baseline_type] = [
                'baseline_data' => $baseline->baseline_data,
                'sample_count' => $baseline->sample_count,
                'has_enough_samples' => $baseline->hasEnoughSamples(),
                'updated_at' => $baseline->updated_at->toIso8601String(),
            ];
        }

        return $result;
    }

    /**
     * Update baseline with new samples
     *
     * @param  User  $user  User to update baseline for
     * @param  array  $newSamples  New behavioral samples
     * @return bool Success
     */
    public function updateBaseline(User $user, array $newSamples): bool
    {
        return $this->buildBaseline($user, $newSamples);
    }

    /**
     * Analyze session-level patterns
     *
     * @param  User  $user  User to analyze
     * @param  string  $sessionId  Session ID
     * @param  array  $signals  All signals
     * @return array Session analysis result
     */
    private function analyzeSession(User $user, string $sessionId, array $signals): array
    {
        // Simple session analysis: check for rapid activity bursts
        $eventCount = 0;

        foreach (['typing', 'mouse', 'touch'] as $modality) {
            if (! empty($signals[$modality]['events'])) {
                $eventCount += count($signals[$modality]['events']);
            }
        }

        // Get historical session data
        $historicalSamples = BehavioralSample::where('user_id', $user->id)
            ->where('sample_type', 'session')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($historicalSamples->isEmpty()) {
            return [
                'score' => null,
                'confidence' => 0.0,
                'baseline_available' => false,
                'message' => 'No session baseline',
            ];
        }

        $avgEventCount = $historicalSamples->avg('sample_data.event_count');
        $stdEventCount = $this->calculateStdDev($historicalSamples->pluck('sample_data.event_count')->toArray());

        if ($stdEventCount == 0) {
            $score = 1.0;
        } else {
            $difference = abs($eventCount - $avgEventCount);
            $normalizedDiff = $difference / max($stdEventCount, 1);
            $score = max(0, 1 - $normalizedDiff);
        }

        return [
            'score' => $score,
            'confidence' => min(1.0, $historicalSamples->count() / 10.0),
            'baseline_available' => true,
            'features' => [
                'event_count' => $eventCount,
                'avg_event_count' => $avgEventCount,
            ],
        ];
    }

    /**
     * Determine anomaly severity
     *
     * @param  float  $score  Fused score
     * @return string Severity
     */
    private function determineAnomalySeverity(float $score): string
    {
        if ($score < self::CRITICAL_THRESHOLD) {
            return 'critical';
        } elseif ($score < self::STEP_UP_THRESHOLD) {
            return 'high';
        } elseif ($score < self::ANOMALY_THRESHOLD) {
            return 'medium';
        }

        return 'none';
    }

    /**
     * Store analysis result
     *
     * @param  User  $user  User
     * @param  string  $sessionId  Session ID
     * @param  array  $componentScores  Component scores
     * @param  array  $fusionResult  Fusion result
     * @param  string  $correlationId  Correlation ID
     */
    private function storeAnalysisResult(
        User $user,
        string $sessionId,
        array $componentScores,
        array $fusionResult,
        string $correlationId
    ): void {
        BehavioralSample::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'sample_type' => 'session',
            'sample_data' => [
                'component_scores' => $componentScores,
                'fusion_result' => $fusionResult,
            ],
            'analyzed_at' => CarbonImmutable::now(),
            'anomaly_score' => 1.0 - $fusionResult['fused_score'],
            'is_anomalous' => $fusionResult['fused_score'] < self::ANOMALY_THRESHOLD,
        ]);
    }

    /**
     * Calculate standard deviation
     *
     * @param  array  $values  Values
     * @return float Standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0.0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }
}
