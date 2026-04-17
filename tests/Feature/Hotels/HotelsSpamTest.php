<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HotelsSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_booking_requests_gets_rate_limited(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson('/api/v1/hotels/bookings', [
                'hotel_id' => 1,
                'room_id' => 1,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'guests' => 2,
            ], [
                'X-Correlation-ID' => "spam-{$i}",
            ]);
        }

        $this->assertContains($response->status(), [429, 403]);
    }

    public function test_suspicious_device_gets_flagged(): void
    {
        Cache::put('fraud:device:suspicious-device', true, 3600);

        $response = $this->postJson('/api/v1/hotels/bookings', [
            'hotel_id' => 1,
            'room_id' => 1,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ], [
            'X-Device-Fingerprint' => 'suspicious-device',
            'X-Correlation-ID' => 'test-suspicious',
        ]);

        $this->assertContains($response->status(), [403, 429]);
    }
}
