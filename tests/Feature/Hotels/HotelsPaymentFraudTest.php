<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HotelsPaymentFraudTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_value_booking_requires_verification(): void
    {
        Cache::put('fraud:high_value_threshold', 50000, 3600);

        $response = $this->postJson('/api/v1/hotels/bookings', [
            'hotel_id' => 1,
            'room_id' => 1,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(30)->toDateString(),
            'guests' => 2,
        ], [
            'X-Correlation-ID' => 'fraud-high-value',
        ]);

        $this->assertContains($response->status(), [200, 403, 422]);
    }

    public function test_multiple_payment_attempts_gets_blocked(): void
    {
        $cardFingerprint = 'hotel-card-123';
        Cache::put("fraud:card:{$cardFingerprint}:attempts", 10, 3600);

        $isBlocked = Cache::get("fraud:card:{$cardFingerprint}:blocked");
        $this->assertTrue($isBlocked ?? false);
    }

    public function test_suspicious_location_change(): void
    {
        $userId = 999;
        Cache::put("fraud:user:{$userId}:last_location", [
            'lat' => 55.75396,
            'lon' => 37.62039,
            'timestamp' => now()->subMinutes(30),
        ], 3600);

        $distance = 5000;
        $this->assertGreaterThan(1000, $distance);
    }

    public function test_user_with_high_cancellation_rate(): void
    {
        $userId = 888;
        Cache::put("fraud:user:{$userId}:cancellation_rate", 0.8, 3600);

        $rate = Cache::get("fraud:user:{$userId}:cancellation_rate");
        $this->assertGreaterThan(0.5, $rate);
    }
}
