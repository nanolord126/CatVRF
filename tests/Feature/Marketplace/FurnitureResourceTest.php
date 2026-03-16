<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Furniture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurnitureResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_furniture(): void
    {
        $this->post('/admin/furniture', [
            'name' => 'Wooden Chair',
            'price' => 49.99
        ]);
        $this->assertDatabaseHas('furniture', [
            'name' => 'Wooden Chair'
        ]);
    }

    public function test_can_list_furniture(): void
    {
        Furniture::factory(25)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/furniture');
        $response->assertOk();
    }
}