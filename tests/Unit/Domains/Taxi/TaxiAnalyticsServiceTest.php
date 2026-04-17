<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\Services\TaxiAnalyticsService;
use App\Domains\Taxi\Models\TaxiAnalyticsDaily;
use App\Domains\Taxi\Models\TaxiDriverAnalytics;
use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

final class TaxiAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxiAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = new TaxiAnalyticsService(
            $this->app->make('db'),
        );
    }

    public function test_aggregate_daily_analytics(): void
    {
        $date = Carbon::today();
        
        TaxiRide::factory()->count(10)->create([
            'status' => TaxiRide::STATUS_COMPLETED,
            'total_price' => 50000,
            'distance_km' => 10,
            'created_at' => $date,
        ]);

        TaxiRide::factory()->count(2)->create([
            'status' => TaxiRide::STATUS_CANCELLED,
            'total_price' => 50000,
            'created_at' => $date,
        ]);

        $analytics = $this->analyticsService->aggregateDailyAnalytics($date, 'test-correlation');

        $this->assertInstanceOf(TaxiAnalyticsDaily::class, $analytics);
        $this->assertEquals(12, $analytics->total_rides);
        $this->assertEquals(10, $analytics->completed_rides);
        $this->assertEquals(2, $analytics->cancelled_rides);
        $this->assertEquals(600000, $analytics->total_revenue_kopeki); // 12 * 50000
    }

    public function test_aggregate_driver_analytics(): void
    {
        $driver = Driver::factory()->create();
        $date = Carbon::today();
        
        TaxiRide::factory()->count(5)->create([
            'driver_id' => $driver->id,
            'status' => TaxiRide::STATUS_COMPLETED,
            'total_price' => 30000,
            'distance_km' => 8,
            'created_at' => $date,
        ]);

        $analytics = $this->analyticsService->aggregateDriverAnalytics($driver->id, $date, 'test-correlation');

        $this->assertInstanceOf(TaxiDriverAnalytics::class, $analytics);
        $this->assertEquals(5, $analytics->total_rides);
        $this->assertEquals(5, $analytics->completed_rides);
        $this->assertEquals(150000, $analytics->total_revenue_kopeki); // 5 * 30000
    }

    public function test_get_revenue_analytics(): void
    {
        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            TaxiAnalyticsDaily::factory()->create([
                'date' => $date,
                'total_rides' => 100 + $i,
                'completed_rides' => 95 + $i,
                'total_revenue_kopeki' => 500000 + ($i * 10000),
            ]);
        }

        $analytics = $this->analyticsService->getRevenueAnalytics($startDate, $endDate);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('total_revenue_rubles', $analytics);
        $this->assertArrayHasKey('total_rides', $analytics);
        $this->assertArrayHasKey('average_completion_rate', $analytics);
        $this->assertEquals(7, $analytics['period']['days']);
    }

    public function test_get_driver_performance_report(): void
    {
        $driver = Driver::factory()->create(['rating' => 4.5]);
        $startDate = Carbon::today()->subDays(30);
        $endDate = Carbon::today();
        
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            TaxiDriverAnalytics::factory()->create([
                'driver_id' => $driver->id,
                'date' => $date,
                'total_rides' => 10,
                'completed_rides' => 9,
                'total_revenue_kopeki' => 50000,
                'online_minutes' => 480,
            ]);
        }

        $report = $this->analyticsService->getDriverPerformanceReport($driver->id, $startDate, $endDate);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('driver', $report);
        $this->assertArrayHasKey('performance', $report);
        $this->assertEquals(4.5, $report['driver']['rating']);
        $this->assertEquals(300, $report['performance']['total_rides']); // 30 * 10
    }

    public function test_predict_demand(): void
    {
        $date = Carbon::today()->addWeek();
        
        // Create historical data for same day of week
        for ($i = 1; $i <= 4; $i++) {
            TaxiAnalyticsDaily::factory()->create([
                'date' => Carbon::today()->subWeeks($i),
                'total_rides' => 100 + ($i * 10),
                'total_revenue_kopeki' => 500000,
            ]);
        }

        $prediction = $this->analyticsService->predictDemand($date);

        $this->assertIsArray($prediction);
        $this->assertArrayHasKey('predicted_rides', $prediction);
        $this->assertArrayHasKey('predicted_revenue_rubles', $prediction);
        $this->assertArrayHasKey('confidence', $prediction);
        $this->assertGreaterThan(0, $prediction['predicted_rides']);
    }

    public function test_get_completion_rate(): void
    {
        $analytics = TaxiAnalyticsDaily::factory()->create([
            'total_rides' => 100,
            'completed_rides' => 95,
            'cancelled_rides' => 5,
        ]);

        $this->assertEquals(95.0, $analytics->getCompletionRate());
    }

    public function test_get_cancellation_rate(): void
    {
        $analytics = TaxiAnalyticsDaily::factory()->create([
            'total_rides' => 100,
            'completed_rides' => 95,
            'cancelled_rides' => 5,
        ]);

        $this->assertEquals(5.0, $analytics->getCancellationRate());
    }

    public function test_get_net_income_kopeki(): void
    {
        $analytics = TaxiDriverAnalytics::factory()->create([
            'total_revenue_kopeki' => 50000,
            'total_tips_kopeki' => 5000,
            'bonus_kopeki' => 2000,
            'penalty_kopeki' => 500,
        ]);

        $this->assertEquals(56500, $analytics->getNetIncomeKopeki()); // 50000 + 5000 + 2000 - 500
    }

    public function test_get_average_hourly_earnings(): void
    {
        $analytics = TaxiDriverAnalytics::factory()->create([
            'total_revenue_kopeki' => 30000,
            'online_minutes' => 480, // 8 hours
        ]);

        $this->assertEquals(7.81, round($analytics->getAverageHourlyEarningsRubles(), 2));
    }
}
