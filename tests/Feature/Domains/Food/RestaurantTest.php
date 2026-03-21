<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Food;

use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\RestaurantOrder;
use Database\Factories\Food\RestaurantFactory;
use Database\Factories\Food\RestaurantOrderFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class RestaurantTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Тест: создание ресторана
     */
    public function test_can_create_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'is_open' => $restaurant->is_open,
        ]);
    }

    /**
     * Тест: создание заказа ресторана
     */
    public function test_can_create_restaurant_order(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = RestaurantOrder::factory()
            ->for($restaurant, 'restaurant')
            ->create();

        $this->assertDatabaseHas('restaurant_orders', [
            'id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: проверка минимальной суммы заказа
     */
    public function test_restaurant_meets_minimum_order(): void
    {
        $restaurant = Restaurant::factory()->create([
            'min_order_amount' => 1000,
        ]);

        $this->assertTrue($restaurant->meetsMinimumOrder(1500));
        $this->assertFalse($restaurant->meetsMinimumOrder(500));
    }

    /**
     * Тест: проверка, открыт ли ресторан
     */
    public function test_restaurant_is_open(): void
    {
        $openRestaurant = Restaurant::factory()->open()->create();
        $closedRestaurant = Restaurant::factory()->closed()->create();

        $this->assertTrue($openRestaurant->isOpen());
        $this->assertFalse($closedRestaurant->isOpen());
    }

    /**
     * Тест: высокий рейтинг ресторана
     */
    public function test_restaurant_high_rating(): void
    {
        $restaurant = Restaurant::factory()->highRating()->create();

        $this->assertGreaterThanOrEqual(4, $restaurant->rating);
        $this->assertGreaterThanOrEqual(100, $restaurant->review_count);
    }

    /**
     * Тест: статусы заказа
     */
    public function test_restaurant_order_statuses(): void
    {
        $pendingOrder = RestaurantOrder::factory()->pending()->create();
        $deliveredOrder = RestaurantOrder::factory()->delivered()->create();
        $cancelledOrder = RestaurantOrder::factory()->cancelled()->create();

        $this->assertTrue($pendingOrder->isPending());
        $this->assertTrue($deliveredOrder->isDelivered());
        $this->assertEquals('cancelled', $cancelledOrder->status);
    }

    /**
     * Тест: связь заказа с рестораном
     */
    public function test_restaurant_order_relationship(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = RestaurantOrder::factory()
            ->for($restaurant, 'restaurant')
            ->create();

        $this->assertEquals($restaurant->id, $order->restaurant->id);
        $this->assertInstanceOf(Restaurant::class, $order->restaurant);
    }
}
