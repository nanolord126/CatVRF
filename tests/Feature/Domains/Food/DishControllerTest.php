<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Food;

use App\Domains\Food\Models\Dish;
use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\RestaurantMenu;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * DishControllerTest — Feature-тесты CRUD-операций над блюдами.
 *
 * Покрывает:
 * - store: успех, валидация, fraud-block, аудит-лог
 * - update: успех, fraud-block, аудит-лог (before/after state)
 * - destroy: успех, fraud-block, аудит-лог
 */
final class DishControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Restaurant $restaurant;
    private RestaurantMenu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant     = Tenant::factory()->create();
        $this->user       = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->restaurant = Restaurant::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->menu       = RestaurantMenu::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'tenant_id'     => $this->tenant->id,
        ]);

        app()->bind('tenant', fn () => $this->tenant);
        $this->actingAs($this->user);
    }

    // ---------------------------------------------------------------------------
    // store()
    // ---------------------------------------------------------------------------

    /** @test */
    public function store_creates_dish_and_logs_audit(): void
    {
        $this->allowFraud();

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once()->with(\Mockery::type('string'), \Mockery::on(fn ($ctx) => isset($ctx['correlation_id'])));

        $this->postJson("/api/v1/food/menus/{$this->menu->id}/dishes", [
            'name'              => 'Борщ',
            'price'             => 35000,
            'calories'          => 300,
            'cooking_time_minutes' => 25,
            'tenant_id'         => $this->tenant->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data.id', 'data.name', 'correlation_id']);

        $this->assertDatabaseHas('dishes', ['name' => 'Борщ', 'tenant_id' => $this->tenant->id]);
    }

    /** @test */
    public function store_returns_422_for_missing_name(): void
    {
        $this->allowFraud();

        $this->postJson("/api/v1/food/menus/{$this->menu->id}/dishes", [
            'price' => 35000,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function store_blocks_request_when_fraud_detected(): void
    {
        $this->blockFraud();

        Log::shouldReceive('channel')->with('fraud_alert')->once()->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->postJson("/api/v1/food/menus/{$this->menu->id}/dishes", [
            'name'  => 'Тест',
            'price' => 35000,
            'tenant_id' => $this->tenant->id,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['correlation_id']);
    }

    // ---------------------------------------------------------------------------
    // update()
    // ---------------------------------------------------------------------------

    /** @test */
    public function update_modifies_dish_and_logs_before_after(): void
    {
        $this->allowFraud();

        $dish = Dish::factory()->create([
            'menu_id'   => $this->menu->id,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Щи',
            'price'     => 20000,
        ]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once()->with(\Mockery::type('string'), \Mockery::on(function ($ctx) {
            return isset($ctx['before'], $ctx['after'], $ctx['correlation_id']);
        }));

        $this->putJson("/api/v1/food/dishes/{$dish->id}", [
            'name'  => 'Щи по-домашнему',
            'price' => 22000,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Щи по-домашнему');

        $this->assertDatabaseHas('dishes', ['id' => $dish->id, 'name' => 'Щи по-домашнему', 'price' => 22000]);
    }

    /** @test */
    public function update_blocks_when_fraud_detected(): void
    {
        $this->blockFraud();

        $dish = Dish::factory()->create(['menu_id' => $this->menu->id, 'tenant_id' => $this->tenant->id]);

        $this->putJson("/api/v1/food/dishes/{$dish->id}", ['name' => 'Fail'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ---------------------------------------------------------------------------
    // destroy()
    // ---------------------------------------------------------------------------

    /** @test */
    public function destroy_soft_deletes_dish_and_logs_audit(): void
    {
        $this->allowFraud();

        $dish = Dish::factory()->create(['menu_id' => $this->menu->id, 'tenant_id' => $this->tenant->id]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->deleteJson("/api/v1/food/dishes/{$dish->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('dishes', ['id' => $dish->id]);
    }

    /** @test */
    public function destroy_blocks_when_fraud_detected(): void
    {
        $this->blockFraud();

        $dish = Dish::factory()->create(['menu_id' => $this->menu->id, 'tenant_id' => $this->tenant->id]);

        $this->deleteJson("/api/v1/food/dishes/{$dish->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function allowFraud(): void
    {
        $mock = $this->createMock(FraudControlService::class);
        $mock->method('check')->willReturn(['score' => 0.05, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $mock);
    }

    private function blockFraud(): void
    {
        $mock = $this->createMock(FraudControlService::class);
        $mock->method('check')->willReturn(['score' => 0.97, 'decision' => 'block']);
        $this->app->instance(FraudControlService::class, $mock);
    }
}
