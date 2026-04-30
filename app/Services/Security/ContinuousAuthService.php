<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\SessionRiskScore;
use App\Models\BehavioralDataPoint;
use App\Models\User;
use App\Services\Behavioral\BehavioralBiometricsService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\PersonalData\ConsentEngine;
use App\Services\PersonalData\PersonalDataAccessAudit;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;
use App\Enums\ConsentType;

final readonly class ContinuousAuthService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BehavioralBiometricsService $behavioral,
        private readonly VoiceBiometricsService $voice,
        private readonly RiskScoreEngine $riskEngine,
        private readonly TrustDecayEngine $trustDecay,
        private readonly ChallengeManager $challengeManager,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,
        private readonly ConsentEngine $consentEngine,
        private readonly PersonalDataAccessAudit $personalDataAudit,
    ) {}

    /**
     * Start continuous monitoring for a session
     */
    public function startMonitoring(string $sessionId, int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->db->transaction(function () use ($sessionId, $user) {
            SessionRiskScore::create([
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'overall_risk_score' => 0,
                'risk_level' => 'low',
                'trust_score' => 100.0,
                'trust_reset_at' => CarbonImmutable::now(),
                'monitoring_started_at' => CarbonImmutable::now(),
            ]);

            // Cache session monitoring status
            $this->redis->setex(
                "continuous_auth:monitoring:{$sessionId}",
                86400, // 24 hours
                json_encode(['monitoring' => true, 'user_id' => $user->id])
            );

            $this->log->$this->logger->info('Continuous authentication monitoring started', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * Collect behavioral data point
     */
    public function collectBehavioralData(string $sessionId, array $data): void
    {
        $monitoringStatus = $this->isMonitoring($sessionId);
        if (! $monitoringStatus) {
            return;
        }

        $userId = $monitoringStatus['user_id'];
        $user = User::find($userId);

        // 152-FZ: Check behavioral biometric consent before collection
        if ($user && ! config('personal-data.testing_mode', false)) {
            if (! $this->consentEngine->hasConsent($user, ConsentType::BIOMETRIC_BEHAVIORAL)) {
                $this->logger->warning('Behavioral data collection blocked: no consent', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                ]);
                return;
            }
        }

        BehavioralDataPoint::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'action' => $data['action'] ?? 'unknown',
            'typing_pattern' => $data['typing_pattern'] ?? null,
            'mouse_dynamics' => $data['mouse_dynamics'] ?? null,
            'touch_gestures' => $data['touch_gestures'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'collected_at' => CarbonImmutable::now(),
            'expires_at' => CarbonImmutable::now()->addDays(30),
        ]);
    }

    /**
     * Perform risk scoring (called by background job)
     */
    public function scoreSession(string $sessionId): array
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore || $riskScore->terminated) {
            return ['status' => 'not_monitored'];
        }

        $this->fraudControl->check(
            userId: $riskScore->user_id,
            operationType: 'continuous_auth_scoring',
            amount: 0,
            correlationId: $sessionId,
        );

        // Calculate risk score
        $riskResult = $this->riskEngine->calculateRisk($sessionId);

        // Update risk score
        $riskScore->update([
            'overall_risk_score' => $riskResult['score'],
            'risk_level' => $riskResult['level'],
            'factor_scores' => $riskResult['factor_scores'],
            'behavioral_anomalies' => $riskResult['behavioral_anomalies'] ?? null,
            'geographic_anomalies' => $riskResult['geographic_anomalies'] ?? null,
            'device_anomalies' => $riskResult['device_anomalies'] ?? null,
            'last_scored_at' => CarbonImmutable::now(),
        ]);

        // Check if challenge required
        if ($riskResult['score'] >= config('continuous_auth.risk_thresholds.high', 60)) {
            $riskScore->update([
                'challenge_required' => true,
                'challenge_type' => $this->getChallengeType($riskResult['risk_level']),
                'challenge_triggered_at' => CarbonImmutable::now(),
            ]);

            // Audit log
            $this->audit->record(
                action: 'continuous_auth_challenge_triggered',
                subjectType: SessionRiskScore::class,
                subjectId: $riskScore->id,
                newValues: [
                    'risk_score' => $riskResult['score'],
                    'risk_level' => $riskResult['level'],
                    'challenge_type' => $riskScore->challenge_type,
                ],
                correlationId: $sessionId,
            );
        }

        // Terminate session if critical risk
        if ($riskResult['score'] >= config('continuous_auth.risk_thresholds.critical', 80)) {
            $this->terminateSession($sessionId, 'critical_risk');
        }

        return [
            'status' => 'scored',
            'risk_score' => $riskResult['score'],
            'risk_level' => $riskResult['level'],
            'challenge_required' => $riskScore->challenge_required,
        ];
    }

    /**
     * Check if session requires step-up challenge
     */
    public function requiresChallenge(string $sessionId): bool
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        return $riskScore &&
               $riskScore->challenge_required &&
               $riskScore->challenge_passed === null &&
               ! $riskScore->terminated;
    }

    /**
     * Execute step-up challenge
     */
    public function executeChallenge(string $sessionId): array
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore || ! $riskScore->challenge_required) {
            return ['status' => 'no_challenge_required'];
        }

        $challengeType = $riskScore->challenge_type ?? 'passkey_liveness';

        $result = $this->challengeManager->executeChallenge(
            $sessionId,
            $riskScore->risk_level,
            $challengeType
        );

        if ($result['passed']) {
            $riskScore->update([
                'challenge_passed' => true,
                'challenge_completed_at' => CarbonImmutable::now(),
                'challenge_required' => false,
                'overall_risk_score' => 0, // Reset risk after successful challenge
                'risk_level' => 'low',
            ]);

            // Reset trust score
            $this->trustDecay->resetTrust($sessionId);

            // Audit log
            $this->audit->record(
                action: 'continuous_auth_challenge_passed',
                subjectType: SessionRiskScore::class,
                subjectId: $riskScore->id,
                newValues: [
                    'challenge_type' => $challengeType,
                ],
                correlationId: $sessionId,
            );
        } else {
            $riskScore->update([
                'challenge_passed' => false,
                'challenge_completed_at' => CarbonImmutable::now(),
            ]);

            // Terminate session on failed challenge
            $this->terminateSession($sessionId, 'challenge_failed');

            // Audit log
            $this->audit->record(
                action: 'continuous_auth_challenge_failed',
                subjectType: SessionRiskScore::class,
                subjectId: $riskScore->id,
                newValues: [
                    'challenge_type' => $challengeType,
                    'reason' => $result['reason'] ?? 'unknown',
                ],
                correlationId: $sessionId,
            );
        }

        return $result;
    }

    /**
     * Stop monitoring for a session
     */
    public function stopMonitoring(string $sessionId): void
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if ($riskScore) {
            $riskScore->update([
                'monitoring_ended_at' => CarbonImmutable::now(),
                'terminated' => false,
            ]);
        }

        $this->redis->del("continuous_auth:monitoring:{$sessionId}");

        $this->log->$this->logger->info('Continuous authentication monitoring stopped', [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Terminate session due to critical risk
     */
    public function terminateSession(string $sessionId, string $reason): void
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if ($riskScore) {
            $riskScore->update([
                'terminated' => true,
                'termination_reason' => $reason,
                'terminated_at' => CarbonImmutable::now(),
                'monitoring_ended_at' => CarbonImmutable::now(),
            ]);
        }

        $this->redis->del("continuous_auth:monitoring:{$sessionId}");

        $this->log->critical('Continuous authentication session terminated', [
            'session_id' => $sessionId,
            'reason' => $reason,
        ]);

        // Audit log
        $this->audit->record(
            action: 'continuous_auth_session_terminated',
            subjectType: SessionRiskScore::class,
            subjectId: $riskScore?->id,
            newValues: [
                'reason' => $reason,
            ],
            correlationId: $sessionId,
        );
    }

    /**
     * Check if session is being monitored
     */
    public function isMonitoring(string $sessionId): ?array
    {
        $cached = $this->redis->get("continuous_auth:monitoring:{$sessionId}");

        if ($cached) {
            return json_decode($cached, true);
        }

        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if ($riskScore && $riskScore->isMonitoring()) {
            return ['monitoring' => true, 'user_id' => $riskScore->user_id];
        }

        throw new \RuntimeException('No monitoring data available');
    }

    /**
     * Check if session is terminated
     */
    public function isTerminated(string $sessionId): bool
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        return $riskScore && $riskScore->terminated;
    }

    /**
     * Get session risk score
     */
    public function getSessionRisk(string $sessionId): ?array
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore) {
            throw new \RuntimeException('Session risk score not found');
        }

        return [
            'session_id' => $riskScore->session_id,
            'user_id' => $riskScore->user_id,
            'overall_risk_score' => $riskScore->overall_risk_score,
            'risk_level' => $riskScore->risk_level,
            'trust_score' => $riskScore->trust_score,
            'challenge_required' => $riskScore->challenge_required,
            'challenge_type' => $riskScore->challenge_type,
            'terminated' => $riskScore->terminated,
            'last_scored_at' => $riskScore->last_scored_at,
        ];
    }

    /**
     * Get challenge type based on risk level
     */
    private function getChallengeType(string $riskLevel): string
    {
        $challenges = config('continuous_auth.challenges', [
            'medium_risk' => 'passkey_only',
            'high_risk' => 'passkey_liveness',
            'critical_risk' => 'passkey_liveness_voice',
        ]);

        return match ($riskLevel) {
            'medium' => $challenges['medium_risk'] ?? 'passkey_only',
            'high' => $challenges['high_risk'] ?? 'passkey_liveness',
            'critical' => $challenges['critical_risk'] ?? 'passkey_liveness_voice',
            default => 'passkey_only',
        };
    }
}
