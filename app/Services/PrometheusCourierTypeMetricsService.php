<?php

declare(strict_types=1);

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;

/**
 * Prometheus Metrics Service for Multi-Type Courier Strategy.
 *
 * Tracks key metrics for courier type performance:
 * - Assignment success rate by type
 * - Average delivery time by type
 * - Cost per delivery by type
 * - SLA compliance by type
 */
final readonly class PrometheusCourierTypeMetricsService
{
    private readonly Histogram $assignmentDuration;

    private readonly Histogram $deliveryTimeByType;

    private readonly Histogram $costByType;

    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {
        $this->assignmentDuration = $this->registry->getOrRegisterHistogram(
            'logistics',
            'courier_assignment_duration_seconds',
            'Time taken to assign courier',
            ['type', 'city', 'success'],
            [0.1, 0.5, 1, 2, 5, 10],
        );

        $this->deliveryTimeByType = $this->registry->getOrRegisterHistogram(
            'logistics',
            'courier_delivery_time_minutes',
            'Actual delivery time by courier type',
            ['type', 'city'],
            [15, 30, 45, 60, 90, 120],
        );

        $this->costByType = $this->registry->getOrRegisterHistogram(
            'logistics',
            'courier_delivery_cost_rubles',
            'Delivery cost by courier type',
            ['type', 'city'],
            [100, 200, 500, 1000, 2000, 5000],
        );
    }

    /**
     * Record assignment duration.
     */
    public function recordAssignment(
        string $type,
        ?string $city,
        bool $success,
        float $durationSeconds,
    ): void {
        $this->assignmentDuration->observe(
            $durationSeconds,
            [$type, $city ?? 'default', $success ? 'true' : 'false'],
        );
    }

    /**
     * Record delivery time.
     */
    public function recordDeliveryTime(
        string $type,
        ?string $city,
        int $minutes,
    ): void {
        $this->deliveryTimeByType->observe(
            $minutes,
            [$type, $city ?? 'default'],
        );
    }

    /**
     * Record delivery cost.
     */
    public function recordDeliveryCost(
        string $type,
        ?string $city,
        int $costKopek,
    ): void {
        $this->costByType->observe(
            $costKopek / 100, // Convert to rubles
            [$type, $city ?? 'default'],
        );
    }

    /**
     * Export metrics for scraping.
     */
    public function exportMetrics(): string
    {
        $renderer = new RenderTextFormat();

        return $this->registry->getMetricFamilySamples();
    }
}
