<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Domain\Entities\SellerMetrics;

/**
 * Aggregate Seller Metrics Job
 *
 * Aggregates metrics per seller for the seller analytics dashboard.
 * Runs on a schedule after daily metrics aggregation.
 */
final readonly class AggregateSellerMetricsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $sellerId,
        public CarbonImmutable $date,
    ) {}

    public function uniqueId(): string
    {
        return "aggregate_seller_metrics_{$this->tenantId}_{$this->sellerId}_{$this->date->toDateString()}";
    }

    public function handle(): void
    {
        Log::info('Aggregating seller metrics', [
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'date' => $this->date->toDateString(),
        ]);

        DB::transaction(function () {
            // Aggregate events for this seller
            $events = DB::table('analytics_events')
                ->where('tenant_id', $this->tenantId)
                ->where('seller_id', $this->sellerId)
                ->whereDate('occurred_at', $this->date->toDateString())
                ->get();

            $ordersCount = 0;
            $ordersRevenue = 0.0;
            $productsViewed = 0;
            $productsSold = 0;
            $uniqueCustomers = 0;
            $refundsCount = 0;
            $refundsAmount = 0.0;
            $customerIds = [];

            foreach ($events as $event) {
                match ($event->event_type) {
                    'order.placed', 'order.completed' => $ordersCount++,
                    'payment.successful' => $ordersRevenue += (float) $event->monetary_value,
                    'product.viewed' => $productsViewed++,
                    'product.purchased' => $productsSold++,
                    'refund.processed' => $refundsCount++,
                    default => null,
                };

                if ($event->user_id && !in_array($event->user_id, $customerIds, true)) {
                    $customerIds[] = $event->user_id;
                }

                if ($event->monetary_value && $event->event_type === 'refund.processed') {
                    $refundsAmount += (float) $event->monetary_value;
                }
            }

            $uniqueCustomers = count($customerIds);

            // Get seller rating from reviews table (TODO: implement)
            $sellerRating = 0.0;

            // Create seller metrics entity
            $sellerMetrics = SellerMetrics::create(
                tenantId: $this->tenantId,
                sellerId: $this->sellerId,
                date: $this->date,
                ordersCount: $ordersCount,
                ordersRevenue: $ordersRevenue,
                productsViewed: $productsViewed,
                productsSold: $productsSold,
                uniqueCustomers: $uniqueCustomers,
                refundsCount: $refundsCount,
                refundsAmount: $refundsAmount,
                sellerRating: $sellerRating,
            );

            // Check if metrics already exist
            $existing = DB::table('analytics_seller_metrics')
                ->where('tenant_id', $this->tenantId)
                ->where('seller_id', $this->sellerId)
                ->where('date', $this->date->toDateString())
                ->first();

            if ($existing) {
                // Update existing
                DB::table('analytics_seller_metrics')
                    ->where('id', $existing->id)
                    ->update([
                        'orders_count' => DB::raw('orders_count + ' . $ordersCount),
                        'orders_revenue' => DB::raw('orders_revenue + ' . $ordersRevenue),
                        'orders_aov' => ($existing->orders_count + $ordersCount) > 0
                            ? (($existing->orders_revenue + $ordersRevenue) / ($existing->orders_count + $ordersCount))
                            : 0,
                        'products_viewed' => DB::raw('products_viewed + ' . $productsViewed),
                        'products_sold' => DB::raw('products_sold + ' . $productsSold),
                        'unique_customers' => DB::raw('unique_customers + ' . $uniqueCustomers),
                        'conversion_rate' => ($existing->products_viewed + $productsViewed) > 0
                            ? ((($existing->orders_count + $ordersCount) / ($existing->products_viewed + $productsViewed)) * 100)
                            : 0,
                        'refunds_count' => DB::raw('refunds_count + ' . $refundsCount),
                        'refunds_amount' => DB::raw('refunds_amount + ' . $refundsAmount),
                        'calculated_at' => CarbonImmutable::now(),
                        'updated_at' => CarbonImmutable::now(),
                    ]);
            } else {
                // Insert new
                DB::table('analytics_seller_metrics')->insert([
                    'tenant_id' => $this->tenantId,
                    'seller_id' => $this->sellerId,
                    'date' => $this->date->toDateString(),
                    'orders_count' => $ordersCount,
                    'orders_revenue' => $ordersRevenue,
                    'orders_aov' => $ordersCount > 0 ? $ordersRevenue / $ordersCount : 0,
                    'products_viewed' => $productsViewed,
                    'products_sold' => $productsSold,
                    'unique_customers' => $uniqueCustomers,
                    'conversion_rate' => $productsViewed > 0 ? ($ordersCount / $productsViewed) * 100 : 0,
                    'refunds_count' => $refundsCount,
                    'refunds_amount' => $refundsAmount,
                    'seller_rating' => $sellerRating,
                    'calculated_at' => CarbonImmutable::now(),
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);
            }
        });

        Log::info('Seller metrics aggregation completed', [
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'date' => $this->date->toDateString(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Seller metrics aggregation failed', [
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'date' => $this->date->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}
