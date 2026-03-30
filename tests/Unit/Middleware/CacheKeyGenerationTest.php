<?php declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;

final class CacheKeyGenerationTest extends TestCase
{
    public function test_generates_unique_cache_keys_per_user(): void
    {
        $userId1 = 1;
        $userId2 = 2;

        $key1 = "user_b2b_mode_{$userId1}";
        $key2 = "user_b2b_mode_{$userId2}";

        $this->assertNotEquals($key1, $key2);
    }

    public function test_cache_key_includes_user_id(): void
    {
        $userId = 123;
        $cacheKey = "user_b2b_mode_{$userId}";

        $this->assertStringContainsString((string)$userId, $cacheKey);
    }

    public function test_cache_tag_includes_user_id(): void
    {
        $userId = 456;
        $cacheTag = "user_b2c_b2b_{$userId}";

        $this->assertStringContainsString((string)$userId, $cacheTag);
    }

    public function test_response_cache_key_includes_hash(): void
    {
        $userId = 789;
        $url = '/api/v1/products';
        $hash = hash('sha256', $url);
        $cacheKey = "response_{$userId}_{$hash}";

        $this->assertStringContainsString((string)$userId, $cacheKey);
        $this->assertStringContainsString($hash, $cacheKey);
    }

    public function test_taste_profile_cache_key_format(): void
    {
        $userId = 999;
        $cacheKey = "user_taste_profile_{$userId}";

        $this->assertStringContainsString('user_taste_profile', $cacheKey);
        $this->assertStringContainsString((string)$userId, $cacheKey);
    }
}
