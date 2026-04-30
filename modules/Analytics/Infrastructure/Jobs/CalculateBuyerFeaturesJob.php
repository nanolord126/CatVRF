<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Models\BuyerSellerFeatures;

/**
 * Calculate Buyer Features Job
 * 
 * Aggregates features for buyer-seller pairs from transactional data.
 * Runs daily to update the feature store for CLV prediction.
 * 
 * Production considerations:
 * - Uses chunked processing to handle large datasets
 * - Updates existing records or creates new ones
 * - Calculates RFM scores, behavioral metrics, traffic sources
 * 
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class CalculateBuyerFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        private readonly ?int $sellerId = null,
        private readonly ?int $tenantId = null,
        private readonly AuditService $auditService,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $this->logAction('calculate_buyer_features', 'Calculating buyer features for CLV prediction');

        Log::info('Starting CalculateBuyerFeaturesJob');

        // Build query for orders - use actual column names from Order model
        $query = DB::table('orders')
            ->select([
                'user_id as buyer_id',
                'business_group_id as seller_id',
                'tenant_id',
            ])
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        if ($this->sellerId) {
            $query->where('business_group_id', $this->sellerId);
        }

        if ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        // Get unique buyer-seller pairs
        $pairs = $query
            ->distinct()
            ->get()
            ->chunk(1000);

        $processed = 0;

        foreach ($pairs as $chunk) {
            foreach ($chunk as $pair) {
                $this->calculateFeaturesForPair(
                    (int) $pair->buyer_id,
                    (int) $pair->seller_id,
                    (int) $pair->tenant_id,
                );
                $processed++;
            }

            // Log progress every 1000 pairs
            if ($processed % 1000 === 0) {
                Log::info("Processed {$processed} buyer-seller pairs");
                $this->queue->release(30); // Extend timeout
            }
        }

        Log::info("CalculateBuyerFeaturesJob completed. Processed {$processed} pairs");
        $this->logAction('calculate_buyer_features_complete', "Processed {$processed} pairs");
    }

    /**
     * Calculate features for a specific buyer-seller pair.
     */
    private function calculateFeaturesForPair(
        int $buyerId,
        int $sellerId,
        int $tenantId,
    ): void {
        $now = now();
        $ninetyDaysAgo = $now->copy()->subDays(90);
        $hundredEightyDaysAgo = $now->copy()->subDays(180);
        $threeSixtyFiveDaysAgo = $now->copy()->subDays(365);

        // Get order data for this pair - use correct column names
        $orders = DB::table('orders')
            ->where('user_id', $buyerId)
            ->where('business_group_id', $sellerId)
            ->where('tenant_id', $tenantId)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        // Calculate recency
        $lastPurchase = $orders->max('created_at');
        $recencyDays = $lastPurchase ? $now->diffInDays($lastPurchase) : null;

        // Calculate frequency
        $frequency90d = $orders->where('created_at', '>=', $ninetyDaysAgo)->count();
        $frequency180d = $orders->where('created_at', '>=', $hundredEightyDaysAgo)->count();
        $frequency365d = $orders->where('created_at', '>=', $threeSixtyFiveDaysAgo)->count();

        // Calculate monetary - convert from kopiykas to rubles
        $monetary90d = (float) $orders->where('created_at', '>=', $ninetyDaysAgo)->sum('total') / 100;
        $monetary180d = (float) $orders->where('created_at', '>=', $hundredEightyDaysAgo)->sum('total') / 100;
        $monetary365d = (float) $orders->where('created_at', '>=', $threeSixtyFiveDaysAgo)->sum('total') / 100;

        $totalOrders = $orders->count();
        $totalMonetary = (float) $orders->sum('total') / 100;
        $avgOrderValue = $totalOrders > 0 ? $totalMonetary / $totalOrders : 0;

        // Calculate RFM scores (1-5 scale, simplified)
        $rScore = $this->calculateRScore($recencyDays);
        $fScore = $this->calculateFScore($frequency180d);
        $mScore = $this->calculateMScore($monetary180d);

        // Get first purchase
        $firstPurchase = $orders->min('created_at');
        $daysSinceFirstPurchase = $firstPurchase ? $now->diffInDays($firstPurchase) : null;

        // Get return rate
        $returnRate = $this->calculateReturnRate($buyerId, $sellerId, $tenantId);

        // Get review score
        $reviewData = $this->getReviewScore($buyerId, $sellerId, $tenantId);

        // Get traffic sources (placeholder - implement from analytics events)
        $trafficSources = $this->getTrafficSources($buyerId, $sellerId, $tenantId);

        // Get last category (placeholder)
        $lastCategory = $this->getLastCategory($buyerId, $sellerId, $tenantId);

        // Get geo data (placeholder)
        $geoData = $this->getGeoData($buyerId, $tenantId);

        // Calculate future labels for training (if enough historical data)
        $trainingLabels = $this->calculateTrainingLabels(
            $buyerId,
            $sellerId,
            $tenantId,
            $hundredEightyDaysAgo,
        );

        // Update or create feature record
        BuyerSellerFeatures::query()->updateOrCreate(
            [
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'tenant_id' => $tenantId,
            ],
            [
                'r_score' => $rScore,
                'f_score' => $fScore,
                'm_score' => $mScore,
                'recency_days' => $recencyDays,
                'last_purchase_at' => $lastPurchase,
                'frequency_90d' => $frequency90d,
                'frequency_180d' => $frequency180d,
                'frequency_365d' => $frequency365d,
                'monetary_90d' => $monetary90d,
                'monetary_180d' => $monetary180d,
                'monetary_365d' => $monetary365d,
                'avg_order_value' => $avgOrderValue,
                'first_purchase_at' => $firstPurchase,
                'days_since_first_purchase' => $daysSinceFirstPurchase,
                'total_orders_all_time' => $totalOrders,
                'total_monetary_all_time' => $totalMonetary,
                'return_rate' => $returnRate,
                'review_score' => $reviewData['score'],
                'total_reviews' => $reviewData['count'],
                'traffic_search_pct' => $trafficSources['search'],
                'traffic_recommendation_pct' => $trafficSources['recommendation'],
                'traffic_direct_pct' => $trafficSources['direct'],
                'traffic_other_pct' => $trafficSources['other'],
                'last_category' => $lastCategory,
                'geo_region' => $geoData['region'],
                'geo_city' => $geoData['city'],
                'actual_monetary_180d' => $trainingLabels['monetary_180d'],
                'actual_monetary_365d' => $trainingLabels['monetary_365d'],
                'churned_180d' => $trainingLabels['churned'],
                'updated_at' => $now,
            ]
        );
    }

    private function calculateRScore(?int $recencyDays): ?int
    {
        if ($recencyDays === null) {
            return null;
        }

        if ($recencyDays <= 30) return 5;
        if ($recencyDays <= 60) return 4;
        if ($recencyDays <= 90) return 3;
        if ($recencyDays <= 180) return 2;
        return 1;
    }

    private function calculateFScore(int $frequency180d): int
    {
        if ($frequency180d >= 10) return 5;
        if ($frequency180d >= 5) return 4;
        if ($frequency180d >= 3) return 3;
        if ($frequency180d >= 1) return 2;
        return 1;
    }

    private function calculateMScore(float $monetary180d): int
    {
        if ($monetary180d >= 50000) return 5;
        if ($monetary180d >= 20000) return 4;
        if ($monetary180d >= 5000) return 3;
        if ($monetary180d >= 1000) return 2;
        return 1;
    }

    private function calculateReturnRate(int $buyerId, int $sellerId, int $tenantId): float
    {
        $totalOrders = DB::table('orders')
            ->where('user_id', $buyerId)
            ->where('business_group_id', $sellerId)
            ->where('tenant_id', $tenantId)
            ->where('payment_status', 'paid')
            ->count();

        if ($totalOrders === 0) {
            return 0.0;
        }

        $returnedOrders = DB::table('orders')
            ->where('user_id', $buyerId)
            ->where('business_group_id', $sellerId)
            ->where('tenant_id', $tenantId)
            ->where('refund_status', '!=', null)
            ->count();

        return round(($returnedOrders / $totalOrders) * 100, 2);
    }

    private function getReviewScore(int $buyerId, int $sellerId, int $tenantId): array
    {
        // TODO: Implement actual review query
        // Placeholder implementation
        return [
            'score' => null,
            'count' => 0,
        ];
    }

    private function getTrafficSources(int $buyerId, int $sellerId, int $tenantId): array
    {
        // TODO: Implement actual traffic source aggregation from analytics events
        // Placeholder implementation
        return [
            'search' => 33.33,
            'recommendation' => 33.33,
            'direct' => 33.34,
            'other' => 0.0,
        ];
    }

    private function getLastCategory(int $buyerId, int $sellerId, int $tenantId): ?string
    {
        // TODO: Implement actual category query from order items
        return null;
    }

    private function getGeoData(int $buyerId, int $tenantId): array
    {
        // TODO: Implement actual geo data query from user profile or orders
        return [
            'region' => null,
            'city' => null,
        ];
    }

    private function calculateTrainingLabels(
        int $buyerId,
        int $sellerId,
        int $tenantId,
        \Carbon\Carbon $cutoffDate,
    ): array {
        // Calculate actual future spending after cutoff date (for training)
        $futureOrders = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('seller_id', $sellerId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $cutoffDate)
            ->get();

        $monetary180d = (float) $futureOrders
            ->where('created_at', '<=', $cutoffDate->copy()->addDays(180))
            ->sum('total_amount');

        $monetary365d = (float) $futureOrders
            ->where('created_at', '<=', $cutoffDate->copy()->addDays(365))
            ->sum('total_amount');

        $churned = $futureOrders->isEmpty();

        return [
            'monetary_180d' => $monetary180d > 0 ? $monetary180d : null,
            'monetary_365d' => $monetary365d > 0 ? $monetary365d : null,
            'churned' => $churned,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateBuyerFeaturesJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        $this->logAction('calculate_buyer_features_failed', $exception->getMessage());
    }
}
