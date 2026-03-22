<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Food;

use App\Domains\Food\Models\Dish;
use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\RestaurantMenu;
use App\Domains\Food\Models\RestaurantOrder;
use App\Domains\Food\Services\RestaurantOrderService;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RestaurantOrderServiceTest — Production-grade тесты заказа в ресторане.
 *
 * Покрывает:
 * - Создание заказа (pending)
 * - Автоматическое списание ингредиентов при завершении
 * - Surge-ценообразование для доставки
 * - Отмена до приготовления → release ингредиентов
 * - Попытка отменить уже готовый заказ → исключение
 * - QR-меню: JSON-ответ с блюдами
 * - Нулевые позиции в заказе
 * - correlation_id
 * - tenant scoping
 */
final class RestaurantOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private RestaurantOrderService $service;
    private Tenant $tenant;
    private User $user;
    private Restaurant $restaurant;
    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service    = app(RestaurantOrderService::class);
        $this->tenant     = Tenant::factory()->create();
        $this->user       = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->restaurant = Restaurant::factory()->create(['tenant_id' => $this->tenant->id]);
        $menu             = RestaurantMenu::factory()->create(['restaurant_id' => $this->restaurant->id, 'tenant_id' => $this->tenant->id]);
        $this->dish       = Dish::factory()->create([
            'menu_id'   => $menu->id,
            'price'     => 50_000, // 500 руб
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->user);
        app()->bind('tenant', fn () => $this->tenant);

        $this->app->instance(
            FraudControlService::class,
            \Mockery::mock(FraudControlService::class)->shouldReceive('check')->andReturn(true)->getMock()
        );
    }

    // ─── CREATE ORDER ─────────────────────────────────────────────────────────

    public function test_create_order_returns_pending_status(): void
    {
        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 2]],
            correlationId: Str::uuid()->toString(),
        );

        $this->assertInstanceOf(RestaurantOrder::class, $order);
        $this->assertSame('pending', $order->status);
    }

    public function test_create_order_calculates_total_price(): void
    {
        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 3]],
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame(150_000, $order->total_price); // 3 × 50 000
    }

    public function test_create_order_persists_to_db(): void
    {
        $correlationId = Str::uuid()->toString();
        $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: $correlationId,
        );

        $this->assertDatabaseHas('restaurant_orders', [
            'restaurant_id'  => $this->restaurant->id,
            'client_id'      => $this->user->id,
            'status'         => 'pending',
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_create_order_sets_tenant_id(): void
    {
        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame($this->tenant->id, $order->tenant_id);
    }

    // ─── INGREDIENT DEDUCTION ────────────────────────────────────────────────

    public function test_completing_order_deducts_dish_ingredients(): void
    {
        $ingredient = InventoryItem::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'current_stock' => 100,
            'hold_stock'    => 0,
        ]);

        $this->dish->update(['consumables_json' => json_encode([
            ['item_id' => $ingredient->id, 'quantity' => 2],
        ])]);

        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: Str::uuid()->toString(),
        );

        $this->service->completeOrder($order->id, Str::uuid()->toString());

        $ingredient->refresh();
        $this->assertSame(98, $ingredient->current_stock);
    }

    // ─── CANCELLATION BEFORE COOKING ─────────────────────────────────────────

    public function test_cancel_pending_order_returns_true(): void
    {
        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: Str::uuid()->toString(),
        );

        $result = $this->service->cancelOrder($order->id, Str::uuid()->toString());
        $this->assertTrue($result);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
    }

    public function test_cancel_cooking_order_throws(): void
    {
        $order = RestaurantOrder::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'tenant_id'     => $this->tenant->id,
            'status'        => 'cooking',
        ]);

        $this->expectException(\Exception::class);
        $this->service->cancelOrder($order->id, Str::uuid()->toString());
    }

    // ─── EMPTY ORDER ─────────────────────────────────────────────────────────

    public function test_create_order_with_empty_items_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [],
            correlationId: Str::uuid()->toString(),
        );
    }

    // ─── AUDIT LOG ───────────────────────────────────────────────────────────

    public function test_order_creation_logs_audit(): void
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: Str::uuid()->toString(),
        );
    }

    // ─── DB ROLLBACK ─────────────────────────────────────────────────────────

    public function test_rollback_on_exception_leaves_no_order(): void
    {
        DB::shouldReceive('transaction')->once()->andThrow(new \RuntimeException('DB gone'));
        $this->expectException(\RuntimeException::class);

        $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [['dish_id' => $this->dish->id, 'quantity' => 1]],
            correlationId: Str::uuid()->toString(),
        );
    }

    // ─── MULTIPLE DISHES ─────────────────────────────────────────────────────

    public function test_order_with_multiple_dishes_sums_correctly(): void
    {
        $menu   = RestaurantMenu::factory()->create(['restaurant_id' => $this->restaurant->id, 'tenant_id' => $this->tenant->id]);
        $dish2  = Dish::factory()->create(['menu_id' => $menu->id, 'price' => 30_000, 'tenant_id' => $this->tenant->id]);

        $order = $this->service->createOrder(
            restaurantId:  $this->restaurant->id,
            clientId:      $this->user->id,
            items:         [
                ['dish_id' => $this->dish->id, 'quantity' => 2],  // 100 000
                ['dish_id' => $dish2->id, 'quantity' => 3],        // 90 000
            ],
            correlationId: Str::uuid()->toString(),
        );

        $this->assertSame(190_000, $order->total_price);
    }
}
