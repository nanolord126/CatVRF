<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Enums\CourierType;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Support\Collection;
use Modules\GeoLogistics\Services\GeoLogisticsService;
use Psr\Log\LoggerInterface;

/**
 * Multi-Modal Route Optimization Service for VRP.
 *
 * Production Strategy:
 * - Optimize routes for single-type clusters (pedestrian, scooter, car)
 * - Support time windows and capacity constraints
 * - Calculate cost matrices with type-specific costs
 * - Integrate with external VRP solvers (OR-Tools, custom)
 */
final readonly class MultiModalRouteOptimizationService
{
    public function __construct(
        private readonly GeoLogisticsService $geoService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Optimize routes for a single courier type.
     *
     * @param  array<int, array{lat: float, lng: float, weight_kg: float, time_window?: array}>  $orders
     * @param  array{lat: float, lng: float}  $depotLocation
     */
    public function optimizeRoutesForType(
        string $type,
        array $orders,
        array $depotLocation,
        int $maxCapacityKg,
        int $maxDeliveryTimeMin,
        int $maxStopsPerRoute = 10,
        bool $isResortSpit = false,
    ): Collection {
        $this->logger->$this->logger->info('Starting route optimization', [
            'type' => $type,
            'order_count' => count($orders),
            'max_capacity_kg' => $maxCapacityKg,
            'max_delivery_time_min' => $maxDeliveryTimeMin,
            'is_resort_spit' => $isResortSpit,
        ]);

        // Use linear clustering for resort/spit zones (Week 3)
        if ($isResortSpit) {
            $costMatrix = $this->calculateCostMatrixForLinearZone($orders, $depotLocation, $type);
            $clusters = $this->clusterOrdersLinear($orders, $costMatrix, $maxStopsPerRoute, 1.5); // 1.5km max distance
        } else {
            // Standard clustering for urban zones
            $costMatrix = $this->calculateCostMatrix($orders, $depotLocation, $type);
            $clusters = $this->clusterOrders($orders, $costMatrix, $maxStopsPerRoute);
        }

        // Build routes for each cluster
        $routes = $clusters->map(function ($cluster) use (
            $type,
            $depotLocation,
            $maxCapacityKg,
            $maxDeliveryTimeMin,
        ) {
            return $this->buildRoute($cluster, $depotLocation, $type, $maxCapacityKg, $maxDeliveryTimeMin);
        });

        $this->logger->$this->logger->info('Route optimization completed', [
            'type' => $type,
            'route_count' => $routes->count(),
        ]);

        return $routes;
    }

    /**
     * Calculate route cost in kopeks.
     */
    public function calculateRouteCost(array $route, float $baseCostPerKm = 500): int
    {
        $type = $route['type'];
        $courierType = CourierType::tryFrom($type);

        if ($courierType === null) {
            return 0;
        }

        $costMultiplier = $courierType->getCostMultiplier();
        $totalDistanceKm = $this->estimateRouteDistance($route);

        return (int) ($totalDistanceKm * $baseCostPerKm * $costMultiplier);
    }

    /**
     * Calculate deadhead ratio (empty distance / total distance).
     * Deadhead = distance traveled without cargo (return to depot, repositioning).
     *
     * @param  array  $route  Route with stops
     * @param  float  $totalDistance  Total route distance
     * @return float Deadhead ratio (0-1)
     */
    public function calculateDeadheadRatio(array $route, float $totalDistance): float
    {
        if ($totalDistance <= 0) {
            return 0.0;
        }

        $stops = $route['stops'] ?? [];
        if (count($stops) < 2) {
            return 0.0;
        }

        // Deadhead = return to depot distance (last stop to first stop/depot)
        $firstStop = $stops[0];
        $lastStop = $stops[count($stops) - 1];
        $returnDistance = $this->calculateDirectDistance($lastStop, $firstStop);

        $deadheadRatio = $returnDistance / $totalDistance;

        $this->logger->$this->logger->info('Deadhead ratio calculated', [
            'return_distance_km' => $returnDistance,
            'total_distance_km' => $totalDistance,
            'deadhead_ratio' => $deadheadRatio,
        ]);

        return $deadheadRatio;
    }

    /**
     * Check if route deadhead ratio is within acceptable threshold.
     * Resort/spit zones: ≤12%, Urban zones: ≤8%.
     *
     * @param  array  $route  Route with stops
     * @param  float  $totalDistance  Total route distance
     * @param  float  $lat  Delivery latitude
     * @param  float  $lng  Delivery longitude
     * @param  int  $tenantId  Tenant ID
     * @return bool True if deadhead ratio is acceptable
     */
    public function isDeadheadRatioAcceptable(
        array $route,
        float $totalDistance,
        float $lat,
        float $lng,
        int $tenantId,
    ): bool {
        $deadheadRatio = $this->calculateDeadheadRatio($route, $totalDistance);

        // Get zone-specific threshold (Week 3)
        // TODO: Integrate with GeoZoneClassifierService when available
        $threshold = 0.08; // Default urban threshold (8%)

        // Check if resort/spit zone (placeholder - would use GeoZoneClassifierService)
        $isResortSpit = false; // $this->zoneClassifier->isResortSpit($lat, $lng, $tenantId);
        if ($isResortSpit) {
            $threshold = 0.12; // Resort zone threshold (12%)
        }

        $isAcceptable = $deadheadRatio <= $threshold;

        $this->logger->$this->logger->info('Deadhead ratio check', [
            'deadhead_ratio' => $deadheadRatio,
            'threshold' => $threshold,
            'is_resort_spit' => $isResortSpit,
            'is_acceptable' => $isAcceptable,
            'lat' => $lat,
            'lng' => $lng,
            'tenant_id' => $tenantId,
        ]);

        return $isAcceptable;
    }

    /**
     * Calculate cost matrix for orders (distance/time between all points).
     *
     * @param  array<int, array{lat: float, lng: float}>  $orders
     * @param  array{lat: float, lng: float}  $depotLocation
     * @return array<int, array<int, float>>
     */
    private function calculateCostMatrix(array $orders, array $depotLocation, string $type): array
    {
        $routingMode = match ($type) {
            'pedestrian' => 'walking',
            'scooter', 'ebike' => 'biking',
            default => 'driving',
        };

        $matrix = [];
        $allLocations = array_merge([$depotLocation], $orders);

        foreach ($allLocations as $i => $from) {
            $matrix[$i] = [];
            foreach ($allLocations as $j => $to) {
                if ($i === $j) {
                    $matrix[$i][$j] = 0;

                    continue;
                }

                $route = $this->geoService->calculateRoute($from, $to, $routingMode);
                $matrix[$i][$j] = $route['duration_min']; // Use time as cost
            }
        }

        return $matrix;
    }

    /**
     * Calculate cost matrix for linear zones (resort/spit).
     * Uses line-string routing with penalize_reverse_direction.
     *
     * @param  array<int, array{lat: float, lng: float}>  $orders
     * @param  array{lat: float, lng: float}  $depotLocation
     * @return array<int, array<int, float>>
     */
    private function calculateCostMatrixForLinearZone(array $orders, array $depotLocation, string $type): array
    {
        $routingMode = match ($type) {
            'pedestrian' => 'walking',
            'scooter', 'ebike' => 'biking',
            default => 'driving',
        };

        $matrix = [];
        $allLocations = array_merge([$depotLocation], $orders);

        // Sort orders by position along the linear axis (simplified: by longitude)
        $sortedIndices = range(0, count($orders) - 1);
        usort($sortedIndices, function ($a, $b) use ($orders) {
            return $orders[$a]['lng'] <=> $orders[$b]['lng'];
        });

        foreach ($allLocations as $i => $from) {
            $matrix[$i] = [];
            foreach ($allLocations as $j => $to) {
                if ($i === $j) {
                    $matrix[$i][$j] = 0;

                    continue;
                }

                $route = $this->geoService->calculateRoute($from, $to, $routingMode);
                $baseCost = $route['duration_min'];

                // Penalize reverse direction on narrow spits (Week 3)
                $fromIndex = $i - 1; // Adjust for depot
                $toIndex = $j - 1;
                if ($fromIndex >= 0 && $toIndex >= 0) {
                    $fromOrderIdx = array_search($fromIndex, $sortedIndices, true);
                    $toOrderIdx = array_search($toIndex, $sortedIndices, true);

                    if ($fromOrderIdx !== false && $toOrderIdx !== false && $toOrderIdx < $fromOrderIdx) {
                        // Going backwards along the spit - add penalty
                        $baseCost *= 1.5; // 50% penalty for reverse direction
                    }
                }

                $matrix[$i][$j] = $baseCost;
            }
        }

        return $matrix;
    }

    /**
     * Cluster orders by proximity using simple greedy algorithm.
     * In production, use K-means or DBSCAN for better clustering.
     */
    private function clusterOrders(array $orders, array $costMatrix, int $maxClusterSize): Collection
    {
        $unassigned = new Collection($orders);
        $clusters = new Collection();

        while (! $unassigned->isEmpty()) {
            $cluster = new Collection([$unassigned->shift()]);
            $currentCluster = $cluster->first();

            // Add nearest orders until max size reached
            while ($cluster->count() < $maxClusterSize && ! $unassigned->isEmpty()) {
                $nearest = $this->findNearestOrder($currentCluster, $unassigned, $costMatrix);
                if ($nearest !== null) {
                    $cluster->push($nearest);
                    $unassigned = $unassigned->reject(fn ($o) => $o === $nearest);
                    $currentCluster = $nearest;
                } else {
                    break;
                }
            }

            $clusters->push($cluster);
        }

        return $clusters;
    }

    /**
     * Cluster orders linearly for resort/spit zones.
     * Orders are grouped along the linear axis (e.g., along a spit) with max distance constraint.
     *
     * @param  array<int, array{lat: float, lng: float}>  $orders
     * @param  array<int, array<int, float>>  $costMatrix
     * @param  int  $maxClusterSize  Maximum orders per cluster
     * @param  float  $maxDistanceKm  Maximum distance between consecutive orders (1.5km for resort zones)
     */
    private function clusterOrdersLinear(array $orders, array $costMatrix, int $maxClusterSize, float $maxDistanceKm): Collection
    {
        // Sort orders by position along linear axis (simplified: by longitude)
        $sortedOrders = new Collection($orders)->sortBy('lng')->values();
        $clusters = new Collection();
        $currentCluster = new Collection();

        foreach ($sortedOrders as $order) {
            if ($currentCluster->isEmpty()) {
                $currentCluster->push($order);
            } else {
                $lastOrder = $currentCluster->last();
                $distance = $this->calculateDirectDistance($lastOrder, $order);

                // Check distance constraint and cluster size
                if ($distance <= $maxDistanceKm && $currentCluster->count() < $maxClusterSize) {
                    $currentCluster->push($order);
                } else {
                    // Start new cluster
                    $clusters->push($currentCluster);
                    $currentCluster = new Collection([$order]);
                }
            }
        }

        // Add last cluster
        if (! $currentCluster->isEmpty()) {
            $clusters->push($currentCluster);
        }

        $this->logger->$this->logger->info('Linear clustering completed', [
            'total_orders' => count($orders),
            'cluster_count' => $clusters->count(),
            'max_distance_km' => $maxDistanceKm,
        ]);

        return $clusters;
    }

    /**
     * Find nearest order to current cluster.
     */
    private function findNearestOrder(array $current, Collection $candidates, array $costMatrix): ?array
    {
        $nearest = null;
        $minCost = PHP_FLOAT_MAX;

        foreach ($candidates as $candidate) {
            // Simplified: use direct distance (production should use proper routing)
            $cost = $this->calculateDirectDistance($current, $candidate);

            if ($cost < $minCost) {
                $minCost = $cost;
                $nearest = $candidate;
            }
        }

        return $nearest;
    }

    /**
     * Calculate direct distance between two points (simplified).
     */
    private function calculateDirectDistance(array $from, array $to): float
    {
        $lat1 = deg2rad($from['lat']);
        $lon1 = deg2rad($from['lng']);
        $lat2 = deg2rad($to['lat']);
        $lon2 = deg2rad($to['lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return 6371 * $c; // Earth radius in km
    }

    /**
     * Build route from cluster with capacity and time constraints.
     */
    private function buildRoute(
        Collection $cluster,
        array $depotLocation,
        string $type,
        int $maxCapacityKg,
        int $maxDeliveryTimeMin,
    ): array {
        $routingMode = match ($type) {
            'pedestrian' => 'walking',
            'scooter', 'ebike' => 'biking',
            default => 'driving',
        };

        $stops = [$depotLocation];
        $totalWeight = 0;
        $totalTime = 0;
        $currentLocation = $depotLocation;

        foreach ($cluster as $order) {
            $route = $this->geoService->calculateRoute($currentLocation, $order, $routingMode);

            // Check capacity constraint
            if ($totalWeight + ($order['weight_kg'] ?? 0) > $maxCapacityKg) {
                break; // Would exceed capacity
            }

            // Check time constraint
            if ($totalTime + $route['duration_min'] > $maxDeliveryTimeMin) {
                break; // Would exceed time limit
            }

            $stops[] = $order;
            $totalWeight += $order['weight_kg'] ?? 0;
            $totalTime += $route['duration_min'];
            $currentLocation = $order;
        }

        // Add return to depot
        $returnRoute = $this->geoService->calculateRoute($currentLocation, $depotLocation, $routingMode);
        $stops[] = $depotLocation;
        $totalTime += $returnRoute['duration_min'];

        return [
            'type' => $type,
            'stops' => $stops,
            'total_weight_kg' => $totalWeight,
            'total_time_min' => $totalTime,
            'stop_count' => count($stops) - 2, // Exclude depot
        ];
    }

    /**
     * Estimate total route distance.
     */
    private function estimateRouteDistance(array $route): float
    {
        $distance = 0;
        $stops = $route['stops'] ?? [];

        for ($i = 0; $i < count($stops) - 1; $i++) {
            $distance += $this->calculateDirectDistance($stops[$i], $stops[$i + 1]);
        }

        return $distance;
    }
}
