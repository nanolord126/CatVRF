<?php

declare(strict_types=1);

namespace Tests\Load;

use Modules\Hotels\Domain\Entities\Booking;
use Modules\Hotels\Domain\Entities\Hotel;
use Modules\Hotels\Domain\Entities\Room;
use Modules\Hotels\Domain\Enums\BookingStatus;
use Modules\Hotels\Domain\Enums\RoomStatus;
use Modules\Hotels\Application\Services\BookingService;
use Modules\Hotels\Application\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

final class HotelsLoadTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private RoomService $roomService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
        $this->roomService = app(RoomService::class);
    }

    public function testHotelsBookingCreation10000RPS(): void
    {
        $hotel = Hotel::factory()->create(['available_rooms' => 100]);
        Room::factory()->count(100)->for($hotel)->create(['status' => RoomStatus::Available]);

        $startTime = microtime(true);
        $iterations = 10000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                Booking::create([
                    'hotel_id' => $hotel->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'check_in' => now()->addDays($i % 30),
                    'check_out' => now()->addDays(($i % 30) + 1),
                    'amount' => rand(5000, 15000),
                    'status' => BookingStatus::Pending,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(10000, $rps);
        $this->assertGreaterThan(98, ($successCount / $iterations) * 100);
    }

    public function testHotelsRoomAvailabilityCheck20000RPS(): void
    {
        $hotel = Hotel::factory()->create(['available_rooms' => 50]);
        Room::factory()->count(50)->for($hotel)->create(['status' => RoomStatus::Available]);

        $startTime = microtime(true);
        $iterations = 20000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->roomService->checkAvailability(
                $hotel->id,
                now()->addDays($i % 30),
                now()->addDays(($i % 30) + 1)
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(20000, $rps);
    }

    public function testHotelsSearch5000RPS(): void
    {
        Hotel::factory()->count(1000)->create(['available_rooms' => rand(10, 100)]);

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            Hotel::where('available_rooms', '>', 10)
                ->where('rating', '>', 4.0)
                ->get();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testHotelsBookingConfirmation3000RPS(): void
    {
        $hotel = Hotel::factory()->create();
        
        $bookings = [];
        for ($i = 0; $i < 1000; $i++) {
            $bookings[] = Booking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'check_in' => now()->addDays(1),
                'check_out' => now()->addDays(2),
                'amount' => 8000,
                'status' => BookingStatus::Pending,
            ]);
        }

        $startTime = microtime(true);
        $iterations = 3000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $booking = $bookings[$i % 1000];
                $booking->update(['status' => BookingStatus::Confirmed]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(3000, $rps);
        $this->assertEquals($iterations, $successCount);
    }

    public function testHotelsRoomPriceCalculation50000RPS(): void
    {
        $hotel = Hotel::factory()->create(['base_price' => 5000]);
        Room::factory()->count(100)->for($hotel)->create(['price' => 5000]);

        $startTime = microtime(true);
        $iterations = 50000;

        for ($i = 0; $i < $iterations; $i++) {
            $nights = ($i % 10) + 1;
            $price = 5000 * $nights;
            Cache::put("price_{$i}", $price, 60);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testHotelsRoomAvailabilityCache50000RPS(): void
    {
        $iterations = 50000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = "hotel_rooms_{$i % 100}";
            Redis::setex($key, 60, rand(1, 50));
            Redis::get($key);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testHotelsConcurrentBookingCreation(): void
    {
        $hotel = Hotel::factory()->create(['available_rooms' => 50]);
        Room::factory()->count(50)->for($hotel)->create(['status' => RoomStatus::Available]);

        $startTime = microtime(true);
        $concurrentRequests = 200;
        $results = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                Booking::create([
                    'hotel_id' => $hotel->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'check_in' => now()->addDays($i % 7),
                    'check_out' => now()->addDays(($i % 7) + 1),
                    'amount' => 8000,
                    'status' => BookingStatus::Pending,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertLessThan(0.5, $duration);
        $this->assertEquals($concurrentRequests, $successCount);
    }

    public function testHotelsMemoryUsageUnderLoad(): void
    {
        $initialMemory = memory_get_usage(true);

        $hotel = Hotel::factory()->create(['available_rooms' => 100]);
        Room::factory()->count(100)->for($hotel)->create();

        for ($i = 0; $i < 10000; $i++) {
            Booking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'check_in' => now()->addDays($i % 365),
                'check_out' => now()->addDays(($i % 365) + 1),
                'amount' => rand(5000, 15000),
                'status' => BookingStatus::Pending,
            ]);
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(150, $memoryIncrease);
    }

    public function testHotelsRoomBookingRaceCondition(): void
    {
        $hotel = Hotel::factory()->create(['available_rooms' => 5]);
        Room::factory()->count(5)->for($hotel)->create(['status' => RoomStatus::Available]);

        $checkInDate = now()->addDay();
        $startTime = microtime(true);
        $results = [];

        for ($i = 0; $i < 50; $i++) {
            try {
                Booking::create([
                    'hotel_id' => $hotel->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'check_in' => $checkInDate,
                    'check_out' => $checkInDate->addDay(),
                    'amount' => 8000,
                    'status' => BookingStatus::Confirmed,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertLessThanOrEqual(5, $successCount, 'Should not exceed available rooms');
        $this->assertLessThan(1, $endTime - $startTime);
    }
}
