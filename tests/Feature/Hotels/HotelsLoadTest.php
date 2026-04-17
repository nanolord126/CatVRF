<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HotelsLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_100_concurrent_bookings(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->postJson('/api/v1/hotels/bookings', [
                'hotel_id' => 1,
                'room_id' => $i % 10 + 1,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'guests' => 2,
            ], [
                'X-Correlation-ID' => "load-{$i}",
            ]);
        }

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(15, $duration);
    }

    public function test_price_calculation_performance(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            $this->postJson('/api/v1/hotels/calculate-price', [
                'hotel_id' => 1,
                'room_id' => 1,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'guests' => 2,
            ], [
                'X-Correlation-ID' => "price-{$i}",
            ]);
        }

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(10, $duration);
    }
}
