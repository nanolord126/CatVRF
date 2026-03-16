<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTableResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_table(): void
    {
        $this->post('/admin/restaurant-tables', [
            'number' => 1,
            'capacity' => 4
        ]);
        $this->assertDatabaseHas('restaurant_tables', [
            'number' => 1
        ]);
    }

    public function test_can_list_tables(): void
    {
        RestaurantTable::factory(30)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/restaurant-tables');
        $response->assertOk();
    }
}