<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Concert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcertResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_concert(): void
    {
        $this->post('/admin/concerts', [
            'name' => 'Summer Concert',
            'date' => now()->addMonth()
        ]);
        $this->assertDatabaseHas('concerts', [
            'name' => 'Summer Concert'
        ]);
    }

    public function test_can_list_concerts(): void
    {
        Concert::factory(5)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/concerts');
        $response->assertOk();
    }
}