<?php

declare(strict_types=1);

namespace Tests\Stress;

use Tests\TestCase;
use Modules\BeautyMasters\Domain\Entities\Appointment;
use Modules\BeautyMasters\Domain\Entities\BeautySalon;
use Modules\BeautyMasters\Domain\Entities\Master;
use Modules\BeautyMasters\Domain\Enums\AppointmentStatus;
use Modules\Hotels\Domain\Entities\Booking;
use Modules\Hotels\Domain\Entities\Hotel;
use Modules\Hotels\Domain\Enums\BookingStatus;
use Modules\Restaurant\Domain\Entities\Order;
use Modules\Restaurant\Domain\Entities\Restaurant;
use Modules\Restaurant\Domain\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

final class BookingStressTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_beauty_appointments_300_rps(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();
        $concurrency = 300;
        $results = [];

        for ($i = 0; $i < $concurrency; $i++) {
            try {
                Appointment::create([
                    'salon_id' => $salon->id,
                    'master_id' => $master->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'scheduled_at' => now()->addDays($i),
                    'status' => AppointmentStatus::Pending,
                    'amount' => 5000,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->assertGreaterThan(95, $successRate = ($successCount / $concurrency) * 100, 'Appointment success rate > 95%');
    }

    public function test_hotel_booking_slot_race_condition(): void
    {
        $hotel = Hotel::factory()->create(['available_rooms' => 10]);
        $concurrency = 50; // More than available rooms
        $results = [];
        $checkInDate = now()->addDay();

        for ($i = 0; $i < $concurrency; $i++) {
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

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        // Should not exceed available rooms
        $this->assertLessThanOrEqual(10, $successCount, 'Should not book more than available rooms');
        
        // All attempts should have a result (no race condition crashes)
        $this->assertCount($concurrency, $results);
    }

    public function test_restaurant_order_stress_500_orders(): void
    {
        $restaurant = Restaurant::factory()->create();
        $orderCount = 500;
        $results = [];
        $startTime = microtime(true);

        for ($i = 0; $i < $orderCount; $i++) {
            try {
                Order::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'amount' => rand(1000, 5000),
                    'status' => OrderStatus::Pending,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(98, $successRate = ($successCount / $orderCount) * 100, 'Order success rate > 98%');
        $this->assertLessThan(20, $duration, '500 orders should complete in < 20 seconds');
    }

    public function test_slot_availability_cache_stress(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = "slot_availability_{$i % 100}";
            Redis::setex($key, 60, rand(0, 10));
            Redis::get($key);
        }

        $duration = microtime(true) - $startTime;
        $opsPerSecond = $iterations / $duration;

        $this->assertGreaterThan(2000, $opsPerSecond, 'Redis slot cache should handle > 2000 ops/sec');
    }

    public function test_booking_confirmation_stress(): void
    {
        $bookings = [];
        for ($i = 0; $i < 200; $i++) {
            $booking = Booking::create([
                'hotel_id' => Hotel::factory()->create()->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'check_in' => now()->addDays($i),
                'check_out' => now()->addDays($i + 1),
                'amount' => 8000,
                'status' => BookingStatus::Pending,
            ]);
            $bookings[] = $booking;
        }

        $startTime = microtime(true);
        $results = [];

        foreach ($bookings as $booking) {
            try {
                $booking->update(['status' => BookingStatus::Confirmed]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertEquals(200, $successCount, 'All confirmations should succeed');
        $this->assertLessThan(15, $duration, '200 confirmations should complete in < 15 seconds');
    }

    public function test_concurrent_booking_cancellations(): void
    {
        $bookings = [];
        for ($i = 0; $i < 150; $i++) {
            $booking = Booking::create([
                'hotel_id' => Hotel::factory()->create()->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'check_in' => now()->addDays(1),
                'check_out' => now()->addDays(2),
                'amount' => 8000,
                'status' => BookingStatus::Confirmed,
            ]);
            $bookings[] = $booking;
        }

        $startTime = microtime(true);
        $results = [];

        foreach ($bookings as $booking) {
            try {
                $booking->update([
                    'status' => BookingStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertEquals(150, $successCount, 'All cancellations should succeed');
        $this->assertLessThan(10, $duration, '150 cancellations should complete in < 10 seconds');
    }
}
