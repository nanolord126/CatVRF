<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\RestaurantMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantMenuResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_menu(): void
    {
        $this->post('/admin/restaurant-menus', [
            'name' => 'Winter Menu',
            'active' => true
        ]);
        $this->assertDatabaseHas('restaurant_menus', [
            'name' => 'Winter Menu'
        ]);
    }

    public function test_can_list_menus(): void
    {
        RestaurantMenu::factory(5)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/restaurant-menus');
        $response->assertOk();
    }
}