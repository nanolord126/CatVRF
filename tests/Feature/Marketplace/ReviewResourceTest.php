<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_review(): void
    {
        $this->post('/admin/reviews', [
            'rating' => 5,
            'comment' => 'Excellent service!'
        ]);
        $this->assertDatabaseHas('reviews', [
            'rating' => 5
        ]);
    }

    public function test_can_list_reviews(): void
    {
        Review::factory(25)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/reviews');
        $response->assertOk();
    }
}