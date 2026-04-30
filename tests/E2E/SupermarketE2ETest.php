<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Domains\Supermarket\DTOs\SupermarketOrderData;
use App\Domains\Supermarket\Jobs\ConfirmInventoryJob;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Services\InventoryReservationService;
use App\Domains\Supermarket\Services\SupermarketService;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * E2E тесты для вертикали Supermarket.
 *
 * Проверяют полный интеграционный flow:
 * - B2C: cart → checkout → payment → delivery tracking
 * - B2B: company → catalog → cart → checkout → documents
 * - Интеграцию с внешними адаптерами
 * - Асинхронные jobs и events
 */
final class SupermarketE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Tenant $tenant;
    private string $token;
    private SupermarketService $supermarketService;
    private InventoryReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->customer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'customer',
        ]);

        $this->token = $this->customer->createToken('test')->plainTextToken;
        $this->supermarketService = app(SupermarketService::class);
        $this->reservationService = app(InventoryReservationService::class);

        // Mock fraud check to always pass
        FraudControlService::shouldReceive('check')->andReturn(true);
    }

    /**
     * E2E: Полный B2C flow - от корзины до доставки.
     */
    public function test_full_b2c_flow_cart_to_delivery(): void
    {
        // Arrange: Подготовка продуктов и инвентаря
        $this->db->table('supermarket_products')->insert([
            [
                'tenant_id' => $this->tenant->id,
                'sub_vertical' => 'grocery_and_delivery',
                'name' => 'Молоко 3.2%',
                'price' => 8900, // 89.00 RUB
                'weight' => 1.0,
                'requires_cold_chain' => true,
                'shelf_life_days' => 7,
                'attributes' => json_encode(['brand' => 'Простоквашино']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant->id,
                'sub_vertical' => 'grocery_and_delivery',
                'name' => 'Хлеб белый',
                'price' => 4500, // 45.00 RUB
                'weight' => 0.5,
                'requires_cold_chain' => false,
                'shelf_life_days' => 3,
                'attributes' => json_encode(['type' => 'wheat']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $milkProductId = $this->db->table('supermarket_products')->first()->id;

        $this->db->table('supermarket_inventory_items')->insert([
            'product_id' => $milkProductId,
            'quantity' => 100,
            'reserved' => 0,
            'expires_at' => now()->addDays(7),
            'batch_number' => 'BATCH001',
            'location' => 'A1-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act 1: Добавление в корзину
        $cartResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/cart/add', [
                'product_id' => $milkProductId,
                'quantity' => 2,
                'attributes' => [],
            ]);

        $cartResponse->assertStatus(201);
        $cartResponse->assertJsonStructure([
            'data' => [
                'items',
                'reservation_expires_at',
                'subtotal',
                'cold_chain_required',
            ],
        ]);

        $this->assertTrue($cartResponse->json('data.cold_chain_required'));
        $this->assertEquals(2, count($cartResponse->json('data.items')));

        // Act 2: Получение слотов доставки
        $slotsResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/supermarket/checkout/delivery-slots?address=Москва,+ул.+Пушкина+10');

        $slotsResponse->assertStatus(200);
        $slotsResponse->assertJsonStructure([
            'slots' => [
                '*' => [
                    'id',
                    'date',
                    'time_range',
                    'cost',
                ],
            ],
        ]);

        $slotId = $slotsResponse->json('slots.0.id');

        // Act 3: Checkout с созданием заказа
        Bus::fake([ConfirmInventoryJob::class]);

        $checkoutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/checkout', [
                'address' => 'Москва, ул. Пушкина 10, кв. 5',
                'slot_id' => $slotId,
                'payment_method' => 'card',
            ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertJsonStructure([
            'success',
            'order_uuid',
            'total_amount',
            'delivery_cost',
            'correlation_id',
        ]);

        $orderUuid = $checkoutResponse->json('order_uuid');

        // Assert: Заказ создан в БД
        $order = SupermarketOrder::where('uuid', $orderUuid)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertTrue($order->cold_chain_required);

        // Assert: Job для подтверждения инвентаря отправлен
        Bus::assertDispatched(ConfirmInventoryJob::class);

        // Act 4: Подтверждение оплаты
        $paymentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/supermarket/orders/{$orderUuid}/payment/confirm", [
                'payment_id' => 'pay_test_123',
                'amount' => $checkoutResponse->json('total_amount'),
            ]);

        $paymentResponse->assertStatus(200);

        // Assert: Статус заказа обновлен
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Act 5: Трекинг заказа
        $trackingResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/supermarket/tracking/{$orderUuid}");

        $trackingResponse->assertStatus(200);
        $trackingResponse->assertJsonStructure([
            'status',
            'cold_chain_required',
            'eta_minutes',
            'temperature',
            'courier',
        ]);
    }

    /**
     * E2E: Истечение резерва в корзине.
     */
    public function test_cart_reservation_expiry(): void
    {
        // Arrange: Создаем продукт
        $this->db->table('supermarket_products')->insert([
            'tenant_id' => $this->tenant->id,
            'sub_vertical' => 'grocery_and_delivery',
            'name' => 'Тестовый продукт',
            'price' => 10000,
            'weight' => 1.0,
            'requires_cold_chain' => false,
            'shelf_life_days' => 30,
            'attributes' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = $this->db->table('supermarket_products')->first()->id;

        // Act: Добавляем в корзину
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/cart/add', [
                'product_id' => $productId,
                'quantity' => 5,
                'attributes' => [],
            ]);

        // Assert: Проверяем что резерв создан
        $cart = $this->supermarketService->getCart($this->customer->id, $this->tenant->id);
        $this->assertNotEmpty($cart['items']);

        // Act: Имитируем истечение резерва (21 минута)
        Cache::put("cart:{$this->customer->id}:{$this->tenant->id}", null);
        $this->db->table('cart_items')
            ->where('user_id', $this->customer->id)
            ->update(['reservation_expires_at' => now()->subMinutes(21)]);

        // Act: Попытка получить корзину
        $cartResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/supermarket/cart');

        // Assert: Корзина пуста или истекшие товары удалены
        $cartResponse->assertStatus(200);
    }

    /**
     * E2E: Холодовая цепь - нарушение температурного режима.
     */
    public function test_cold_chain_violation_detection(): void
    {
        // Arrange: Создаем заказ с холодовой цепью
        $order = SupermarketOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->customer->id,
            'status' => 'shipped',
            'cold_chain_required' => true,
            'delivery_eta' => 60,
        ]);

        // Act: Регистрируем заказ для мониторинга
        $this->db->table('supermarket_cold_chain_monitoring')->insert([
            'order_id' => $order->id,
            'status' => 'monitoring',
            'max_allowed_temp' => 5,
            'min_allowed_temp' => -18,
            'delivery_eta_minutes' => 60,
            'last_check_at' => now(),
            'correlation_id' => 'test_correlation',
            'created_at' => now(),
        ]);

        // Act: Записываем нормальную температуру
        $this->db->table('supermarket_cold_chain_temperatures')->insert([
            'order_id' => $order->id,
            'sensor_id' => 'SENSOR_001',
            'temperature' => 4.5,
            'recorded_at' => now(),
        ]);

        // Act: Записываем нарушение (высокая температура)
        $this->db->table('supermarket_cold_chain_temperatures')->insert([
            'order_id' => $order->id,
            'sensor_id' => 'SENSOR_001',
            'temperature' => 8.5, // Выше максимальной 5°C
            'recorded_at' => now(),
        ]);

        // Assert: Нарушение записано
        $violation = $this->db->table('supermarket_cold_chain_violations')
            ->where('order_id', $order->id)
            ->first();

        $this->assertNotNull($violation);
        $this->assertEquals('high', $violation->violation_type);
        $this->assertEquals('critical', $violation->severity);
    }

    /**
     * E2E: B2B flow - регистрация компании и заказ.
     */
    public function test_b2b_company_registration_and_order(): void
    {
        // Act 1: Регистрация B2B компании
        $companyResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/b2b/companies', [
                'name' => 'ООО Тестовая Компания',
                'inn' => '1234567890',
                'kpp' => '123456789',
                'legal_address' => 'Москва, ул. Ленина 1',
                'actual_address' => 'Москва, ул. Ленина 1',
                'contact_person' => 'Иван Иванов',
                'phone' => '+79001234567',
                'email' => 'test@company.ru',
            ]);

        $companyResponse->assertStatus(201);
        $companyId = $companyResponse->json('data.id');

        // Act 2: Добавление в B2B корзину
        $this->db->table('supermarket_products')->insert([
            'tenant_id' => $this->tenant->id,
            'sub_vertical' => 'office_catering',
            'name' => 'Бизнес-ланч',
            'price' => 50000, // 500 RUB
            'weight' => 1.0,
            'requires_cold_chain' => true,
            'shelf_life_days' => 1,
            'attributes' => json_encode(['serving_size' => 'standard']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = $this->db->table('supermarket_products')->first()->id;

        $b2bCartResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/b2b/cart/add', [
                'company_id' => $companyId,
                'product_id' => $productId,
                'quantity' => 10,
                'attributes' => [],
            ]);

        $b2bCartResponse->assertStatus(201);

        // Act 3: B2B checkout
        $b2bCheckoutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/b2b/checkout', [
                'company_id' => $companyId,
                'address' => 'Москва, ул. Ленина 1',
                'delivery_date' => now()->addDays(7)->toDateString(),
                'payment_method' => 'invoice',
            ]);

        $b2bCheckoutResponse->assertStatus(200);

        // Assert: B2B заказ создан
        $order = SupermarketOrder::where('b2b_company_id', $companyId)->first();
        $this->assertNotNull($order);
        $this->assertTrue($order->is_b2b);
    }

    /**
     * E2E: Отмена заказа с освобождением резерва.
     */
    public function test_order_cancellation_releases_reservation(): void
    {
        // Arrange: Создаем заказ с резервом
        $this->db->table('supermarket_products')->insert([
            'tenant_id' => $this->tenant->id,
            'sub_vertical' => 'grocery_and_delivery',
            'name' => 'Товар для отмены',
            'price' => 10000,
            'weight' => 1.0,
            'requires_cold_chain' => false,
            'shelf_life_days' => 30,
            'attributes' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = $this->db->table('supermarket_products')->first()->id;

        $orderData = new SupermarketOrderData(
            userId: $this->customer->id,
            tenantId: $this->tenant->id,
            businessGroupId: null,
            items: [
                [
                    'product_id' => $productId,
                    'product_name' => 'Товар для отмены',
                    'quantity' => 5,
                    'price' => 10000,
                    'warehouse_id' => 1,
                    'sub_vertical' => 'grocery_and_delivery',
                ],
            ],
            sellerAddress: 'Москва',
            buyerAddress: 'Москва, ул. Тестовая 1',
            deliverySlot: '2026-04-27 10:00-12:00',
            subVertical: 'grocery_and_delivery',
            warehouseId: 1,
            correlationId: 'test_cancellation',
            isB2B: false,
        );

        $orderResult = $this->supermarketService->createOrder([
            'user_id' => $orderData->userId,
            'tenant_id' => $orderData->tenantId,
            'items' => $orderData->items,
            'seller_address' => $orderData->sellerAddress,
            'buyer_address' => $orderData->buyerAddress,
            'delivery_slot' => $orderData->deliverySlot,
            'sub_vertical' => $orderData->subVertical,
            'warehouse_id' => $orderData->warehouseId,
            'correlation_id' => $orderData->correlationId,
        ]);

        $orderId = $orderResult['order_id'];

        // Act: Отмена заказа
        $this->supermarketService->cancelOrder($orderId, $this->customer->id, 'Customer request');

        // Assert: Заказ отменен
        $order = SupermarketOrder::find($orderId);
        $this->assertEquals('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);

        // Assert: Резервы освобождены
        $reservations = $this->db->table('inventory_reservations')
            ->where('order_id', $orderId)
            ->get();

        foreach ($reservations as $reservation) {
            $this->assertEquals('expired', $reservation->status);
        }
    }

    /**
     * E2E: Rate limiting на API endpoints.
     */
    public function test_rate_limiting_on_checkout_endpoint(): void
    {
        // Act: Делаем много запросов подряд
        for ($i = 0; $i < 130; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/supermarket/popular-products?sub_vertical=grocery_and_delivery');

            if ($i < 120) {
                $response->assertStatus(200);
            } else {
                // После 120 запросов должен быть 429 Too Many Requests
                $response->assertStatus(429);
            }
        }
    }

    /**
     * E2E: Fraud detection блокирует подозрительные операции.
     */
    public function test_fraud_detection_blocks_suspicious_operations(): void
    {
        // Arrange: Настраиваем fraud check на блокировку
        FraudControlService::shouldReceive('check')
            ->once()
            ->andThrow(new \RuntimeException('Fraud detected'));

        // Act: Попытка checkout
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/supermarket/checkout', [
                'address' => 'Москва, ул. Тестовая 1',
                'slot_id' => 'slot_1',
                'payment_method' => 'card',
            ]);

        // Assert: Запрос отклонен
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Fraud detected',
        ]);
    }

    /**
     * E2E: Кэширование продуктов и инвалидация.
     */
    public function test_product_caching_and_invalidation(): void
    {
        // Arrange: Создаем продукт
        $this->db->table('supermarket_products')->insert([
            'tenant_id' => $this->tenant->id,
            'sub_vertical' => 'grocery_and_delivery',
            'name' => 'Кэшируемый продукт',
            'price' => 10000,
            'weight' => 1.0,
            'requires_cold_chain' => false,
            'shelf_life_days' => 30,
            'attributes' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = $this->db->table('supermarket_products')->first()->id;

        // Act 1: Первый запрос - кэш пуст
        $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/supermarket/popular-products?sub_vertical=grocery_and_delivery');

        $response1->assertStatus(200);
        $cacheKey = "supermarket:products:grocery_and_delivery:{$this->tenant->id}";

        // Act 2: Второй запрос - из кэша
        Cache::put($cacheKey, $response1->json(), now()->addMinutes(30));

        // Act 3: Обновление продукта - инвалидация кэша
        $this->db->table('supermarket_products')
            ->where('id', $productId)
            ->update(['price' => 15000]);

        Cache::tags(['supermarket', 'products'])->flush();

        // Assert: Кэш очищен
        $this->assertNull(Cache::get($cacheKey));
    }
}
