<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\BeautySalon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeautySalonResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_beauty_salon(): void
    {
        $this->post('/admin/beauty-salons', [
            'name' => 'Premium Salon'
        ]);
        $this->assertDatabaseHas('beauty_salons', [
            'name' => 'Premium Salon'
        ]);
    }

    public function test_can_list_salons(): void
    {
        BeautySalon::factory(3)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/beauty-salons');
        $response->assertOk();
    }
}