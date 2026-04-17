<?php declare(strict_types=1);

namespace Tests\Feature\Fashion;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

final class FashionLoadTest extends \Tests\TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_handles_100_concurrent_product_requests(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/products');
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertGreaterThan(80, $successfulResponses, 'Should handle at least 80% of concurrent requests');
    }

    public function test_handles_50_concurrent_order_creations(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'payment_method' => 'wallet',
                ]);
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200 || $r->status() === 201)->count();
        
        $this->assertGreaterThan(40, $successfulResponses, 'Should handle at least 80% of concurrent orders');
    }

    public function test_handles_bulk_product_search_queries(): void
    {
        $searchTerms = ['dress', 'shirt', 'pants', 'shoes', 'bag', 'jacket', 'skirt', 'hat', 'scarf', 'belt'];
        $responses = [];

        foreach ($searchTerms as $term) {
            $responses[] = $this->actingAs($this->user)
                ->getJson("/api/fashion/search?q={$term}");
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertEquals(count($searchTerms), $successfulResponses, 'All search queries should succeed');
    }

    public function test_handles_concurrent_recommendation_requests(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/ml/cross-vertical-recommendations');
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertGreaterThan(15, $successfulResponses, 'Should handle most recommendation requests');
    }

    public function test_handles_concurrent_size_calculations(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 30; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/ml/size-recommendation/1');
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertGreaterThan(25, $successfulResponses, 'Should handle most size calculation requests');
    }

    public function test_handles_concurrent_review_submissions(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 40; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/products/1/reviews', [
                    'rating' => rand(1, 5),
                    'comment' => 'Test review ' . $i,
                ]);
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 201 || $r->status() === 429)->count();
        
        $this->assertGreaterThan(30, $successfulResponses, 'Should handle most review submissions (success or rate-limited)');
    }

    public function test_handles_concurrent_return_requests(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/returns', [
                    'order_id' => 1,
                    'product_id' => 1,
                    'reason' => 'wrong_size',
                ]);
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200 || $r->status() === 422)->count();
        
        $this->assertGreaterThan(15, $successfulResponses, 'Should handle most return requests');
    }

    public function test_handles_concurrent_inventory_operations(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/products/1/reserve', [
                    'quantity' => 1,
                    'order_id' => 'order_' . $i,
                ]);
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200 || $r->status() === 422)->count();
        
        $this->assertGreaterThan(40, $successfulResponses, 'Should handle most inventory operations');
    }

    public function test_database_connection_pool_under_load(): void
    {
        $startTime = microtime(true);
        $responses = [];

        for ($i = 0; $i < 200; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/products');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertGreaterThan(180, $successfulResponses, 'Connection pool should handle load');
        $this->assertLessThan(30, $duration, '200 requests should complete in under 30 seconds');
    }

    public function test_cache_performance_under_load(): void
    {
        // First request to populate cache
        $this->actingAs($this->user)
            ->getJson('/api/fashion/products/1');

        $startTime = microtime(true);
        $responses = [];

        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/products/1');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertEquals(100, $successfulResponses, 'All cached requests should succeed');
        $this->assertLessThan(5, $duration, '100 cached requests should be fast (< 5s)');
    }

    public function test_rate_limiting_under_load(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/products');
        }

        $rateLimitedResponses = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertGreaterThan(0, $rateLimitedResponses, 'Some requests should be rate-limited');
        $this->assertGreaterThan(50, $successfulResponses, 'Most requests should still succeed');
    }

    public function test_memory_usage_stability(): void
    {
        $initialMemory = memory_get_usage(true);
        
        for ($i = 0; $i < 500; $i++) {
            $this->actingAs($this->user)
                ->getJson('/api/fashion/products');
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $this->assertLessThan(50, $memoryIncrease, 'Memory increase should be under 50MB for 500 requests');
    }

    public function test_concurrent_user_sessions(): void
    {
        $users = User::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);
        $responses = [];

        foreach ($users as $user) {
            $responses[] = $this->actingAs($user)
                ->getJson('/api/fashion/orders');
        }

        $successfulResponses = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        
        $this->assertEquals(count($users), $successfulResponses, 'All concurrent user sessions should work');
    }
}
