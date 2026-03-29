<?php declare(strict_types=1);

namespace Tests\Feature\GroceryAndDelivery;

use App\Domains\GroceryAndDelivery\Models\{GroceryStore, GroceryProduct, GroceryOrder, DeliverySlot};
use App\Domains\GroceryAndDelivery\Services\GroceryOrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

final class GroceryOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private GroceryOrderService $orderService;
    private User $user;
    private GroceryStore $store;
    private array $items;

    protected function setUp(): void
    {
        parent::setUp();

        // Инициализация сервиса
        $this->orderService = app(GroceryOrderService::class);

        // Создание тестовых данных
        $this->user = User::factory()->create();
        $this->store = GroceryStore::factory()->create(['tenant_id' => tenant()->id]);

        // Создание тестовых товаров
        $product1 = GroceryProduct::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Яблоки',
            'price' => 10000, // 100 руб
            'current_stock' => 100,
        ]);

        $product2 = GroceryProduct::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Молоко',
            'price' => 8000, // 80 руб
            'current_stock' => 50,
        ]);

        $this->items = [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 1],
        ];
    }

    /**
     * Тест: создание заказа успешно
     */
    public function test_user_can_create_grocery_order(): void
    {
        $slot = DeliverySlot::factory()->create(['store_id' => $this->store->id]);
        $correlationId = (string) Str::uuid();

        // Act
        $order = $this->orderService->createOrder(
            userId: $this->user->id,
            storeId: $this->store->id,
            deliverySlotId: $slot->id,
            items: $this->items,
            lat: 55.7558,
            lon: 37.6173,
            correlationId: $correlationId,
        );

        // Assert
        $this->assertNotNull($order->id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($this->store->id, $order->store_id);
        $this->assertEquals($this->user->id, $order->user_id);
        $this->assertEquals(28000, $order->total_price); // 2*100 + 1*80 = 280 руб
        $this->assertDatabaseHas('grocery_orders', [
            'id' => $order->id,
            'status' => 'pending',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Тест: подтверждение заказа
     */
    public function test_order_can_be_confirmed(): void
    {
        $order = GroceryOrder::factory()->create(['store_id' => $this->store->id]);
        $correlationId = (string) Str::uuid();

        // Act
        $confirmed = $this->orderService->confirmOrder($order, $correlationId);

        // Assert
        $this->assertEquals('confirmed', $confirmed->status);
        $this->assertEquals($correlationId, $confirmed->correlation_id);
    }

    /**
     * Тест: завершение заказа и доставка
     */
    public function test_order_can_be_completed(): void
    {
        $order = GroceryOrder::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'confirmed',
            'total_price' => 28000,
            'commission_amount' => 3920, // 14%
        ]);

        // Добавить товары в заказ
        foreach ($this->items as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price_per_unit' => GroceryProduct::find($item['product_id'])->price,
                'total_price' => $item['quantity'] * GroceryProduct::find($item['product_id'])->price,
            ]);
        }

        $correlationId = (string) Str::uuid();

        // Mock InventoryManagementService и WalletService
        $this->mock('App\Services\InventoryManagementService', function ($mock) {
            $mock->shouldReceive('deductStock')->times(2);
        });

        $this->mock('App\Services\WalletService', function ($mock) {
            $mock->shouldReceive('credit')->once();
        });

        // Act
        $completed = $this->orderService->completeOrder($order, $correlationId);

        // Assert
        $this->assertEquals('delivered', $completed->status);
        $this->assertNotNull($completed->delivered_at);
    }

    /**
     * Тест: отмена заказа освобождает холдованные товары
     */
    public function test_cancelled_order_releases_held_stock(): void
    {
        $slot = DeliverySlot::factory()->create(['store_id' => $this->store->id]);
        $correlationId = (string) Str::uuid();

        // Mock Inventory Service для холда
        $this->mock('App\Services\InventoryManagementService', function ($mock) {
            $mock->shouldReceive('reserveStock')->times(2);
            $mock->shouldReceive('releaseStock')->times(2);
        });

        // Создать заказ
        $order = $this->orderService->createOrder(
            userId: $this->user->id,
            storeId: $this->store->id,
            deliverySlotId: $slot->id,
            items: $this->items,
            lat: 55.7558,
            lon: 37.6173,
            correlationId: $correlationId,
        );

        // Act
        $cancelled = $this->orderService->cancelOrder($order, 'user_requested', $correlationId);

        // Assert
        $this->assertEquals('cancelled', $cancelled->status);
    }

    /**
     * Тест: доступные слоты доставки по времени
     */
    public function test_can_get_available_delivery_slots(): void
    {
        $today = now();
        $availableSlot = DeliverySlot::factory()->create([
            'store_id' => $this->store->id,
            'start_time' => $today->copy()->setHours(10, 0),
            'end_time' => $today->copy()->setHours(11, 0),
            'is_available' => true,
            'max_orders' => 10,
            'current_orders' => 5,
        ]);

        $fullSlot = DeliverySlot::factory()->create([
            'store_id' => $this->store->id,
            'start_time' => $today->copy()->setHours(12, 0),
            'end_time' => $today->copy()->setHours(13, 0),
            'is_available' => false,
            'max_orders' => 10,
            'current_orders' => 10,
        ]);

        // Act
        $service = app('App\Domains\GroceryAndDelivery\Services\DeliverySlotManagementService');
        $slots = $service->getAvailableSlots($this->store->id, $today);

        // Assert
        $this->assertCount(1, $slots);
        $this->assertEquals($availableSlot->id, $slots->first()->id);
    }

    /**
     * Тест: B2B и B2C режимы для магазинов
     */
    public function test_store_respects_b2b_settings(): void
    {
        $b2cStore = GroceryStore::factory()->create(['tenant_id' => tenant()->id]);
        $b2bStore = GroceryStore::factory()->create([
            'tenant_id' => tenant()->id,
            'is_b2b_available' => true,
        ]);

        // В B2C режиме - оба магазина видны
        $this->assertEquals(2, GroceryStore::where('is_active', true)->count());

        // В B2B режиме - только B2B магазин
        $b2bStores = GroceryStore::where('is_b2b_available', true)->count();
        $this->assertGreaterThanOrEqual(1, $b2bStores);
    }

    /**
     * Тест: логирование всех операций
     */
    public function test_order_operations_are_logged(): void
    {
        $slot = DeliverySlot::factory()->create(['store_id' => $this->store->id]);
        $correlationId = (string) Str::uuid();

        // Mock логирования
        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->times(1);

        // Act
        $order = $this->orderService->createOrder(
            userId: $this->user->id,
            storeId: $this->store->id,
            deliverySlotId: $slot->id,
            items: $this->items,
            lat: 55.7558,
            lon: 37.6173,
            correlationId: $correlationId,
        );

        // Assert - корреляционный ID должен быть в логе
        $this->assertEquals($correlationId, $order->correlation_id);
    }
}
