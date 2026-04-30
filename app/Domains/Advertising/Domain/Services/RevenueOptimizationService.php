<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Interfaces\AdInventoryRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\PublisherRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Revenue Optimization Service
 *
 * Implements dynamic pricing and revenue optimization algorithms
 * to maximize publisher revenue while maintaining fill rates and advertiser ROI.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class RevenueOptimizationService
{
    private const CACHE_TTL = 600; // 10 minutes
    private const PRICE_UPDATE_INTERVAL = 3600; // 1 hour

    public function __construct(
        private readonly AdInventoryRepositoryInterface $inventoryRepository,
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly PublisherRepositoryInterface $publisherRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calculate optimal floor price for inventory
     *
     * @param int $inventoryId Inventory ID
     * @return array{floor_price: int, recommended_price: int, confidence: float, factors: array}
     */
    public function calculateOptimalFloorPrice(int $inventoryId): array
    {
        $cacheKey = "revenue:floor_price:{$inventoryId}";
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $inventory = $this->inventoryRepository->findById($inventoryId);
        if ($inventory === null) {
            throw new \RuntimeException('Inventory not found');
        }

        $this->logger->info('Calculating optimal floor price', [
            'inventory_id' => $inventoryId,
        ]);

        // Analyze historical auction data
        $historicalData = $this->getHistoricalAuctionData($inventoryId);
        
        // Calculate market demand
        $demandScore = $this->calculateDemandScore($inventory);
        
        // Calculate supply constraint
        $supplyScore = $this->calculateSupplyScore($inventory);
        
        // Calculate competitor pricing
        $competitorPrices = $this->getCompetitorPrices($inventory);
        
        // Calculate optimal price
        $basePrice = isset($historicalData['avg_winning_price']) ? $historicalData['avg_winning_price'] : 50000;
        
        // Apply demand multiplier
        $demandMultiplier = 1.0 + ($demandScore - 0.5) * 0.4;
        
        // Apply supply multiplier
        $supplyMultiplier = 1.0 + (0.5 - $supplyScore) * 0.3;
        
        $recommendedPrice = (int) ($basePrice * $demandMultiplier * $supplyMultiplier);
        
        // Set floor price at 80% of recommended
        $floorPrice = (int) ($recommendedPrice * 0.8);
        
        // Calculate confidence
        $confidence = $this->calculatePriceConfidence(
            $historicalData['sample_size'] ?? 0,
            $demandScore,
            $supplyScore,
        );

        $result = [
            'floor_price' => $floorPrice,
            'recommended_price' => $recommendedPrice,
            'confidence' => $confidence,
            'factors' => [
                'historical_avg_price' => $basePrice,
                'demand_score' => $demandScore,
                'supply_score' => $supplyScore,
                'demand_multiplier' => $demandMultiplier,
                'supply_multiplier' => $supplyMultiplier,
                'competitor_prices' => $competitorPrices,
            ],
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Get historical auction data for inventory
     */
    private function getHistoricalAuctionData(int $inventoryId): array
    {
        $key = "revenue:historical:{$inventoryId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        // In production, query auction history
        // For now, return placeholder
        $data = [
            'avg_winning_price' => 75000,
            'min_winning_price' => 50000,
            'max_winning_price' => 150000,
            'win_rate' => 0.65,
            'sample_size' => 150,
        ];

        Redis::setex($key, 3600, json_encode($data));
        
        return $data;
    }

    /**
     * Calculate demand score for inventory
     */
    private function calculateDemandScore(\App\Domains\Advertising\Domain\Entities\AdInventory $inventory): float
    {
        $remaining = $inventory->getRemainingImpressions();
        $total = $inventory->available_impressions;
        
        // Lower remaining = higher demand
        $utilization = 1.0 - ($remaining / max(1, $total));
        
        // Time-based demand curve
        $timeUntilEnd = $inventory->available_until->diffInSeconds(now());
        $timeScore = $timeUntilEnd < 86400 ? 0.8 : 0.5;
        
        return ($utilization * 0.7) + ($timeScore * 0.3);
    }

    /**
     * Calculate supply score for inventory
     */
    private function calculateSupplyScore(\App\Domains\Advertising\Domain\Entities\AdInventory $inventory): float
    {
        // Get similar inventory count
        $similarInventory = $this->inventoryRepository->findByPublisher($inventory->publisher_id)
            ->where('inventory_type', $inventory->inventory_type)
            ->where('placement', $inventory->placement);
        
        $totalSimilar = $similarInventory->count();
        
        // More similar inventory = higher supply
        $supplyScore = min(1.0, $totalSimilar / 10.0);
        
        return $supplyScore;
    }

    /**
     * Get competitor prices for comparison
     */
    private function getCompetitorPrices(\App\Domains\Advertising\Domain\Entities\AdInventory $inventory): array
    {
        // In production, query market data
        // For now, return placeholder
        return [
            'min' => 40000,
            'avg' => 70000,
            'max' => 120000,
        ];
    }

    /**
     * Calculate confidence in price recommendation
     */
    private function calculatePriceConfidence(
        int $sampleSize,
        float $demandScore,
        float $supplyScore,
    ): float {
        $confidence = 0.5;
        
        // More historical data = higher confidence
        if ($sampleSize > 100) {
            $confidence += 0.2;
        } elseif ($sampleSize > 50) {
            $confidence += 0.1;
        }
        
        // Clear demand/supply signals = higher confidence
        if (abs($demandScore - 0.5) > 0.3) {
            $confidence += 0.1;
        }
        
        if (abs($supplyScore - 0.5) > 0.3) {
            $confidence += 0.1;
        }
        
        return min(0.95, $confidence);
    }

    /**
     * Optimize reserve prices for auctions
     *
     * @param int $auctionId Auction ID
     * @return array{current_reserve_price: int, optimal_reserve_price: int, expected_improvement: float}
     */
    public function optimizeAuctionReservePrice(int $auctionId): array
    {
        $auction = $this->auctionRepository->findById($auctionId);
        if ($auction === null) {
            throw new \RuntimeException('Auction not found');
        }

        $currentReserve = $auction->reserve_price;
        
        // Get historical auction performance
        $historical = $this->getHistoricalAuctionData($auction->inventory_id ?? 0);
        
        // Calculate optimal reserve
        $avgWinningPrice = isset($historical['avg_winning_price']) ? $historical['avg_winning_price'] : 50000;
        $winRate = isset($historical['win_rate']) ? $historical['win_rate'] : 0.5;
        
        // If win rate is too high, increase reserve
        // If win rate is too low, decrease reserve
        if ($winRate > 0.8) {
            $optimalReserve = (int) ($avgWinningPrice * 0.9);
        } elseif ($winRate < 0.4) {
            $optimalReserve = (int) ($avgWinningPrice * 0.6);
        } else {
            $optimalReserve = (int) ($avgWinningPrice * 0.75);
        }
        
        // Ensure reserve is at least 50% of starting price
        $optimalReserve = max($optimalReserve, (int) ($auction->starting_price * 0.5));
        
        $expectedImprovement = (($optimalReserve - $currentReserve) / max(1, $currentReserve)) * 100;

        return [
            'current_reserve_price' => $currentReserve,
            'optimal_reserve_price' => $optimalReserve,
            'expected_improvement' => $expectedImprovement,
            'historical_win_rate' => $winRate,
            'historical_avg_price' => $avgWinningPrice,
        ];
    }

    /**
     * Calculate revenue forecast for publisher
     *
     * @param int $publisherId Publisher ID
     * @param int $days Forecast period in days
     * @return array{daily_forecast: array, total_revenue: int, confidence: float}
     */
    public function calculateRevenueForecast(int $publisherId, int $days = 30): array
    {
        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        $inventories = $this->inventoryRepository->findByPublisher($publisherId);
        
        $dailyForecast = [];
        $totalRevenue = 0;
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->addDays($i);
            $dayRevenue = 0;
            
            foreach ($inventories as $inventory) {
                if ($inventory->isAvailable() && $date->between($inventory->available_from, $inventory->available_until)) {
                    // Estimate daily revenue based on utilization
                    $utilization = $this->calculateDemandScore($inventory);
                    $dailyCapacity = $inventory->available_impressions / max(1, $inventory->available_until->diffInDays($inventory->available_from));
                    
                    $floorPrice = $this->calculateOptimalFloorPrice($inventory->id)['floor_price'];
                    
                    $dayRevenue += (int) ($dailyCapacity * $utilization * ($floorPrice / 1000)); // CPM to per impression
                }
            }
            
            $dailyForecast[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'estimated_revenue' => $dayRevenue,
                'weekday' => $date->dayOfWeek,
            ];
            
            $totalRevenue += $dayRevenue;
        }
        
        return [
            'daily_forecast' => $dailyForecast,
            'total_revenue' => $totalRevenue,
            'average_daily_revenue' => (int) ($totalRevenue / $days),
            'confidence' => 0.75, // Placeholder
        ];
    }

    /**
     * Get revenue optimization recommendations for publisher
     *
     * @param int $publisherId Publisher ID
     * @return array{recommendations: array, priority_actions: array, expected_lift: float}
     */
    public function getOptimizationRecommendations(int $publisherId): array
    {
        $recommendations = [];
        $priorityActions = [];
        
        $inventories = $this->inventoryRepository->findByPublisher($publisherId);
        
        foreach ($inventories as $inventory) {
            if (!$inventory->isAvailable()) {
                continue;
            }
            
            $priceData = $this->calculateOptimalFloorPrice($inventory->id);
            $demandScore = $this->calculateDemandScore($inventory);
            
            // Recommendation 1: Adjust floor prices
            if ($demandScore > 0.8 && $priceData['confidence'] > 0.7) {
                $recommendations[] = [
                    'type' => 'increase_floor_price',
                    'inventory_id' => $inventory->id,
                    'current_price' => $priceData['floor_price'],
                    'recommended_price' => (int) ($priceData['floor_price'] * 1.15),
                    'reason' => 'High demand detected, can increase prices',
                    'priority' => 'high',
                ];
            }
            
            // Recommendation 2: Release reserved inventory if underutilized
            if ($demandScore < 0.3) {
                $recommendations[] = [
                    'type' => 'release_inventory',
                    'inventory_id' => $inventory->id,
                    'reserved_impressions' => $inventory->reserved_impressions,
                    'reason' => 'Low demand, consider releasing reserved inventory',
                    'priority' => 'medium',
                ];
            }
            
            // Recommendation 3: Create more inventory if high demand
            if ($demandScore > 0.9 && $inventory->getRemainingImpressions() < 10000) {
                $recommendations[] = [
                    'type' => 'create_inventory',
                    'inventory_type' => $inventory->inventory_type,
                    'placement' => $inventory->placement,
                    'reason' => 'High demand with low remaining inventory',
                    'priority' => 'high',
                ];
            }
        }
        
        // Priority actions
        $highPriority = array_filter($recommendations, fn($r) => $r['priority'] === 'high');
        $priorityActions = array_values($highPriority);
        
        return [
            'recommendations' => $recommendations,
            'priority_actions' => $priorityActions,
            'total_recommendations' => count($recommendations),
            'expected_lift' => count($highPriority) > 0 ? 0.15 : 0.05, // 15% lift if high priority actions taken
        ];
    }

    /**
     * Batch optimize floor prices for multiple inventories
     *
     * @param array<int, int> $inventoryIds Array of inventory IDs
     * @return array<int, array>
     */
    public function batchOptimizeFloorPrices(array $inventoryIds): array
    {
        $results = [];
        
        foreach ($inventoryIds as $inventoryId) {
            try {
                $results[$inventoryId] = $this->calculateOptimalFloorPrice($inventoryId);
            } catch (\Throwable $e) {
                $this->logger->error('Floor price optimization failed', [
                    'inventory_id' => $inventoryId,
                    'error' => $e->getMessage(),
                ]);
                $results[$inventoryId] = null;
            }
        }
        
        return $results;
    }

    /**
     * Apply dynamic pricing rules in real-time
     *
     * @param int $inventoryId Inventory ID
     * @param int $currentBid Current bid amount
     * @return int Adjusted price
     */
    public function applyDynamicPricing(int $inventoryId, int $currentBid): int
    {
        $inventory = $this->inventoryRepository->findById($inventoryId);
        if ($inventory === null) {
            return $currentBid;
        }
        
        $demandScore = $this->calculateDemandScore($inventory);
        
        // Dynamic multiplier based on demand
        $multiplier = match (true) {
            $demandScore > 0.8 => 1.2, // High demand: 20% premium
            $demandScore > 0.6 => 1.1, // Medium-high demand: 10% premium
            $demandScore < 0.3 => 0.9, // Low demand: 10% discount
            default => 1.0,
        };
        
        return (int) ($currentBid * $multiplier);
    }

    /**
     * Track revenue performance against forecasts
     *
     * @param int $publisherId Publisher ID
     * @return array{actual_revenue: int, forecasted_revenue: int, variance: float, variance_percent: float}
     */
    public function trackRevenuePerformance(int $publisherId): array
    {
        // In production, query actual revenue from analytics
        $actualRevenue = 0;
        
        $forecast = $this->calculateRevenueForecast($publisherId, 7); // 7-day forecast
        $forecastedRevenue = $forecast['total_revenue'];
        
        $variance = $actualRevenue - $forecastedRevenue;
        $variancePercent = $forecastedRevenue > 0 
            ? ($variance / $forecastedRevenue) * 100 
            : 0;
        
        return [
            'actual_revenue' => $actualRevenue,
            'forecasted_revenue' => $forecastedRevenue,
            'variance' => $variance,
            'variance_percent' => $variancePercent,
            'performance' => abs($variancePercent) < 10 ? 'on_track' : 'off_track',
        ];
    }
}
