<?php declare(strict_types=1);

namespace Tests\Feature\Api\Food;

use App\Domains\Food\Models\Dish;
use App\Domains\Food\Models\OrderItem;
use App\Domains\Food\Models\RestaurantOrder;
use App\Domains\Food\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FoodApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $restaurantOwner;
    protected Restaurant $restaurant;
    protected Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['is_business' => false]);
        $this->restaurantOwner = User::factory()->create(['is_business' => true]);

        $this->restaurant = Restaurant::factory()
            ->for($this->restaurantOwner, 'owner')
            ->create([
                'name' => 'Delicious Pizza',
                'cuisine_type' => ['pizza', 'pasta'],
                'is_active' => true,
                'is_b2c_available' => true,
            ]);

        $this->dish = Dish::factory()
            ->for($this->restaurant)
            ->create([
                'name' => 'Margherita Pizza',
                'price' => 50000,  // 500 руб
                'cooking_time_minutes' => 15,
                'calories' => 800,
                'allergens' => ['gluten', 'dairy'],
            ]);
    }

    /**
     * Тест: Клиент может получить список ресторанов с фильтром
     */
    public function test_customer_can_list_restaurants_with_filters(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/restaurants', [
                'cuisine' => 'pizza',
                'min_rating' => 4.0,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'cuisine_type',
                        'rating',
                        'estimated_delivery_time',
                        'min_order_price',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Тест: Клиент может просмотреть меню ресторана
     */
    public function test_customer_can_view_restaurant_menu(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/restaurants/{$this->restaurant->id}/menu");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'description',
                        'calories',
                        'allergens',
                        'cooking_time',
                    ],
                ],
            ]);
    }

    /**
     * Тест: Создание заказа с проверкой фрода
     */
    public function test_customer_can_create_order_with_fraud_check(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/restaurants/orders', [
                'restaurant_id' => $this->restaurant->id,
                'items' => [
                    [
                        'dish_id' => $this->dish->id,
                        'quantity' => 2,
                        'special_instructions' => 'No onions',
                    ],
                ],
                'delivery_address' => 'Главная ул., 1',
                'phone' => '+79991234567',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'correlation_id',
                    'total_price',
                    'estimated_delivery_time',
                    'items',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas(RestaurantOrder::class, [
            'user_id' => $this->customer->id,
            'restaurant_id' => $this->restaurant->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: Блокировка заказа при высокой фрод-оценке
     */
    public function test_order_blocked_on_high_fraud_score(): void
    {
        // Создать множество быстрых заказов (признак фрода)
        for ($i = 0; $i < 6; $i++) {
            RestaurantOrder::factory()
                ->for($this->customer)
                ->for($this->restaurant)
                ->create(['created_at' => now()->subMinutes(10 - $i)]);
        }

        // Попытка создать ещё один заказ - должна быть заблокирована
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/restaurants/orders', [
                'restaurant_id' => $this->restaurant->id,
                'items' => [
                    ['dish_id' => $this->dish->id, 'quantity' => 1],
                ],
                'delivery_address' => 'Главная ул., 1',
                'phone' => '+79991234567',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Order blocked due to fraud suspicion',
            ]);
    }

    /**
     * Тест: Расчет стоимости с комиссией (14%)
     */
    public function test_order_price_calculated_with_commission(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/restaurants/orders', [
                'restaurant_id' => $this->restaurant->id,
                'items' => [
                    [
                        'dish_id' => $this->dish->id,
                        'quantity' => 2,
                    ],
                ],
                'delivery_address' => 'Главная ул., 1',
            ]);

        $response->assertStatus(201);

        $totalPrice = $response->json('data.total_price');
        $dishPrice = 50000 * 2;  // 2 пиццы по 500 руб каждая = 1000 руб
        $commission = intval($dishPrice * 0.14);

        // Итого = цена + комиссия + доставка (если есть)
        $this->assertGreaterThanOrEqual($dishPrice + $commission, $totalPrice);
    }

    /**
     * Тест: Автоматическое списание расходников при завершении заказа
     */
    public function test_consumables_deducted_on_order_completion(): void
    {
        $order = RestaurantOrder::factory()
            ->for($this->customer)
            ->for($this->restaurant)
            ->create(['status' => 'ready_for_delivery']);

        // Добавить item с расходниками
        OrderItem::factory()
            ->for($order)
            ->for($this->dish)
            ->create();

        // Вызвать завершение заказа
        $response = $this->actingAs($this->restaurantOwner)
            ->patchJson("/api/v1/restaurants/orders/{$order->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas(RestaurantOrder::class, [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Тест: Rate limiting на создание заказов (10/24h)
     */
    public function test_order_creation_rate_limited(): void
    {
        $restaurants = Restaurant::factory(11)->create();

        // Создать 10 заказов (в лимите)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->customer)
                ->postJson('/api/v1/restaurants/orders', [
                    'restaurant_id' => $restaurants[$i]->id,
                    'items' => [
                        ['dish_id' => $this->dish->id, 'quantity' => 1],
                    ],
                    'delivery_address' => 'Главная ул., 1',
                ]);

            $response->assertStatus(201);
        }

        // 11-й заказ должен быть заблокирован
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/restaurants/orders', [
                'restaurant_id' => $restaurants[10]->id,
                'items' => [
                    ['dish_id' => $this->dish->id, 'quantity' => 1],
                ],
                'delivery_address' => 'Главная ул., 1',
            ]);

        $response->assertStatus(429);  // Too Many Requests
    }

    /**
     * Тест: Клиент может отменить заказ до готовки
     */
    public function test_customer_can_cancel_order_before_cooking(): void
    {
        $order = RestaurantOrder::factory()
            ->for($this->customer)
            ->for($this->restaurant)
            ->create(['status' => 'pending']);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/restaurants/orders/{$order->id}/cancel", [
                'reason' => 'Передумал',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas(RestaurantOrder::class, [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Тест: Штраф за отмену после начала готовки
     */
    public function test_cancellation_fee_after_cooking_started(): void
    {
        $order = RestaurantOrder::factory()
            ->for($this->customer)
            ->for($this->restaurant)
            ->create([
                'status' => 'cooking',
                'total_price' => 100000,
            ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/restaurants/orders/{$order->id}/cancel");

        $response->assertStatus(200);

        // Проверить, что применён штраф (50% от суммы)
        $refundAmount = $response->json('data.refund_amount');
        $this->assertEquals(50000, $refundAmount);  // 50% от 100000
    }

    /**
     * Тест: Отслеживание заказа в реальном времени
     */
    public function test_customer_can_track_order_in_realtime(): void
    {
        $order = RestaurantOrder::factory()
            ->for($this->customer)
            ->for($this->restaurant)
            ->create(['status' => 'cooking']);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/restaurants/orders/{$order->id}/track");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'estimated_ready_time',
                    'estimated_delivery_time',
                    'current_step',  // "accepted", "cooking", "ready", "on_the_way", "delivered"
                ],
            ]);
    }

    /**
     * Тест: Фильтр по аллергенам в меню
     */
    public function test_menu_filtered_by_allergens(): void
    {
        // Создать блюдо БЕЗ молочных продуктов
        $veganDish = Dish::factory()
            ->for($this->restaurant)
            ->create([
                'name' => 'Vegan Pizza',
                'allergens' => [],
            ]);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/restaurants/{$this->restaurant->id}/menu", [
                'exclude_allergens' => ['dairy'],
            ]);

        $response->assertStatus(200);
        $dishIds = $response->json('data.*.id');

        // Проверить, что блюдо WITH молочными продуктами не показано
        $this->assertNotContains($this->dish->id, $dishIds);
        // А вегетарианское показано
        $this->assertContains($veganDish->id, $dishIds);
    }

    /**
     * Тест: Каждый заказ имеет correlation_id
     */
    public function test_order_has_correlation_id(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/restaurants/orders', [
                'restaurant_id' => $this->restaurant->id,
                'items' => [
                    ['dish_id' => $this->dish->id, 'quantity' => 1],
                ],
                'delivery_address' => 'Главная ул., 1',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'correlation_id',
                ],
            ]);

        $order = RestaurantOrder::first();
        $this->assertNotNull($order->correlation_id);
        $this->assertIsString($order->correlation_id);
    }

    /**
     * Тест: B2C пользователь видит только B2C рестораны
     */
    public function test_b2c_customer_sees_only_b2c_restaurants(): void
    {
        $b2bOnlyRestaurant = Restaurant::factory()->create([
            'is_b2c_available' => false,
            'is_b2b_available' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/restaurants');

        $response->assertStatus(200);
        $restaurantIds = $response->json('data.*.id');

        $this->assertContains($this->restaurant->id, $restaurantIds);
        $this->assertNotContains($b2bOnlyRestaurant->id, $restaurantIds);
    }

    /**
     * Тест: Корпоративные заказы для B2B
     */
    public function test_b2b_customer_can_make_corporate_order(): void
    {
        $businessUser = User::factory()->create(['is_business' => true]);

        $response = $this->actingAs($businessUser)
            ->postJson('/api/v1/restaurants/orders/corporate', [
                'restaurant_id' => $this->restaurant->id,
                'items' => [
                    ['dish_id' => $this->dish->id, 'quantity' => 50],  // Большой заказ
                ],
                'delivery_address' => 'Офис на ул. Науки',
                'company_name' => 'Tech Corp',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'is_corporate' => true,
                ],
            ]);
    }
}
