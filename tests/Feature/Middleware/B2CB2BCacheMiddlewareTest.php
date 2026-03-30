<?php declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class B2CB2BCacheMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_caches_b2b_mode_determination(): void
    {
        $response = $this->actingAs($this->getTestUser())
            ->get('/api/v1/test', [
                'inn' => '7707083893',
                'business_card_id' => 123,
            ]);

        $this->assertNotNull(Cache::get('user_b2b_mode_' . $this->getTestUser()->id));
    }

    public function test_respects_cache_ttl(): void
    {
        $user = $this->getTestUser();
        
        $this->actingAs($user)->get('/api/v1/test', [
            'inn' => '7707083893',
        ]);

        $cacheKey = 'user_b2b_mode_' . $user->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_invalidates_by_tags(): void
    {
        $user = $this->getTestUser();
        $cacheTag = "user_b2c_b2b_{$user->id}";

        $this->actingAs($user)->get('/api/v1/test', [
            'inn' => '7707083893',
        ]);

        Cache::store('redis')->tags([$cacheTag])->flush();
        
        $this->assertFalse(Cache::has('user_b2b_mode_' . $user->id));
    }

    private function getTestUser()
    {
        return \App\Models\User::factory()->create();
    }
}
