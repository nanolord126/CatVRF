<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BehavioralDataPoint;
use App\Models\User;
use App\Services\Behavioral\BehavioralBiometricsService;
use App\Services\Security\CooldownService;
use App\Services\Security\InsiderThreatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Enums\CooldownActionType;

/**
 * Behavioral Anomaly Detection Job
 *
 * Asynchronously processes behavioral signals for anomaly detection.
 * Integrates with Cooldown and InsiderThreat services for security response.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class BehavioralAnomalyDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $dataPointId,
        public int $userId,
        public string $sessionId,
    ) {
        $this->onQueue(config('behavioral.performance.queue_name', 'behavioral'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        BehavioralBiometricsService $behavioralService,
        CooldownService $cooldownService,
        InsiderThreatService $insiderThreatService,
    ): void {
        $startTime = microtime(true);

        try {
            $dataPoint = BehavioralDataPoint::find($this->dataPointId);
            if (! $dataPoint) {
                Log::warning('BehavioralDataPoint not found', [
                    'data_point_id' => $this->dataPointId,
                ]);

                return;
            }

            $user = User::find($this->userId);
            if (! $user) {
                Log::warning('User not found for behavioral analysis', [
                    'user_id' => $this->userId,
                ]);

                return;
            }

            // Prepare signals for analysis
            $signals = [
                'typing' => $dataPoint->typing_pattern,
                'mouse' => $dataPoint->mouse_dynamics,
                'touch' => $dataPoint->touch_gestures,
            ];

            // Analyze signals
            $result = $behavioralService->analyzeSignals(
                user: $user,
                signals: $signals,
                sessionId: $this->sessionId,
            );

            // Handle anomaly detection
            if ($result['is_anomalous']) {
                $this->handleAnomaly(
                    user: $user,
                    result: $result,
                    cooldownService: $cooldownService,
                    insiderThreatService: $insiderThreatService,
                    action: $dataPoint->action,
                );
            }

            $latencyMs = (microtime(true) - $startTime) * 1000;

            Log::info('Behavioral anomaly detection completed', [
                'user_id' => $user->id,
                'session_id' => $this->sessionId,
                'overall_score' => $result['overall_score'],
                'is_anomalous' => $result['is_anomalous'],
                'latency_ms' => round($latencyMs, 2),
            ]);
        } catch (\Throwable $e) {
            Log::error('Behavioral anomaly detection failed', [
                'data_point_id' => $this->dataPointId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->release(60); // Retry after 60 seconds
        }
    }

    /**
     * Handle detected anomaly
     */
    private function handleAnomaly(
        User $user,
        array $result,
        CooldownService $cooldownService,
        InsiderThreatService $insiderThreatService,
        string $action,
    ): void {
        $severity = $result['anomaly_severity'] ?? 'unknown';
        $score = $result['overall_score'] ?? 0.0;

        Log::warning('Behavioral anomaly detected', [
            'user_id' => $user->id,
            'severity' => $severity,
            'score' => $score,
            'action' => $action,
        ]);

        // Trigger cooldown for high/critical severity
        if (in_array($severity, ['high', 'critical'], true)) {
            $cooldownHours = $severity === 'critical' ? 24 : 1;
            
            $cooldownService->startCooldown(
                user: $user,
                actionType: CooldownActionType::BEHAVIORAL_ANOMALY,
                hours: $cooldownHours,
                reason: "Behavioral anomaly detected (severity: {$severity}, score: {$score})",
            );
        }

        // Check for insider threat (staff only)
        if ($user->role?->isBusiness() ?? false) {
            $insiderThreatService->analyzeAction(
                staff: $user,
                tenant: $user->tenant,
                actionType: $action,
                context: [
                    'behavioral_score' => $score,
                    'behavioral_severity' => $severity,
                ],
            );
        }
    }
}
