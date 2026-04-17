<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class CalculateTrendScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly int $tries;
    public readonly int $timeout;

    public function __construct(
        public int $productId,
        public string $correlationId,
        int $tries = 3,
        int $timeout = 120,
    ) {
        $this->tries = $tries;
        $this->timeout = $timeout;
    }

    public function handle(): void
    {
        try {
            $trendScore = $this->calculateTrendScore();
            $demandForecast = $this->forecastDemand();

            $this->saveTrendData($trendScore, $demandForecast);

            Log::channel('audit')->info('Trend score calculated successfully', [
                'product_id' => $this->productId,
                'trend_score' => $trendScore,
                'demand_velocity' => $demandForecast['velocity'],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to calculate trend score', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    private function calculateTrendScore(): float
    {
        $views = $this->getViewsCount();
        $addToCarts = $this->getAddToCartsCount();
        $purchases = $this->getPurchasesCount();
        $socialMentions = $this->getSocialMentionsCount();
        $returns = $this->getReturnsCount();

        $baseScore = 0.0;
        $baseScore += min($views / 1000, 0.25);
        $baseScore += min($addToCarts / 100, 0.25);
        $baseScore += min($purchases / 50, 0.30);
        $baseScore += min($socialMentions / 20, 0.15);
        $baseScore -= min($returns / 10, 0.05);

        return max(0.0, min($baseScore, 1.0));
    }

    private function getViewsCount(): int
    {
        return DB::table('product_views')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }

    private function getAddToCartsCount(): int
    {
        return DB::table('cart_items')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }

    private function getPurchasesCount(): int
    {
        return DB::table('order_items')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }

    private function getSocialMentionsCount(): int
    {
        return DB::table('fashion_social_mentions')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
    }

    private function getReturnsCount(): int
    {
        return DB::table('fashion_returns')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
    }

    private function forecastDemand(): array
    {
        $historicalSales = $this->getHistoricalSales();
        $totalSales = array_sum(array_column($historicalSales, 'sales'));
        $avgDailySales = count($historicalSales) > 0 ? $totalSales / count($historicalSales) : 0;
        $velocity = min($avgDailySales / 10, 1.0);

        $trend = $this->calculateTrendDirection($historicalSales);
        $seasonalFactor = $this->calculateSeasonalFactor();
        $priceElasticity = $this->calculatePriceElasticity();

        return [
            'velocity' => $velocity,
            'trend' => $trend,
            'avg_daily_sales' => $avgDailySales,
            'seasonal_factor' => $seasonalFactor,
            'price_elasticity' => $priceElasticity,
            'confidence' => $this->calculateForecastConfidence($historicalSales),
        ];
    }

    private function getHistoricalSales(): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->productId)
            ->where('order_items.created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(order_items.created_at) as date, COUNT(*) as sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function calculateTrendDirection(array $historicalSales): string
    {
        if (count($historicalSales) < 2) {
            return 'stable';
        }

        $recentSales = $historicalSales[count($historicalSales) - 1]['sales'] ?? 0;
        $previousSales = $historicalSales[count($historicalSales) - 2]['sales'] ?? 0;

        if ($recentSales > $previousSales * 1.2) {
            return 'increasing';
        } elseif ($recentSales < $previousSales * 0.8) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function calculateSeasonalFactor(): float
    {
        $currentMonth = Carbon::now()->month;
        $seasonalFactors = [
            1 => 0.8, 2 => 0.7, 3 => 0.9, 4 => 1.0,
            5 => 1.1, 6 => 1.2, 7 => 1.3, 8 => 1.2,
            9 => 1.1, 10 => 1.0, 11 => 1.3, 12 => 1.4,
        ];

        return $seasonalFactors[$currentMonth] ?? 1.0;
    }

    private function calculatePriceElasticity(): float
    {
        $priceHistory = DB::table('fashion_dynamic_pricing')
            ->where('product_id', $this->productId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('created_at')
            ->get()
            ->toArray();

        if (count($priceHistory) < 2) {
            return -1.0;
        }

        $priceChanges = [];
        $demandChanges = [];

        for ($i = 1; $i < count($priceHistory); $i++) {
            $priceChange = ($priceHistory[$i]['dynamic_price'] - $priceHistory[$i - 1]['dynamic_price']) / $priceHistory[$i - 1]['dynamic_price'];
            $demandChange = $this->getDemandChangeBetweenPrices($priceHistory[$i - 1], $priceHistory[$i]);

            if ($priceChange !== 0.0 && $demandChange !== 0.0) {
                $priceChanges[] = $priceChange;
                $demandChanges[] = $demandChange;
            }
        }

        if (empty($priceChanges)) {
            return -1.0;
        }

        $avgPriceChange = array_sum($priceChanges) / count($priceChanges);
        $avgDemandChange = array_sum($demandChanges) / count($demandChanges);

        return $avgPriceChange !== 0.0 ? $avgDemandChange / $avgPriceChange : -1.0;
    }

    private function getDemandChangeBetweenPrices(array $pricePoint1, array $pricePoint2): float
    {
        $sales1 = $this->getSalesBetweenDates(
            Carbon::parse($pricePoint1['created_at']),
            Carbon::parse($pricePoint2['created_at'])
        );
        $sales2 = $this->getSalesBetweenDates(
            Carbon::parse($pricePoint2['created_at']),
            Carbon::parse($pricePoint2['created_at'])->addDays(7)
        );

        return $sales1 !== 0 ? ($sales2 - $sales1) / $sales1 : 0.0;
    }

    private function getSalesBetweenDates(Carbon $start, Carbon $end): int
    {
        return DB::table('order_items')
            ->where('product_id', $this->productId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function calculateForecastConfidence(array $historicalSales): float
    {
        if (count($historicalSales) < 7) {
            return 0.3;
        }

        $salesValues = array_column($historicalSales, 'sales');
        $mean = array_sum($salesValues) / count($salesValues);
        $variance = 0.0;

        foreach ($salesValues as $value) {
            $variance += pow($value - $mean, 2);
        }

        $variance /= count($salesValues);
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $mean !== 0 ? $stdDev / $mean : 1.0;

        return max(0.0, min(1.0, 1.0 - $coefficientOfVariation));
    }

    private function saveTrendData(float $trendScore, array $demandForecast): void
    {
        DB::table('fashion_trend_scores')->updateOrInsert(
            ['product_id' => $this->productId],
            [
                'trend_score' => $trendScore,
                'demand_velocity' => $demandForecast['velocity'],
                'demand_trend' => $demandForecast['trend'],
                'seasonal_factor' => $demandForecast['seasonal_factor'],
                'price_elasticity' => $demandForecast['price_elasticity'],
                'forecast_confidence' => $demandForecast['confidence'],
                'correlation_id' => $this->correlationId,
                'updated_at' => Carbon::now(),
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('CalculateTrendScoreJob failed', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
