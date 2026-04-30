<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use Illuminate\Support\Facades\Log;

final class CRMPerformanceService
{
    public function __construct(
        private readonly ?TracerInterface $tracer,
        private readonly ?MeterInterface $meter,
    ) {}

    public function traceOperation(string $operation, callable $callback): mixed
    {
        if (!$this->tracer) {
            return $callback();
        }

        $span = $this->tracer->spanBuilder($operation)
            ->startSpan();

        try {
            return $span->isActive()
                ? $span->recordException(fn() => $callback())
                : $callback();
        } finally {
            $span->end();
        }
    }

    public function incrementCounter(string $name, array $attributes = []): void
    {
        if (!$this->meter) return;

        $counter = $this->meter->createCounter($name);
        $counter->add(1, $attributes);
    }

    public function recordGauge(string $name, float $value, array $attributes = []): void
    {
        if (!$this->meter) return;

        $gauge = $this->meter->createObservableGauge($name);
        $gauge->observe($value, $attributes);
    }

    public function recordHistogram(string $name, float $value, array $attributes = []): void
    {
        if (!$this->meter) return;

        $histogram = $this->meter->createHistogram($name);
        $histogram->record($value, $attributes);
    }

    public function logSlowQuery(string $query, float $duration, float $threshold = 100.0): void
    {
        if ($duration > $threshold) {
            Log::warning('Slow CRM query detected', [
                'query' => $query,
                'duration_ms' => $duration,
                'threshold_ms' => $threshold,
            ]);

            $this->incrementCounter('crm_slow_queries_total', [
                'duration_bucket' => $this->getDurationBucket($duration),
            ]);
        }
    }

    private function getDurationBucket(float $duration): string
    {
        return match (true) {
            $duration < 50 => '0-50',
            $duration < 100 => '50-100',
            $duration < 500 => '100-500',
            $duration < 1000 => '500-1000',
            default => '1000+',
        };
    }
}
