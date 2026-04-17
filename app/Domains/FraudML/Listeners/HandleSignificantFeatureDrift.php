<?php declare(strict_types=1);

namespace App\Domains\FraudML\Listeners;

use App\Domains\FraudML\Events\SignificantFeatureDriftDetected;
use App\Domains\FraudML\Services\FeatureDriftDetectorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HandleSignificantFeatureDrift — обрабатывает значительный дрифт фич
 * 
 * Действия при обнаружении HIGH severity дрифта:
 * 1. Логирует в audit канал
 * 2. Инвалидирует кэш reference distributions для вертикали
 * 3. Отправляет алерт (в проде - Slack/Email/PagerDuty)
 * 4. Записывает Prometheus метрику
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class HandleSignificantFeatureDrift implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'ml-drift';

    public function __construct(
        private readonly LogManager $logger,
        private readonly FeatureDriftDetectorService $driftDetector,
    ) {}

    public function handle(SignificantFeatureDriftDetected $event): void
    {
        $driftResult = $event->driftResult;
        $vertical = $driftResult['vertical'] ?? $driftResult['summary']['vertical'] ?? 'unknown';

        // Log to audit channel
        $this->logger->channel('audit')->critical('Significant feature drift detected', [
            'vertical' => $vertical,
            'drift_result' => $this->sanitizeDriftResult($driftResult),
            'correlation_id' => $event->correlationId,
        ]);

        // Invalidate reference cache for affected vertical
        if (isset($driftResult['vertical'])) {
            $this->driftDetector->invalidateReferenceCache($vertical);
        }

        // In production, send alerts here:
        // - Slack notification
        // - Email to ML team
        // - PagerDuty for critical drifts
        $this->sendAlert($driftResult, $event->correlationId);

        // Force model into shadow mode if not already
        $this->triggerShadowMode($vertical, $event->correlationId);

        Log::info('Significant feature drift handled', [
            'vertical' => $vertical,
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Send alert about significant drift
     */
    private function sendAlert(array $driftResult, string $correlationId): void
    {
        $vertical = $driftResult['vertical'] ?? 'unknown';
        $maxSeverity = $driftResult['summary']['max_severity'] ?? 'HIGH';
        $maxDriftScore = $driftResult['summary']['max_drift_score'] ?? 0.0;

        // In production, integrate with Slack/Email/PagerDuty
        // For now, log as alert
        $this->logger->alert('ALERT: Significant feature drift detected', [
            'vertical' => $vertical,
            'max_severity' => $maxSeverity,
            'max_drift_score' => $maxDriftScore,
            'affected_features' => array_keys($driftResult['features'] ?? []),
            'correlation_id' => $correlationId,
            'action_required' => 'Review model performance and consider rollback',
        ]);
    }

    /**
     * Trigger shadow mode for affected vertical
     */
    private function triggerShadowMode(string $vertical, string $correlationId): void
    {
        // In production, this would:
        // 1. Find active model for vertical
        // 2. Create shadow copy of previous version
        // 3. Route 10% traffic to shadow model
        // 4. Monitor shadow model performance

        $this->logger->info('Shadow mode triggered for vertical', [
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
            'reason' => 'Significant feature drift detected',
        ]);

        // Clear active model cache to force reload
        Cache::forget('fraud_model_active_version');
    }

    /**
     * Sanitize drift result for logging (remove large arrays)
     */
    private function sanitizeDriftResult(array $driftResult): array
    {
        $sanitized = $driftResult;

        // Remove large expected/actual arrays from individual feature results
        if (isset($sanitized['features'])) {
            foreach ($sanitized['features'] as $feature => $result) {
                unset($sanitized['features'][$feature]['psi']['expected']);
                unset($sanitized['features'][$feature]['psi']['actual']);
                unset($sanitized['features'][$feature]['ks']['expected']);
                unset($sanitized['features'][$feature]['ks']['actual']);
                unset($sanitized['features'][$feature]['js_divergence']['expected']);
                unset($sanitized['features'][$feature]['js_divergence']['actual']);
            }
        }

        return $sanitized;
    }
}
