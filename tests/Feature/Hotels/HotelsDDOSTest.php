<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HotelsDDOSTest extends TestCase
{
    use RefreshDatabase;

    public function test_massive_concurrent_requests_gets_throttled(): void
    {
        $blockedCount = 0;

        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/v1/hotels/bookings', [
                'hotel_id' => 1,
                'room_id' => 1,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'guests' => 2,
            ], [
                'X-Forwarded-For' => '192.168.1.200',
                'X-Correlation-ID' => "ddos-{$i}",
            ]);

            if ($response->status() === 429 || $response->status() === 403) {
                $blockedCount++;
            }
        }

        $this->assertGreaterThan(80, $blockedCount);
    }

    public function test_ip_banned_after_excessive_requests(): void
    {
        Cache::put('ban:ip:192.168.1.250', true, 3600);

        $response = $this->postJson('/api/v1/hotels/bookings', [
            'hotel_id' => 1,
            'room_id' => 1,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ], [
            'X-Forwarded-For' => '192.168.1.250',
            'X-Correlation-ID' => 'banned-ip',
        ]);

        $this->assertEquals(403, $response->status());
    }
}
