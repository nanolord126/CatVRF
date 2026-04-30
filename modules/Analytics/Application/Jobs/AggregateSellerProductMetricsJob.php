<?php

declare(strict_types=1);

namespace Modules\Analytics\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Models\ProductMetrics;

/**
 * Aggregate Seller Product Metrics Job
 *
 * Aggregates daily metrics for all products from orders and events.
 * Runs daily via cron to populate analytics_product_metrics table.
 *
 * Performance: Uses bulk upsert for efficiency with many products.
 * TODO: Implement incremental aggregation for real-time updates.
 */
final class AggregateSellerProductMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly ?string $date = null,
        private readonly ?int $tenantId = null,
        private readonly ?int $sellerId = null,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(AuditService $auditService): void
    {
        $this->auditService = $auditService;
        $date = $this->date ?? Carbon::yesterday()->toDateString();

        Log::info('Aggregating seller product metrics', [
            'date' => $date,
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
        ]);

        // Fraud check - first action
        $this->logAction('aggregate_product_metrics', [
            'date' => $date,
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
        ]);

        // Get all products with activity on this date
        $query = DB::table('order_items')
            ->select('product_id', 'seller_id', 'tenant_id')
            ->whereDate('created_at', $date)
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'seller_id', 'tenant_id');

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        if ($this->sellerId !== null) {
            $query->where('seller_id', $this->sellerId);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $this->aggregateForProduct($product->product_id, $product->seller_id, $product->tenant_id, $date);
        }

        // Also aggregate products that were viewed but not purchased
        $this->aggregateViewedProducts($date, $this->tenantId, $this->sellerId);

        Log::info('Seller product metrics aggregated', [
            'date' => $date,
            'products_count' => $products->count(),
        ]);

        // TODO: Invalidate cache for affected sellers
    }

    /**
     * Aggregate metrics for a specific product and date.
     */
    private function aggregateForProduct(int $productId, int $sellerId, int $tenantId, string $date): void
    {
        // Get order metrics
        $orderMetrics = DB::table('order_items')
            ->where('product_id', $productId)
            ->whereHas('order', fn ($q) => $q
                ->where('seller_id', $sellerId)
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', $date)
            )
            ->selectRaw('
                SUM(quantity) as purchases,
                SUM(quantity * price) as revenue,
                COUNT(DISTINCT order_id) as order_count
            ')
            ->first();

        // Get engagement metrics from analytics_events
        $engagement = DB::table('analytics_events')
            ->where('product_id', $productId)
            ->where('seller_id', $sellerId)
            ->where('tenant_id', $tenantId)
            ->whereDate('occurred_at', $date)
            ->selectRaw('
                SUM(CASE WHEN event_type = 'product_viewed' THEN 1 ELSE 0 END) as views,
                SUM(CASE WHEN event_type = 'product_viewed' THEN 1 ELSE 0 END) as unique_viewers,
                SUM(CASE WHEN event_type = 'added_to_cart' THEN 1 ELSE 0 END) as add_to_cart
            ')
            ->first();

        // Get refund metrics
        $refunds = DB::table('refund_items')
            ->where('product_id', $productId)
            ->whereHas('refund', fn ($q) => $q
                ->where('seller_id', $sellerId)
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', $date)
            )
            ->selectRaw('
                SUM(quantity) as refunds,
                SUM(amount) as refund_amount
            ')
            ->first();

        // Calculate conversion rates
        $conversionRate = ($engagement->views ?? 0) > 0
            ? (($orderMetrics->purchases ?? 0) / ($engagement->views ?? 1)) * 100
            : 0;

        $cartConversionRate = ($engagement->add_to_cart ?? 0) > 0
            ? (($orderMetrics->purchases ?? 0) / ($engagement->add_to_cart ?? 1)) * 100
            : 0;

        $refundRate = ($orderMetrics->purchases ?? 0) > 0
            ? (($refunds->refunds ?? 0) / ($orderMetrics->purchases ?? 1)) * 100
            : 0;

        // Get average rating
        $avgRating = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->avg('rating') ?? 0;

        // Upsert metrics
        ProductMetrics::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'date' => $date,
            ],
            [
                'seller_id' => $sellerId,
                'views' => $engagement->views ?? 0,
                'add_to_cart' => $engagement->add_to_cart ?? 0,
                'purchases' => $orderMetrics->purchases ?? 0,
                'unique_viewers' => $engagement->unique_viewers ?? 0,
                'revenue' => $orderMetrics->revenue ?? 0,
                'conversion_rate' => $conversionRate,
                'cart_conversion_rate' => $cartConversionRate,
                'refunds' => $refunds->refunds ?? 0,
                'refund_rate' => $refundRate,
                'avg_rating' => $avgRating,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Aggregate products that were viewed but not purchased.
     */
    private function aggregateViewedProducts(string $date, ?int $tenantId, ?int $sellerId): void
    {
        $query = DB::table('analytics_events')
            ->select('product_id', 'seller_id', 'tenant_id')
            ->where('event_type', 'product_viewed')
            ->whereDate('occurred_at', $date)
            ->whereNotIn('product_id', function ($q) use ($date) {
                $q->select('product_id')
                    ->from('order_items')
                    ->whereDate('created_at', $date);
            })
            ->groupBy('product_id', 'seller_id', 'tenant_id');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($sellerId !== null) {
            $query->where('seller_id', $sellerId);
        }

        $viewedProducts = $query->get();

        foreach ($viewedProducts as $product) {
            $engagement = DB::table('analytics_events')
                ->where('product_id', $product->product_id)
                ->where('seller_id', $product->seller_id)
                ->where('tenant_id', $product->tenant_id)
                ->whereDate('occurred_at', $date)
                ->selectRaw('
                    SUM(CASE WHEN event_type = 'product_viewed' THEN 1 ELSE 0 END) as views,
                    SUM(CASE WHEN event_type = 'product_viewed' THEN 1 ELSE 0 END) as unique_viewers,
                    SUM(CASE WHEN event_type = 'added_to_cart' THEN 1 ELSE 0 END) as add_to_cart
                ')
                ->first();

            ProductMetrics::query()->updateOrCreate(
                [
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->product_id,
                    'date' => $date,
                ],
                [
                    'seller_id' => $product->seller_id,
                    'views' => $engagement->views ?? 0,
                    'add_to_cart' => $engagement->add_to_cart ?? 0,
                    'purchases' => 0,
                    'unique_viewers' => $engagement->unique_viewers ?? 0,
                    'revenue' => 0,
                    'conversion_rate' => 0,
                    'cart_conversion_rate' => 0,
                    'refunds' => 0,
                    'refund_rate' => 0,
                    'avg_rating' => 0,
                    'calculated_at' => now(),
                ]
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to aggregate seller product metrics', [
            'date' => $this->date,
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'error' => $exception->getMessage(),
        ]);

        $this->logAction('aggregate_product_metrics_failed', [
            'date' => $this->date,
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
