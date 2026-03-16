<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_property(): void
    {
        $this->post('/admin/properties', [
            'title' => 'Luxury Apartment',
            'price' => 500000
        ]);
        $this->assertDatabaseHas('properties', [
            'title' => 'Luxury Apartment'
        ]);
    }

    public function test_can_list_properties(): void
    {
        Property::factory(20)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/properties');
        $response->assertOk();
    }
}