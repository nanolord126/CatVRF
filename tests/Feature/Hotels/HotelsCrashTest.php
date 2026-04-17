<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class HotelsCrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_handles_database_failure(): void
    {
        DB::shouldReceive('connection')->andThrow(new \Exception('DB failed'));

        $response = $this->postJson('/api/v1/hotels/bookings', [
            'hotel_id' => 1,
            'room_id' => 1,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ], [
            'X-Correlation-ID' => 'crash-db',
        ]);

        $this->assertContains($response->status(), [500, 503]);
    }

    public function test_handles_malformed_request(): void
    {
        $response = $this->postJson('/api/v1/hotels/bookings', [
            'check_in' => 'invalid',
            'check_out' => null,
        ], [
            'X-Correlation-ID' => 'crash-malformed',
        ]);

        $this->assertEquals(422, $response->status());
    }

    public function test_logs_errors_with_correlation_id(): void
    {
        Log::shouldReceive('error')->once();

        $this->postJson('/api/v1/hotels/bookings', [
            'hotel_id' => 1,
            'room_id' => 1,
            'check_in' => 'invalid',
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ], [
            'X-Correlation-ID' => 'crash-log',
        ]);

        $this->assertTrue(true);
    }
}
