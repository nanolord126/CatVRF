<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_category(): void
    {
        $this->post('/admin/categories', [
            'name' => 'Electronics',
            'description' => 'Electronic devices'
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics'
        ]);
    }

    public function test_can_list_categories(): void
    {
        Category::factory(10)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/categories');
        $response->assertOk();
    }
}