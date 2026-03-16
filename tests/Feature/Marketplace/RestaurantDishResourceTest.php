<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\RestaurantDish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantDishResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_dish(): void
    {
        $this->post('/admin/restaurant-dishes', [
            'name' => 'Pasta Carbonara',
            'price' => 12.99
        ]);
        $this->assertDatabaseHas('restaurant_dishes', [
            'name' => 'Pasta Carbonara'
        ]);
    }

    public function test_can_list_dishes(): void
    {
        RestaurantDish::factory(10)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/restaurant-dishes');
        $response->assertOk();
    }
}