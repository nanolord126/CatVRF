<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_restaurant(): void
    {
        $this->post('/admin/restaurants', [
            'name' => 'Test Restaurant',
            'cuisine' => 'Italian'
        ]);
        $this->assertDatabaseHas('restaurants', [
            'name' => 'Test Restaurant'
        ]);
    }

    public function test_can_list_restaurants(): void
    {
        Restaurant::factory(5)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/restaurants');
        $response->assertOk();
    }
}