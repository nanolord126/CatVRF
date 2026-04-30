<?php

declare(strict_types=1);

namespace App\Traits;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Psr\Log\LoggerInterface;

trait WithTelemetry
{
    protected ?TracerInterface $tracer = null;

    protected ?SpanInterface $currentSpan = null;

    /**
     * Initialize the tracer.
     */
    protected function initTracer(string $serviceName, ?TracerProviderInterface $tracerProvider = null): void
    {
        if (! config('telemetry.enabled', false)) {
            return;
        }

        if ($tracerProvider === null) {
            $tracerProvider = app(TracerProviderInterface::class);
        }

        $this->tracer = $tracerProvider->getTracer($serviceName);
    }

    /**
     * Start a new span.
     */
    protected function startSpan(
        string $spanName,
        array $attributes = [],
        ?string $spanKind = SpanKind::KIND_INTERNAL,
    ): SpanInterface {
        if (! config('telemetry.enabled', false) || $this->tracer === null) {
            return new class implements SpanInterface {
                public function addEvent(string $name, iterable $attributes = [], int $timestamp = 0): SpanInterface { return $this; }
                public function recordException(\Throwable $exception, iterable $attributes = []): SpanInterface { return $this; }
                public function setStatus(string $code, ?string $description = null): SpanInterface { return $this; }
                public function setAttribute(string $key, $value): SpanInterface { return $this; }
                public function setAttributes(iterable $attributes): SpanInterface { return $this; }
                public function end(int $timestamp = null): void {}
                public function getContext(): Context { return Context::getCurrent(); }
                public function isRecording(): bool { return false; }
                public function getSpanName(): string { return ''; }
                public function getParentSpanId(): string { return ''; }
                public function getSpanContext(): \OpenTelemetry\API\Trace\SpanContextInterface { return \OpenTelemetry\API\Trace\SpanContextInterface::INVALID; }
                public function getDuration(): int { return 0; }
                public function getStartEpochNanos(): int { return 0; }
                public function getEndEpochNanos(): int { return 0; }
                public function getInstrumentationScope(): \OpenTelemetry\API\Common\InstrumentationScopeInterface { 
                    return new class implements \OpenTelemetry\API\Common\InstrumentationScopeInterface {
                        public function getName(): string { return ''; }
                        public function getVersion(): ?string { return null; }
                        public function getSchemaUrl(): ?string { return null; }
                        public function getAttributes(): iterable { return []; }
                    };
                }
                public function getLinks(): iterable { return []; }
                public function getKind(): int { return 0; }
                public function getResource(): \OpenTelemetry\API\Common\Resource\ResourceInterface {
                    return \OpenTelemetry\API\Common\Resource\Resource::empty();
                }
            };
        }

        $builder = $this->tracer->spanBuilder($spanName)
            ->setSpanKind($spanKind)
            ->setStartTimestamp(microtime(true) * 1e9);

        foreach ($attributes as $key => $value) {
            $builder->setAttribute($key, $value);
        }

        $this->currentSpan = $builder->startSpan();

        return $this->currentSpan;
    }

    /**
     * End the current span.
     */
    protected function endSpan(): void
    {
        if ($this->currentSpan !== null) {
            $this->currentSpan->end();
            $this->currentSpan = null;
        }
    }

    /**
     * Execute a callback within a span.
     */
    protected function withSpan(
        string $spanName,
        callable $callback,
        array $attributes = [],
        ?string $spanKind = SpanKind::KIND_INTERNAL,
    ) {
        $span = $this->startSpan($spanName, $attributes, $spanKind);

        try {
            $result = $callback();
            $span->setStatus('ok');
            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus('error', $e->getMessage());
            throw $e;
        } finally {
            $span->end();
        }
    }

    /**
     * Add attributes to the current span.
     */
    protected function addSpanAttributes(array $attributes): void
    {
        if ($this->currentSpan !== null) {
            $this->currentSpan->setAttributes($attributes);
        }
    }

    /**
     * Record an exception in the current span.
     */
    protected function recordSpanException(\Throwable $exception): void
    {
        if ($this->currentSpan !== null) {
            $this->currentSpan->recordException($exception);
            $this->currentSpan->setStatus('error', $exception->getMessage());
        }
    }

    /**
     * Add an event to the current span.
     */
    protected function addSpanEvent(string $name, array $attributes = []): void
    {
        if ($this->currentSpan !== null) {
            $this->currentSpan->addEvent($name, $attributes);
        }
    }

    /**
     * Get standard attributes for a service operation.
     */
    protected function getStandardAttributes(
        string $vertical,
        string $operation,
        ?string $userId = null,
        ?string $tenantId = null,
        ?string $correlationId = null,
    ): array {
        return [
            'vertical' => $vertical,
            'operation' => $operation,
            'service.name' => config('telemetry.service_name'),
            'service.version' => config('telemetry.service_version'),
            'deployment.environment' => config('telemetry.deployment_environment'),
            'user.id' => $userId,
            'tenant.id' => $tenantId,
            'correlation.id' => $correlationId,
        ];
    }
}
