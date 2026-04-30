<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Support\Collection;

use App\Domains\Logistics\DTOs\BatchOptimizationResult;
use Psr\Log\LoggerInterface;

/**
 * Spit Batch Optimizer Service (Agentic AI Tool)
 *
 * Agentic AI tool for automatic batch optimization in resort/spit zones.
 * Automatically detects linear clusters along spits/beaches and optimizes batches.
 *
 * Production Strategy:
 * - Detects linear clusters of orders along spits/beaches
 * - Automatically forms batches with 1.5km max distance
 * - Checks deadhead ratio thresholds (≤12% for resort zones)
 * - Integrates with Agentic AI for real-time optimization
 * - Supports shadow mode for A/B testing
 */
final readonly class SpitBatchOptimizerService
{
    public function __construct(
        private readonly GeoZoneClassifierService $zoneClassifier,
        private readonly MultiModalRouteOptimizationService $routeOptimizer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Optimize batch for resort/spit zone (Agentic AI tool).
     *
     * @param  array<int, array{order_id: string, lat: float, lng: float, weight_kg: float, time_window?: array}>  $orders
     * @param  int  $tenantId  Tenant ID
     * @param  array{lat: float, lng: float}  $depotLocation  Depot location
     * @param  bool  $shadowMode  Shadow mode for A/B testing
     */
    public function optimizeSpitBatch(
        array $orders,
        int $tenantId,
        array $depotLocation,
        bool $shadowMode = false,
    ): BatchOptimizationResult {
        $this->logger->$this->logger->info('Starting spit batch optimization (Agentic AI)', [
            'order_count' => count($orders),
            'tenant_id' => $tenantId,
            'shadow_mode' => $shadowMode,
        ]);

        if (empty($orders)) {
            return new BatchOptimizationResult(
                batches: [],
                totalOrders: 0,
                totalDeadheadRatio: 0,
                isResortSpit: false,
                optimizationScore: 0,
            );
        }

        // Check if any order is in resort/spit zone
        $firstOrder = $orders[0];
        $isResortSpit = $this->zoneClassifier->isResortSpit($firstOrder['lat'], $firstOrder['lng'], $tenantId);

        if (! $isResortSpit) {
            $this->logger->$this->logger->info('Not in resort/spit zone, skipping spit optimization', [
                'tenant_id' => $tenantId,
                'lat' => $firstOrder['lat'],
                'lng' => $firstOrder['lng'],
            ]);

            // Return empty result - not applicable for urban zones
            return new BatchOptimizationResult(
                batches: [],
                totalOrders: count($orders),
                totalDeadheadRatio: 0,
                isResortSpit: false,
                optimizationScore: 0,
            );
        }

        // Get zone classification
        $zoneClassification = $this->zoneClassifier->classifyZoneAndGetMaxBatchDistance(
            $firstOrder['lat'],
            $firstOrder['lng'],
            $tenantId,
        );

        $maxBatchDistance = $zoneClassification['max_batch_distance_km']; // 1.5km for resort zones
        $deadheadThreshold = $zoneClassification['deadhead_ratio_threshold']; // 0.12 for resort zones

        // Detect linear clusters
        $linearClusters = $this->detectLinearClusters($orders, $maxBatchDistance);

        $batches = [];
        $totalDeadheadRatio = 0;

        // Optimize each linear cluster
        foreach ($linearClusters as $cluster) {
            $batch = $this->optimizeLinearCluster(
                $cluster,
                $tenantId,
                $depotLocation,
                $deadheadThreshold,
                $shadowMode,
            );

            if ($batch !== null) {
                $batches[] = $batch;
                $totalDeadheadRatio += $batch['deadhead_ratio'];
            }
        }

        // Calculate optimization score
        $optimizationScore = $this->calculateOptimizationScore($batches, $orders);

        $this->logger->$this->logger->info('Spit batch optimization completed', [
            'batch_count' => count($batches),
            'total_deadhead_ratio' => $totalDeadheadRatio / max(1, count($batches)),
            'optimization_score' => $optimizationScore,
            'shadow_mode' => $shadowMode,
        ]);

        return new BatchOptimizationResult(
            batches: $batches,
            totalOrders: count($orders),
            totalDeadheadRatio: $totalDeadheadRatio / max(1, count($batches)),
            isResortSpit: true,
            optimizationScore: $optimizationScore,
        );
    }

    /**
     * Detect linear clusters of orders along spit/beach.
     *
     * @param  array<int, array{order_id: string, lat: float, lng: float, weight_kg: float}>  $orders
     * @param  float  $maxDistanceKm  Max distance between consecutive orders
     * @return array<int, array<int, array{order_id: string, lat: float, lng: float, weight_kg: float}>>
     */
    private function detectLinearClusters(array $orders, float $maxDistanceKm): array
    {
        // Sort orders by longitude (linear axis)
        $sortedOrders = new Collection($orders)->sortBy('lng')->values()->all();

        $clusters = [];
        $currentCluster = [];

        foreach ($sortedOrders as $order) {
            if (empty($currentCluster)) {
                $currentCluster[] = $order;
            } else {
                $lastOrder = end($currentCluster);
                $distance = $this->calculateDirectDistance(
                    $lastOrder['lat'],
                    $lastOrder['lng'],
                    $order['lat'],
                    $order['lng'],
                );

                if ($distance <= $maxDistanceKm) {
                    $currentCluster[] = $order;
                } else {
                    $clusters[] = $currentCluster;
                    $currentCluster = [$order];
                }
            }
        }

        if (! empty($currentCluster)) {
            $clusters[] = $currentCluster;
        }

        $this->logger->$this->logger->info('Linear clusters detected', [
            'total_orders' => count($orders),
            'cluster_count' => count($clusters),
            'max_distance_km' => $maxDistanceKm,
        ]);

        return $clusters;
    }

    /**
     * Optimize a linear cluster into a batch.
     *
     * @param  array<int, array{order_id: string, lat: float, lng: float, weight_kg: float}>  $cluster
     * @param  int  $tenantId  Tenant ID
     * @param  array{lat: float, lng: float}  $depotLocation  Depot location
     * @param  float  $deadheadThreshold  Deadhead ratio threshold
     * @param  bool  $shadowMode  Shadow mode
     * @return array|null Batch or null if optimization fails
     */
    private function optimizeLinearCluster(
        array $cluster,
        int $tenantId,
        array $depotLocation,
        float $deadheadThreshold,
        bool $shadowMode,
    ): ?array {
        $orderCount = count($cluster);
        if ($orderCount < 2) {
            // Single order - no batching needed
            return null;
        }

        // Calculate total weight
        $totalWeight = array_sum(array_column($cluster, 'weight_kg'));

        // Build route using linear VRP
        $routes = $this->routeOptimizer->optimizeRoutesForType(
            type: 'car', // Default to car for resort zones
            orders: $cluster,
            depotLocation: $depotLocation,
            maxCapacityKg: 100, // 100kg default
            maxDeliveryTimeMin: 120, // 2 hours
            maxStopsPerRoute: $orderCount,
            isResortSpit: true,
        );

        if ($routes->isEmpty()) {
            return null;
        }

        $route = $routes->first();
        $totalDistance = $this->routeOptimizer->estimateRouteDistance($route);

        // Check deadhead ratio
        $isDeadheadAcceptable = $this->routeOptimizer->isDeadheadRatioAcceptable(
            $route,
            $totalDistance,
            $cluster[0]['lat'],
            $cluster[0]['lng'],
            $tenantId,
        );

        if (! $isDeadheadAcceptable && ! $shadowMode) {
            $this->logger->warning('Deadhead ratio exceeds threshold, rejecting batch', [
                'cluster_size' => $orderCount,
                'total_distance_km' => $totalDistance,
                'deadhead_threshold' => $deadheadThreshold,
            ]);

            return null;
        }

        $deadheadRatio = $this->routeOptimizer->calculateDeadheadRatio($route, $totalDistance);

        return [
            'order_ids' => array_column($cluster, 'order_id'),
            'order_count' => $orderCount,
            'total_weight_kg' => $totalWeight,
            'total_distance_km' => $totalDistance,
            'deadhead_ratio' => $deadheadRatio,
            'route' => $route,
            'is_shadow_mode' => $shadowMode,
        ];
    }

    /**
     * Calculate optimization score (0-1).
     * Higher score = better optimization.
     */
    private function calculateOptimizationScore(array $batches, array $originalOrders): float
    {
        if (empty($batches)) {
            return 0.0;
        }

        $batchedOrderCount = array_sum(array_column($batches, 'order_count'));
        $totalOrderCount = count($originalOrders);

        if ($totalOrderCount === 0) {
            return 0.0;
        }

        $batchingEfficiency = $batchedOrderCount / $totalOrderCount;

        $avgDeadheadRatio = array_sum(array_column($batches, 'deadhead_ratio')) / count($batches);
        $deadheadScore = max(0, 1 - ($avgDeadheadRatio / 0.15)); // Normalize against 15%

        // Combined score: 70% batching efficiency, 30% deadhead optimization
        $score = (0.7 * $batchingEfficiency) + (0.3 * $deadheadScore);

        return min(1.0, max(0.0, $score));
    }

    /**
     * Calculate direct distance between two points (Haversine formula).
     */
    private function calculateDirectDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}
