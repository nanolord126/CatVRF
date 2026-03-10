<?php

namespace Tests\Feature\Domains\Food;

use App\Models\Domains\Food\FoodOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $foodOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->foodOrder = FoodOrder::factory()->create(['tenant_id' => tenant()->id]);
    }

    public function test_index_returns_food_orders(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/food');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_food_order(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/food/{$this->foodOrder->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->foodOrder->id);
    }

    public function test_store_creates_food_order(): void
    {
        $data = [
            'restaurant_id' => User::factory()->create()->id,
            'customer_id' => User::factory()->create()->id,
            'total_amount' => 350,
            'status' => 'pending',
            'items' => json_encode([['name' => 'Pizza', 'quantity' => 2]]),
            'delivery_address' => '123 Main St',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/food', $data);
        $response->assertStatus(201);
        $this->assertDatabaseHas('food_orders', ['total_amount' => 350]);
    }
}
