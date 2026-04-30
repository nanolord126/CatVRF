<?php

declare(strict_types=1);

namespace App\Traits;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\Gauge;

/**
 * With Prometheus Metrics Trait
 *
 * Provides Prometheus metrics collection capabilities for services.
 * Follows production observability best practices.
 * 
 * Requires prometheus/prometheus_php package:
 * composer require promphp/prometheus_php
 */
trait WithPrometheusMetrics
{
    private ?CollectorRegistry $registry = null;
    private ?Counter $counter = null;
    private ?Histogram $histogram = null;
    private ?Gauge $gauge = null;

    /**
     * Increment a counter metric.
     */
    protected function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        $this->getCounter($name)->incBy($value, $labels);
    }

    /**
     * Record duration in histogram.
     */
    protected function recordDuration(string $name, float $duration, array $labels = []): void
    {
        $this->getHistogram($name)->observe($duration, $labels);
    }

    /**
     * Set gauge value.
     */
    protected function setGauge(string $name, float $value, array $labels = []): void
    {
        $this->getGauge($name)->set($value, $labels);
    }

    /**
     * Increment gauge value.
     */
    protected function incrementGauge(string $name, float $value = 1.0, array $labels = []): void
    {
        $this->getGauge($name)->incBy($value, $labels);
    }

    /**
     * Decrement gauge value.
     */
    protected function decrementGauge(string $name, float $value = 1.0, array $labels = []): void
    {
        $this->getGauge($name)->decBy($value, $labels);
    }

    /**
     * Record operation with metrics.
     */
    protected function withMetrics(
        string $counterName,
        string $histogramName,
        array $labels,
        callable $callback
    ): mixed {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            
            // Record success
            $this->incrementCounter($counterName, [...$labels, 'status' => 'success']);
            
            // Record duration
            $duration = microtime(true) - $startTime;
            $this->recordDuration($histogramName, $duration, $labels);
            
            return $result;
        } catch (\Throwable $e) {
            // Record failure
            $this->incrementCounter($counterName, [...$labels, 'status' => 'error']);
            
            // Record duration even on failure
            $duration = microtime(true) - $startTime;
            $this->recordDuration($histogramName, $duration, $labels);
            
            throw $e;
        }
    }

    private function getRegistry(): CollectorRegistry
    {
        if ($this->registry === null) {
            $this->registry = app(CollectorRegistry::class);
        }
        
        return $this->registry;
    }

    private function getCounter(string $name): Counter
    {
        if ($this->counter === null || $this->counter->getName() !== $name) {
            $this->counter = $this->getRegistry()->getOrRegisterCounter(
                'analytics',
                $name,
                'Analytics operation counter',
                ['operation', 'status']
            );
        }
        
        return $this->counter;
    }

    private function getHistogram(string $name): Histogram
    {
        if ($this->histogram === null || $this->histogram->getName() !== $name) {
            $this->histogram = $this->getRegistry()->getOrRegisterHistogram(
                'analytics',
                $name,
                'Analytics operation duration',
                ['operation'],
                [0.01, 0.05, 0.1, 0.5, 1, 2, 5, 10]
            );
        }
        
        return $this->histogram;
    }

    private function getGauge(string $name): Gauge
    {
        if ($this->gauge === null || $this->gauge->getName() !== $name) {
            $this->gauge = $this->getRegistry()->getOrRegisterGauge(
                'analytics',
                $name,
                'Analytics gauge metric',
                ['metric']
            );
        }
        
        return $this->gauge;
    }
}
