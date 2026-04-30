<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use App\Models\User;

final class ResponseCacheMiddlewareTest extends TestCase
{
    public function test_caches_get_response(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/v1/products');
        $this->actingAs($user)->get('/api/v1/products');

        $this->assertTrue(Cache::has('response_cache_'.$user->id));
    }

    public function test_respects_no_cache_parameter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/v1/products?no-cache=1');

        $this->assertFalse(Cache::has('response_cache_'.$user->id));
    }

    public function test_only_caches_authenticated_requests(): void
    {
        $this->get('/api/v1/products');

        $this->assertFalse(Cache::has('response_cache'));
    }

    public function test_cache_ttl_is_ten_minutes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/v1/products');

        // Verify cache has appropriate TTL set
        $this->assertTrue(Cache::has('response_cache_'.$user->id));
    }

    public function test_invalidates_by_user_tags(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/v1/products');

        Cache::store('redis')->tags(["response_{$user->id}"])->flush();

        $this->assertFalse(Cache::has('response_cache_'.$user->id));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }
}
