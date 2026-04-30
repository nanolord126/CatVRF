<?php

declare(strict_types=1);

namespace App\Traits;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\Context\Context;

/**
 * With OpenTelemetry Tracing Trait
 *
 * Provides OpenTelemetry tracing capabilities for services.
 * Follows production observability best practices.
 */
trait WithOpenTelemetryTracing
{
    /**
     * Start a new span for tracing.
     * 
     * @param string $name Span name
     * @param callable $callback Operation to execute within the span
     * @return mixed Result of the callback
     */
    protected function trace(string $name, callable $callback): mixed
    {
        $tracer = app(TracerInterface::class);
        
        $spanBuilder = $tracer->spanBuilder($name);
        
        return $this->executeWithSpan($spanBuilder, $callback);
    }

    /**
     * Start a new span with attributes.
     * 
     * @param string $name Span name
     * @param array $attributes Span attributes
     * @param callable $callback Operation to execute within the span
     * @return mixed Result of the callback
     */
    protected function traceWithAttributes(string $name, array $attributes, callable $callback): mixed
    {
        $tracer = app(TracerInterface::class);
        
        $spanBuilder = $tracer->spanBuilder($name);
        
        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, $value);
        }
        
        return $this->executeWithSpan($spanBuilder, $callback);
    }

    /**
     * Execute operation within a span.
     */
    private function executeWithSpan(SpanBuilderInterface $spanBuilder, callable $callback): mixed
    {
        $span = $spanBuilder->startSpan();
        $scope = $span->activate();
        
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
            $scope->detach();
        }
    }

    /**
     * Add attribute to current span.
     */
    protected function addSpanAttribute(string $key, mixed $value): void
    {
        $tracer = app(TracerInterface::class);
        $span = $tracer->getSpan();
        
        if ($span !== null) {
            $span->setAttribute($key, $value);
        }
    }

    /**
     * Record exception in current span.
     */
    protected function recordSpanException(\Throwable $exception): void
    {
        $tracer = app(TracerInterface::class);
        $span = $tracer->getSpan();
        
        if ($span !== null) {
            $span->recordException($exception);
            $span->setStatus('error', $exception->getMessage());
        }
    }
}
