<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Tracing;

use Illuminate\Support\Facades\Log;

/**
 * BigData Tracing Service
 *
 * Provides OpenTelemetry tracing for the BigData pipeline.
 * Gracefully degrades when OTel SDK is not installed.
 *
 * Spans:
 * - Event tracking (producer)
 * - Kafka consumer (consumer)
 * - ClickHouse query (client)
 * - Spark job (client)
 * - Feature store computation (internal)
 *
 * Correlation IDs flow through the entire pipeline:
 * Laravel → Kafka → ClickHouse → Spark → Feature Store
 */
final class BigDataTracingService
{
    private const TRACER_NAME = 'catvrf-bigdata';
    private const SERVICE_NAME = 'catvrf-bigdata-pipeline';

    private bool $otelAvailable;

    public function __construct()
    {
        $this->otelAvailable = interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
    }

    /**
     * Start a span for event tracking
     */
    public function startEventTrackingSpan(string $eventType, string $correlationId): ?object
    {
        if (!$this->otelAvailable) {
            return null;
        }

        try {
            $tracer = $this->getTracer();
            $span = $tracer->spanBuilder("bigdata.track.{$eventType}")
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_PRODUCER)
                ->setAttribute('messaging.destination', config('bigdata.kafka.topic', 'bigdata_events'))
                ->setAttribute('messaging.system', 'kafka')
                ->setAttribute('event.type', $eventType)
                ->setAttribute('correlation.id', $correlationId)
                ->setAttribute('service.name', self::SERVICE_NAME)
                ->startSpan();

            return $span;
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to start event tracking span', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Start a span for Kafka consumer processing
     */
    public function startConsumerSpan(string $topic, string $groupId, string $correlationId): ?object
    {
        if (!$this->otelAvailable) {
            return null;
        }

        try {
            $tracer = $this->getTracer();
            $span = $tracer->spanBuilder("bigdata.consume.{$topic}")
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_CONSUMER)
                ->setAttribute('messaging.source', $topic)
                ->setAttribute('messaging.system', 'kafka')
                ->setAttribute('messaging.consumer_group', $groupId)
                ->setAttribute('correlation.id', $correlationId)
                ->setAttribute('service.name', self::SERVICE_NAME)
                ->startSpan();

            return $span;
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to start consumer span', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Start a span for ClickHouse query
     */
    public function startClickHouseSpan(string $operation, string $table, string $correlationId): ?object
    {
        if (!$this->otelAvailable) {
            return null;
        }

        try {
            $tracer = $this->getTracer();
            $span = $tracer->spanBuilder("bigdata.clickhouse.{$operation}")
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT)
                ->setAttribute('db.system', 'clickhouse')
                ->setAttribute('db.operation', $operation)
                ->setAttribute('db.table', $table)
                ->setAttribute('db.name', config('bigdata.clickhouse.database', 'catvrf_bigdata'))
                ->setAttribute('correlation.id', $correlationId)
                ->startSpan();

            return $span;
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to start ClickHouse span', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Start a span for Spark job execution
     */
    public function startSparkJobSpan(string $jobName, string $correlationId): ?object
    {
        if (!$this->otelAvailable) {
            return null;
        }

        try {
            $tracer = $this->getTracer();
            $span = $tracer->spanBuilder("bigdata.spark.{$jobName}")
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT)
                ->setAttribute('spark.job.name', $jobName)
                ->setAttribute('spark.master', config('bigdata.spark.master', 'local[*]'))
                ->setAttribute('correlation.id', $correlationId)
                ->setAttribute('service.name', self::SERVICE_NAME)
                ->startSpan();

            return $span;
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to start Spark span', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Start a span for feature store computation
     */
    public function startFeatureStoreSpan(string $featureType, string $correlationId): ?object
    {
        if (!$this->otelAvailable) {
            return null;
        }

        try {
            $tracer = $this->getTracer();
            $span = $tracer->spanBuilder("bigdata.feature_store.{$featureType}")
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
                ->setAttribute('feature_store.type', $featureType)
                ->setAttribute('correlation.id', $correlationId)
                ->startSpan();

            return $span;
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to start feature store span', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * End a span with optional error status
     */
    public function endSpan(?object $span, bool $success = true, ?string $errorMessage = null): void
    {
        if ($span === null) {
            return;
        }

        try {
            if (!$success && $errorMessage && method_exists($span, 'setAttribute')) {
                $span->setAttribute('error', true);
                $span->setAttribute('error.message', $errorMessage);
            }

            if (method_exists($span, 'end')) {
                $span->end();
            }
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to end span', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add event to an existing span
     */
    public function addSpanEvent(?object $span, string $eventName, array $attributes = []): void
    {
        if ($span === null) {
            return;
        }

        try {
            if (method_exists($span, 'addEvent')) {
                $span->addEvent($eventName, $attributes);
            }
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to add span event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get or create correlation ID from context or request headers
     */
    public function getOrCreateCorrelationId(): string
    {
        // Try from request headers
        $requestId = request()->header('X-Correlation-ID')
            ?? request()->header('X-Request-ID')
            ?? request()->header('traceparent');

        if ($requestId) {
            return is_array($requestId) ? $requestId[0] : $requestId;
        }

        // Try from current OTel context
        if ($this->otelAvailable) {
            try {
                $currentSpan = \OpenTelemetry\API\Trace\Span::getCurrent();
                if ($currentSpan && $currentSpan->getContext()->isValid()) {
                    $traceId = $currentSpan->getContext()->getTraceId();
                    if ($traceId) {
                        return $traceId;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through
            }
        }

        return uniqid('bd_', true);
    }

    /**
     * Inject trace context into Kafka message headers
     */
    public function injectTraceContext(array $messageHeaders = []): array
    {
        if (!$this->otelAvailable) {
            return $messageHeaders;
        }

        try {
            $currentSpan = \OpenTelemetry\API\Trace\Span::getCurrent();

            if ($currentSpan && $currentSpan->getContext()->isValid()) {
                $context = $currentSpan->getContext();
                $messageHeaders['traceparent'] = sprintf(
                    '00-%s-%s-01',
                    $context->getTraceId(),
                    $context->getSpanId(),
                );
                $messageHeaders['tracestate'] = 'catvrf=bigdata';
            }
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to inject trace context', ['error' => $e->getMessage()]);
        }

        return $messageHeaders;
    }

    /**
     * Get the OTel tracer instance
     */
    private function getTracer(): \OpenTelemetry\API\Trace\TracerInterface
    {
        $tracerProvider = \OpenTelemetry\API\Globals::tracerProvider();

        return $tracerProvider->getTracer(self::TRACER_NAME, '1.0.0');
    }
}
