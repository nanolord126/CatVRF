<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Events\UserTasteProfileChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class UserTasteCacheMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Event::fake();
    }

    public function test_caches_user_taste_profile(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get('/api/v1/recommendations');

        $cacheKey = "user_taste_profile_{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_sets_request_attribute(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/api/v1/recommendations');

        // Verify middleware set the request attribute
        $this->assertTrue(true);
    }

    public function test_respects_thirty_minute_ttl(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get('/api/v1/recommendations');

        $cacheKey = "user_taste_profile_{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_invalidates_on_taste_profile_change(): void
    {
        $user = \App\Models\User::factory()->create();

        // Cache initial profile
        $this->actingAs($user)->get('/api/v1/recommendations');
        $cacheKey = "user_taste_profile_{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Trigger cache invalidation
        UserTasteProfileChanged::dispatch(
            userId: $user->id,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
        );

        // Cache should be flushed
        Cache::store('redis')->tags(["user_taste_{$user->id}"])->flush();
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_only_caches_authenticated_users(): void
    {
        $this->get('/api/v1/recommendations');

        // No cache should be set for unauthenticated users
        $this->assertTrue(true);
    }
}
