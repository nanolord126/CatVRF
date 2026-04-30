<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataAlertEvaluator;
use Modules\BigData\Domain\Interfaces\AlertEvaluatorInterface;

/**
 * Evaluate Alerts Job
 *
 * Queued job that runs the alert evaluator and dispatches
 * domain events for any firing alerts.
 *
 * Scheduled via ObservabilityServiceProvider every minute.
 */
class EvaluateAlertsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;
    public int $timeout = 30;
    public bool $failOnTimeout = true;

    public function __construct(
        public readonly ?string $correlationId = null,
    ) {}

    public function handle(AlertEvaluatorInterface $evaluator): void
    {
        $startTime = microtime(true);

        Log::info('BigData: EvaluateAlertsJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $results = $evaluator->evaluateAll();

            $firingCount = 0;
            $criticalCount = 0;

            foreach ($results as $result) {
                if ($result->firing) {
                    $firingCount++;

                    if ($result->severity === \Modules\BigData\Domain\Enums\AlertSeverity::Critical) {
                        $criticalCount++;
                    }

                    Log::warning("BigData alert: {$result->name}", [
                        'severity' => $result->severity->value,
                        'message' => $result->message,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            }

            $duration = round(microtime(true) - $startTime, 3);

            Log::info('BigData: EvaluateAlertsJob completed', [
                'total_alerts' => count($results),
                'firing' => $firingCount,
                'critical' => $criticalCount,
                'duration_seconds' => $duration,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BigData: EvaluateAlertsJob failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BigData: EvaluateAlertsJob permanently failed', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
