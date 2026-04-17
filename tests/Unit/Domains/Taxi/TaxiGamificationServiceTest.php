<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiDriverStats;
use App\Domains\Taxi\Services\TaxiGamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxiGamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private readonly TaxiGamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiGamificationService::class);
    }

    public function test_award_ride_completion_increases_stats(): void
    {
        $driver = TaxiDriver::factory()->create([
            'id' => 1,
            'tenant_id' => 1,
            'current_streak' => 0,
        ]);

        TaxiDriverStats::factory()->create([
            'driver_id' => 1,
            'tenant_id' => 1,
            'rides_completed' => 10,
            'total_earnings' => 10000,
        ]);

        $this->service->awardRideCompletion(
            driverId: 1,
            ridePrice: 50000,
            distanceKm: 10.5,
            durationMinutes: 25,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $stats = TaxiDriverStats::where('driver_id', 1)->first();
        $this->assertEquals(11, $stats->rides_completed);
        $this->assertEquals(60000, $stats->total_earnings);
        $this->assertEquals(10.5, $stats->total_distance_km);
        $this->assertEquals(25, $stats->total_time_minutes);
    }

    public function test_award_streak_bonus_increases_streak(): void
    {
        $driver = TaxiDriver::factory()->create([
            'id' => 1,
            'tenant_id' => 1,
            'current_streak' => 4,
            'max_streak' => 4,
        ]);

        $this->service->awardStreakBonus(
            driverId: 1,
            streakCount: 5,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $driver->refresh();
        $this->assertEquals(5, $driver->current_streak);
        $this->assertEquals(5, $driver->max_streak);
    }

    public function test_award_achievement_creates_achievement_record(): void
    {
        $driver = TaxiDriver::factory()->create([
            'id' => 1,
            'tenant_id' => 1,
        ]);

        $this->service->awardAchievement(
            driverId: 1,
            achievementCode: 'first_100_rides',
            achievementName: 'First 100 Rides',
            achievementDescription: 'Completed 100 rides',
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $this->assertDatabaseHas('taxi_driver_achievements', [
            'driver_id' => 1,
            'achievement_code' => 'first_100_rides',
            'achievement_name' => 'First 100 Rides',
        ]);
    }

    public function test_calculate_streak_bonus_returns_correct_amount(): void
    {
        $bonus = $this->service->calculateStreakBonus(5);

        $this->assertGreaterThan(0, $bonus);
        $this->assertIsInt($bonus);
    }

    public function test_reset_streak_on_cancellation(): void
    {
        $driver = TaxiDriver::factory()->create([
            'id' => 1,
            'tenant_id' => 1,
            'current_streak' => 5,
        ]);

        $this->service->resetStreak(
            driverId: 1,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $driver->refresh();
        $this->assertEquals(0, $driver->current_streak);
    }

    public function test_get_driver_leaderboard_returns_sorted_results(): void
    {
        TaxiDriver::factory()->create([
            'id' => 1,
            'tenant_id' => 1,
            'rating' => 4.5,
            'total_rides' => 50,
        ]);

        TaxiDriver::factory()->create([
            'id' => 2,
            'tenant_id' => 1,
            'rating' => 4.9,
            'total_rides' => 100,
        ]);

        TaxiDriver::factory()->create([
            'id' => 3,
            'tenant_id' => 1,
            'rating' => 4.7,
            'total_rides' => 75,
        ]);

        $leaderboard = $this->service->getDriverLeaderboard(
            tenantId: 1,
            limit: 10,
            correlationId: 'test-correlation-123',
        );

        $this->assertCount(3, $leaderboard);
        $this->assertEquals(4.9, $leaderboard[0]->rating);
        $this->assertEquals(100, $leaderboard[0]->total_rides);
    }
}
