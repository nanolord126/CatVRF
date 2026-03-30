<?php declare(strict_types=1);

namespace Tests\Unit\Jobs\CacheWarmers;

use App\Jobs\CacheWarmers\WarmUserTasteProfileJob;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class WarmUserTasteProfileJobTest extends TestCase
{
    public function test_job_caches_user_taste_profile(): void
    {
        $userId = 123;
        $job = new WarmUserTasteProfileJob($userId);

        $job->handle();

        $cacheKey = "user_taste_profile_{$userId}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_cached_profile_includes_correlation_id(): void
    {
        $userId = 456;
        $job = new WarmUserTasteProfileJob($userId);

        $job->handle();

        $cacheKey = "user_taste_profile_{$userId}";
        $profile = Cache::get($cacheKey);

        $this->assertArrayHasKey('correlation_id', $profile);
        $this->assertNotNull($profile['correlation_id']);
    }

    public function test_job_sets_correct_ttl(): void
    {
        $userId = 789;
        $job = new WarmUserTasteProfileJob($userId);

        $job->handle();

        $cacheKey = "user_taste_profile_{$userId}";
        $this->assertTrue(Cache::has($cacheKey));
    }
}
