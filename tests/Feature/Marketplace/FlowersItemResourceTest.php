<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\FlowersItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowersItemResourceTest extends TestCase
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
        $this->post('/admin/flowers-items', [
            'flower_id' => 1,
            'quantity' => 50
        ]);
        $this->assertDatabaseHas('flowers_items', [
            'flower_id' => 1
        ]);
    }

    public function test_can_list_items(): void
    {
        FlowersItem::factory(20)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/flowers-items');
        $response->assertOk();
    }
}