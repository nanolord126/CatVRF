<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_item(): void
    {
        $this->post('/admin/inventory-items', [
            'name' => 'Widget A',
            'quantity' => 100
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Widget A'
        ]);
    }

    public function test_can_list_items(): void
    {
        InventoryItem::factory(50)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/inventory-items');
        $response->assertOk();
    }
}