<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataMonitoringFacade;

/**
 * Self-Heal Job
 *
 * Queued job that checks pipeline health and restarts consumers
 * if Kafka lag is critical and consumers are offline.
 *
 * Scheduled via ObservabilityServiceProvider every 5 minutes.
 *
 * Features:
 * - Retry with backoff (30s, 60s) — self-healing may fail transiently
 * - Tagged for Horizon monitoring
 * - Audit-logged with correlation ID
 * - Reports restart outcome to monitoring channel
 */
class SelfHealJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    public array $tags = ['bigdata', 'self-heal'];

    /** Seconds to wait before retrying */
    public function backoff(): array
    {
        return [30, 60];
    }

    public function __construct(
        public readonly string $topic = 'bigdata_events',
        public readonly ?string $correlationId = null,
    ) {}

    public function handle(BigDataMonitoringFacade $monitoring): void
    {
        Log::info('BigData: SelfHealJob started', [
            'topic' => $this->topic,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $result = $monitoring->restartConsumers($this->topic);

            if ($result['restarted']) {
                Log::warning('BigData: SelfHealJob restarted consumers', [
                    'topic' => $this->topic,
                    'reason' => $result['reason'],
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                Log::info('BigData: SelfHealJob — no action needed', [
                    'topic' => $this->topic,
                    'reason' => $result['reason'],
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('BigData: SelfHealJob failed', [
                'topic' => $this->topic,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a permanent job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('BigData: SelfHealJob permanently failed', [
            'topic' => $this->topic,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
