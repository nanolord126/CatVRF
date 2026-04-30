<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Application\Services\AnalyticsService;
use Modules\Analytics\Domain\Entities\DailyMetrics;

/**
 * Aggregate Daily Metrics Job
 *
 * Aggregates raw analytics events into daily metrics.
 * Runs on a schedule (e.g., every hour) to process events.
 * 
 * This job should be unique per tenant/date to prevent duplicate processing.
 */
final readonly class AggregateDailyMetricsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return "aggregate_daily_metrics_{$this->tenantId}_{$this->date->toDateString()}";
    }

    public function __construct(
        private readonly int $tenantId,
        private readonly CarbonImmutable $date,
    ) {}

    public function handle(AnalyticsService $analyticsService): void
    {
        Log::info('Aggregating daily metrics', [
            'tenant_id' => $this->tenantId,
            'date' => $this->date->toDateString(),
        ]);

        DB::transaction(function () use ($analyticsService) {
            // Aggregate events from analytics_events table
            $events = DB::table('analytics_events')
                ->where('tenant_id', $this->tenantId)
                ->whereDate('occurred_at', $this->date->toDateString())
                ->whereNull('processed_at')
                ->get();

            $ordersCount = 0;
            $ordersRevenue = 0.0;
            $usersActive = 0;
            $usersNew = 0;
            $productsViewed = 0;
            $productsAddedToCart = 0;
            $sellersActive = 0;
            $sessions = 0;
            $pageViews = 0;
            $refundsCount = 0;
            $refundsAmount = 0.0;

            foreach ($events as $event) {
                match ($event->event_type) {
                    'order.placed', 'order.completed' => $ordersCount++,
                    'payment.successful' => $ordersRevenue += (float) $event->monetary_value,
                    'user.signup' => $usersNew++,
                    'session.started' => $sessions++,
                    'page.viewed' => $pageViews++,
                    'product.viewed' => $productsViewed++,
                    'product.added_to_cart' => $productsAddedToCart++,
                    'seller.signup' => $sellersActive++,
                    'refund.processed' => $refundsCount++,
                    default => null,
                };

                if ($event->user_id && $event->event_type !== 'user.signup') {
                    $usersActive = max($usersActive, $event->user_id);
                }
            }

            // Create or update daily metrics
            $dailyMetrics = DailyMetrics::create(
                tenantId: $this->tenantId,
                date: $this->date,
                ordersCount: $ordersCount,
                ordersRevenue: $ordersRevenue,
                usersActive: $usersActive,
                usersNew: $usersNew,
                productsViewed: $productsViewed,
                productsAddedToCart: $productsAddedToCart,
                sellersActive: $sellersActive,
                sessions: $sessions,
                pageViews: $pageViews,
                refundsCount: $refundsCount,
                refundsAmount: $refundsAmount,
            );

            // Check if metrics already exist
            $existing = DB::table('analytics_daily_metrics')
                ->where('tenant_id', $this->tenantId)
                ->where('date', $this->date->toDateString())
                ->first();

            if ($existing) {
                // Update existing record
                DB::table('analytics_daily_metrics')
                    ->where('id', $existing->id)
                    ->update([
                        'orders_count' => DB::raw('orders_count + ' . $ordersCount),
                        'orders_revenue' => DB::raw('orders_revenue + ' . $ordersRevenue),
                        'users_active' => DB::raw('GREATEST(users_active, ' . $usersActive . ')'),
                        'users_new' => DB::raw('users_new + ' . $usersNew),
                        'products_viewed' => DB::raw('products_viewed + ' . $productsViewed),
                        'products_added_to_cart' => DB::raw('products_added_to_cart + ' . $productsAddedToCart),
                        'sellers_active' => DB::raw('GREATEST(sellers_active, ' . $sellersActive . ')'),
                        'sessions' => DB::raw('sessions + ' . $sessions),
                        'page_views' => DB::raw('page_views + ' . $pageViews),
                        'refunds_count' => DB::raw('refunds_count + ' . $refundsCount),
                        'refunds_amount' => DB::raw('refunds_amount + ' . $refundsAmount),
                        'calculated_at' => CarbonImmutable::now(),
                        'updated_at' => CarbonImmutable::now(),
                    ]);
            } else {
                // Insert new record
                DB::table('analytics_daily_metrics')->insert([
                    'tenant_id' => $this->tenantId,
                    'date' => $this->date->toDateString(),
                    'orders_count' => $ordersCount,
                    'orders_revenue' => $ordersRevenue,
                    'orders_aov' => $ordersCount > 0 ? $ordersRevenue / $ordersCount : 0,
                    'users_active' => $usersActive,
                    'users_new' => $usersNew,
                    'products_viewed' => $productsViewed,
                    'products_added_to_cart' => $productsAddedToCart,
                    'sellers_active' => $sellersActive,
                    'sessions' => $sessions,
                    'page_views' => $pageViews,
                    'gmv' => $ordersRevenue - $refundsAmount,
                    'refunds_count' => $refundsCount,
                    'refunds_amount' => $refundsAmount,
                    'conversion_rate' => $sessions > 0 ? ($ordersCount / $sessions) * 100 : 0,
                    'cart_abandonment_rate' => $productsAddedToCart > 0 ? (($productsAddedToCart - $ordersCount) / $productsAddedToCart) * 100 : 0,
                    'calculated_at' => CarbonImmutable::now(),
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);
            }

            // Mark events as processed
            DB::table('analytics_events')
                ->where('tenant_id', $this->tenantId)
                ->whereDate('occurred_at', $this->date->toDateString())
                ->whereNull('processed_at')
                ->update(['processed_at' => CarbonImmutable::now()]);

            // Invalidate cache
            // TODO: Implement cache invalidation
        });

        Log::info('Daily metrics aggregation completed', [
            'tenant_id' => $this->tenantId,
            'date' => $this->date->toDateString(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Daily metrics aggregation failed', [
            'tenant_id' => $this->tenantId,
            'date' => $this->date->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}
