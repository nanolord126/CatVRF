<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\TaxiService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_service(): void
    {
        $this->post('/admin/taxi-services', [
            'name' => 'Premium Taxi',
            'type' => 'premium'
        ]);
        $this->assertDatabaseHas('taxi_services', [
            'name' => 'Premium Taxi'
        ]);
    }

    public function test_can_list_services(): void
    {
        TaxiService::factory(10)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/taxi-services');
        $response->assertOk();
    }
}