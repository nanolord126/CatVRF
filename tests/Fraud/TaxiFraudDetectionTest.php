<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\Entities\Driver;
use Modules\Taxi\Domain\Enums\RideStatus;
use Illuminate\Support\Facades\Cache;

final class TaxiFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_ride_cancellation(): void
    {
        $driver = Driver::factory()->create();
        $userId = 1;

        // Driver cancelling rides to manipulate ratings
        for ($i = 0; $i < 20; $i++) {
            $ride = Ride::create([
                'driver_id' => $driver->id,
                'user_id' => $userId + $i,
                'tenant_id' => 1,
                'pickup_location' => 'Location A',
                'dropoff_location' => 'Location B',
                'amount' => 500,
                'status' => RideStatus::Accepted,
            ]);

            // Cancel after acceptance
            $ride->update([
                'status' => RideStatus::Cancelled,
                'cancelled_by' => 'driver',
                'cancelled_at' => now()->addSeconds(10),
            ]);
        }

        $this->fraudControl->checkRideManipulation([
            'driver_id' => $driver->id,
            'cancel_count' => 20,
            'cancel_rate' => 100,
            'avg_cancel_time_seconds' => 10,
        ]);

        $this->assertFraudAlertCreated(
            'driver',
            $driver->id,
            FraudType::ServiceManipulation,
            FraudSeverity::High
        );
    }

    public function test_route_manipulation(): void
    {
        $driver = Driver::factory()->create();
        $userId = 1;

        // Simulate driver taking longer routes to inflate fare
        $this->fraudControl->checkRouteFraud([
            'driver_id' => $driver->id,
            'ride_id' => 1,
            'expected_distance_km' => 5.0,
            'actual_distance_km' => 15.0,
            'expected_duration_minutes' => 15,
            'actual_duration_minutes' => 45,
            'fare_multiplier' => 3.0,
        ]);

        $this->assertFraudAlertCreated(
            'driver',
            $driver->id,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }

    public function test_driver_rating_manipulation(): void
    {
        $driver = Driver::factory()->create();
        $deviceFingerprint = 'fp_taxi_bot';

        // Fake 5-star reviews from bot accounts
        for ($i = 0; $i < 30; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'driver',
                'entity_id' => $driver->id,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Excellent driver!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'driver',
            $driver->id,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_ride_hoarding(): void
    {
        $userId = 1;

        // User booking multiple rides simultaneously
        for ($i = 0; $i < 5; $i++) {
            Ride::create([
                'user_id' => $userId,
                'driver_id' => Driver::factory()->create()->id,
                'tenant_id' => 1,
                'pickup_location' => "Location $i",
                'dropoff_location' => "Destination $i",
                'amount' => 500,
                'status' => RideStatus::Booked,
                'scheduled_at' => now()->addMinutes(10),
            ]);
        }

        $this->fraudControl->checkResourceHoarding([
            'user_id' => $userId,
            'resource_type' => 'ride',
            'resource_count' => 5,
            'time_window_minutes' => 10,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceHoarding,
            FraudSeverity::Medium
        );
    }

    public function test_surge_pricing_manipulation(): void
    {
        $driver = Driver::factory()->create();

        // Simulate driver manipulating app to trigger surge pricing
        $this->fraudControl->checkSurgeManipulation([
            'driver_id' => $driver->id,
            'location' => 'City Center',
            'reported_demand' => 'critical',
            'actual_available_drivers' => 50,
            'surge_multiplier' => 3.5,
        ]);

        $this->assertFraudAlertCreated(
            'driver',
            $driver->id,
            FraudType::PriceManipulation,
            FraudSeverity::Critical
        );
    }
}
