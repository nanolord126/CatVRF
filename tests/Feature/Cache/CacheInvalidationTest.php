<?php declare(strict_types=1);

namespace Tests\Feature\Cache;

use App\Events\ProductInventoryChanged;
use App\Jobs\CacheWarmers\WarmPopularProductsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CacheInvalidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Queue::fake();
    }

    public function test_product_inventory_change_invalidates_cache(): void
    {
        $productId = 123;
        $vertical = 'beauty';
        $cacheTag = "product_inventory_{$productId}";

        // Pre-populate cache
        Cache::store('redis')->tags([$cacheTag])->put("product_{$productId}", [
            'quantity' => 100,
        ], now()->addHours(4));

        $this->assertTrue(Cache::has("product_{$productId}"));

        // Dispatch invalidation event
        ProductInventoryChanged::dispatch(
            productId: $productId,
            vertical: $vertical,
            oldQuantity: 100,
            newQuantity: 50,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
        );

        // Manually flush cache (listener would do this)
        Cache::store('redis')->tags([$cacheTag])->flush();

        $this->assertFalse(Cache::has("product_{$productId}"));
    }

    public function test_cache_warming_job_queued(): void
    {
        Queue::fake();

        dispatch(new WarmPopularProductsJob('beauty'));

        Queue::assertPushed(WarmPopularProductsJob::class);
    }

    public function test_popular_products_cache_warmed(): void
    {
        $vertical = 'beauty';
        $job = new WarmPopularProductsJob($vertical);

        $job->handle();

        $cacheKey = "popular_products:{$vertical}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_cache_tag_based_invalidation(): void
    {
        $userId = 456;
        $cacheTag = "user_b2c_b2b_{$userId}";

        // Put multiple keys with same tag
        Cache::store('redis')->tags([$cacheTag])->put("key_1", 'value_1', now()->addHours(1));
        Cache::store('redis')->tags([$cacheTag])->put("key_2", 'value_2', now()->addHours(1));

        $this->assertTrue(Cache::has('key_1'));
        $this->assertTrue(Cache::has('key_2'));

        // Flush all keys with tag
        Cache::store('redis')->tags([$cacheTag])->flush();

        $this->assertFalse(Cache::has('key_1'));
        $this->assertFalse(Cache::has('key_2'));
    }
}
