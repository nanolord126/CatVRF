<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use Illuminate\Database\DatabaseManager;
use Prometheus\CollectorRegistry;

/**
 * Prometheus Metrics Collector for Event System
 */
final readonly class EventMetricsCollector
{
    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly DatabaseManager $db,
    ) {}

    public function recordEventPublished(string $eventType, string $queue, string $vertical): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'catvrf_events',
            name: 'published_total',
            help: 'Total number of events published',
            labels: ['event_type', 'queue', 'vertical']
        );

        $counter->inc([$eventType, $queue, $vertical]);
    }

    public function recordEventProcessingTime(string $eventType, float $durationMs): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            namespace: 'catvrf_events',
            name: 'processing_duration_seconds',
            help: 'Event processing duration in seconds',
            labels: ['event_type'],
            buckets: [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );

        $histogram->observe($durationMs / 1000, [$eventType]);
    }

    public function recordEventFailed(string $eventType, string $reason): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'catvrf_events',
            name: 'failed_total',
            help: 'Total number of failed events',
            labels: ['event_type', 'reason']
        );

        $counter->inc([$eventType, $reason]);
    }

    public function recordEventRetried(string $eventType): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'catvrf_events',
            name: 'retried_total',
            help: 'Total number of retried events',
            labels: ['event_type']
        );

        $counter->inc([$eventType]);
    }

    public function recordDLQEvent(string $eventType, string $failureReason): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'catvrf_events',
            name: 'dlq_total',
            help: 'Total number of events moved to Dead Letter Queue',
            labels: ['event_type', 'failure_reason']
        );

        $counter->inc([$eventType, $failureReason]);
    }

    public function updateOutboxMetrics(): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            namespace: 'catvrf_events',
            name: 'outbox_pending',
            help: 'Number of pending events in outbox',
            labels: []
        );

        $pendingCount = $this->db->table('outbox_messages')
            ->where('status', 'pending')
            ->count();

        $gauge->set($pendingCount);
    }

    public function updateDLQMetrics(): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            namespace: 'catvrf_events',
            name: 'dlq_unprocessed',
            help: 'Number of unprocessed events in Dead Letter Queue',
            labels: []
        );

        $unprocessedCount = $this->db->table('dead_letter_queue')
            ->where('is_processed', false)
            ->count();

        $gauge->set($unprocessedCount);
    }

    public function updateQueueMetrics(): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            namespace: 'catvrf_events',
            name: 'queue_size',
            help: 'Number of jobs in queue',
            labels: ['queue']
        );

        $queues = ['emergency', 'payment', 'notification', 'default'];

        foreach ($queues as $queue) {
            $size = $this->db->table('jobs')
                ->where('queue', $queue)
                ->count();

            $gauge->set($size, [$queue]);
        }
    }

    public function updateClickHouseMetrics(): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            namespace: 'catvrf_events',
            name: 'clickhouse_events_24h',
            help: 'Number of events in ClickHouse last 24h',
            labels: []
        );

        // This would query ClickHouse, for now set to 0
        $gauge->set(0);
    }

    public function recordVerticalEvent(string $vertical, string $eventType): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'catvrf_events',
            name: 'vertical_events_total',
            help: 'Total events per vertical',
            labels: ['vertical', 'event_type']
        );

        $counter->inc([$vertical, $eventType]);
    }

    public function getMetrics(): string
    {
        return $this->registry->getMetricFamilySamples();
    }
}
