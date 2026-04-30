<?php

declare(strict_types=1);

namespace Tests\Load;

use Modules\Restaurant\Domain\Entities\Order;
use Modules\Restaurant\Domain\Entities\Restaurant;
use Modules\Restaurant\Domain\Enums\OrderStatus;
use Modules\Restaurant\Application\Services\OrderService;
use Modules\Restaurant\Application\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

final class RestaurantLoadTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;
    private ReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
        $this->reservationService = app(ReservationService::class);
    }

    public function testRestaurantOrderCreation15000RPS(): void
    {
        $restaurant = Restaurant::factory()->create();

        $startTime = microtime(true);
        $iterations = 15000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                Order::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'amount' => rand(1000, 5000),
                    'status' => OrderStatus::Pending,
                    'items' => json_encode([['id' => 1, 'quantity' => 1]]),
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

        $this->assertGreaterThan(15000, $rps);
        $this->assertGreaterThan(99, ($successCount / $iterations) * 100);
    }

    public function testRestaurantMenuRetrieval20000RPS(): void
    {
        $restaurant = Restaurant::factory()->create();

        $startTime = microtime(true);
        $iterations = 20000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->orderService->getMenu($restaurant->id);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(20000, $rps);
    }

    public function testRestaurantSearch5000RPS(): void
    {
        Restaurant::factory()->count(1000)->create();

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            Restaurant::where('rating', '>', 4.0)
                ->where('is_active', true)
                ->get();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testRestaurantOrderStatusUpdate5000RPS(): void
    {
        $restaurant = Restaurant::factory()->create();
        
        $orders = [];
        for ($i = 0; $i < 1000; $i++) {
            $orders[] = Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 3000,
                'status' => OrderStatus::Pending,
                'items' => json_encode([['id' => 1, 'quantity' => 1]]),
            ]);
        }

        $startTime = microtime(true);
        $iterations = 5000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $order = $orders[$i % 1000];
                $order->update(['status' => OrderStatus::Preparing]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(5000, $rps);
        $this->assertEquals($iterations, $successCount);
    }

    public function testRestaurantReservationBooking3000RPS(): void
    {
        $restaurant = Restaurant::factory()->create(['capacity' => 50]);

        $startTime = microtime(true);
        $iterations = 3000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $this->reservationService->createReservation([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'date' => now()->addDays($i % 30),
                    'time' => '19:00',
                    'guests' => 2,
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

        $this->assertGreaterThan(3000, $rps);
        $this->assertGreaterThan(98, ($successCount / $iterations) * 100);
    }

    public function testRestaurantMenuCache50000RPS(): void
    {
        $iterations = 50000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = "restaurant_menu_{$i % 100}";
            Redis::setex($key, 60, json_encode(['id' => $i, 'name' => 'Item ' . $i]));
            Redis::get($key);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testRestaurantOrderTracking10000RPS(): void
    {
        $restaurant = Restaurant::factory()->create();
        
        $orders = [];
        for ($i = 0; $i < 1000; $i++) {
            $orders[] = Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 3000,
                'status' => OrderStatus::Preparing,
                'items' => json_encode([['id' => 1, 'quantity' => 1]]),
            ]);
        }

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $order = $orders[$i % 1000];
            $this->orderService->trackOrder($order->id);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(10000, $rps);
    }

    public function testRestaurantConcurrentOrderCreation(): void
    {
        $restaurant = Restaurant::factory()->create();

        $startTime = microtime(true);
        $concurrentRequests = 300;
        $results = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                Order::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'amount' => rand(1000, 5000),
                    'status' => OrderStatus::Pending,
                    'items' => json_encode([['id' => 1, 'quantity' => 1]]),
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

    public function testRestaurantMemoryUsageUnderLoad(): void
    {
        $initialMemory = memory_get_usage(true);

        $restaurant = Restaurant::factory()->create();

        for ($i = 0; $i < 10000; $i++) {
            Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => rand(1000, 5000),
                'status' => OrderStatus::Pending,
                'items' => json_encode([['id' => 1, 'quantity' => 1]]),
            ]);
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(150, $memoryIncrease);
    }

    public function testRestaurantKitchenDisplaySystemLoad(): void
    {
        $restaurant = Restaurant::factory()->create();
        
        $orders = [];
        for ($i = 0; $i < 500; $i++) {
            $orders[] = Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 3000,
                'status' => OrderStatus::Confirmed,
                'items' => json_encode([
                    ['id' => 1, 'quantity' => 2],
                    ['id' => 2, 'quantity' => 1],
                ]),
            ]);
        }

        $startTime = microtime(true);
        $iterations = 5000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $order = $orders[$i % 500];
                $this->orderService->updateOrderStatus($order->id, OrderStatus::Preparing);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(5000, $rps);
        $this->assertEquals($iterations, $successCount);
    }
}
