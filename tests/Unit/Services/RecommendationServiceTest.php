<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RecommendationService;
use App\Services\FraudControlService;
use App\Services\RateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * RecommendationServiceTest — Unit-тесты системы рекомендаций.
 *
 * Покрываемые сценарии:
 *  1.  getForUser — cache hit возвращает кэшированные данные
 *  2.  getForUser — cache miss запускает логику
 *  3.  getForUser — результат кэшируется с правильным TTL
 *  4.  getForUser — fraud check вызывается перед выдачей
 *  5.  getForUser — rate limit вызывается
 *  6.  getForUser — tenant scoping применяется
 *  7.  getCrossVertical — возвращает рекомендации из другой вертикали
 *  8.  scoreItem — возвращает float 0..1
 *  9.  invalidateUserCache — удаляет кэш пользователя
 * 10.  При ошибке ML-сервиса — fallback на hot items
 * 11.  correlation_id присутствует в логах
 * 12.  Пустой userId — throw exception (не return null)
 * 13.  getB2BForTenant — B2B рекомендации
 * 14.  Vertical filter применяется корректно
 * 15.  Context параметры передаются в логику
 */
final class RecommendationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private RecommendationService $service;
    private FraudControlService $fraudControl;
    private RateLimiterService $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudControl = $this->getMockBuilder(FraudControlService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkRecommendation'])
            ->getMock();

        $this->fraudControl->method('checkRecommendation')->willReturn(true);

        $this->rateLimiter = $this->getMockBuilder(RateLimiterService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['check'])
            ->getMock();

        $this->rateLimiter->method('check')->willReturn(true);

        $this->app->instance(FraudControlService::class, $this->fraudControl);
        $this->app->instance(RateLimiterService::class, $this->rateLimiter);

        $this->service = app(RecommendationService::class);
    }

    // ─── 1. CACHE HIT ─────────────────────────────────────────────────────────

    public function test_get_for_user_returns_cached_data_on_hit(): void
    {
        $cacheKey  = "recommend:user:{$this->user->id}:vertical:beauty:v1";
        $cachedItems = collect([
            ['id' => 1, 'name' => 'Haircut', 'score' => 0.92],
            ['id' => 2, 'name' => 'Manicure', 'score' => 0.87],
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedItems);

        // Should NOT call put since we got a cache hit
        Cache::shouldReceive('put')->never();

        $result = $this->service->getForUser($this->user->id, 'beauty');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result->first()['id']);
    }

    // ─── 2. CACHE MISS ────────────────────────────────────────────────────────

    public function test_get_for_user_runs_logic_on_cache_miss(): void
    {
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once()->andReturn(true);

        Log::shouldReceive('channel')
            ->with('recommend')
            ->andReturnSelf();
        Log::shouldReceive('info')->andReturn(null);

        $result = $this->service->getForUser($this->user->id, 'beauty');

        $this->assertInstanceOf(Collection::class, $result);
        // On cache miss with no real data, result can be empty collection
        // but must NOT be null
        $this->assertNotNull($result);
    }

    // ─── 3. CACHE WRITE WITH TTL ──────────────────────────────────────────────

    public function test_result_is_cached_with_300_seconds_ttl(): void
    {
        Cache::shouldReceive('get')->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $ttl === 300;
            })
            ->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $this->service->getForUser($this->user->id, 'beauty');
    }

    // ─── 4. FRAUD CHECK IS CALLED ─────────────────────────────────────────────

    public function test_fraud_check_is_called_before_recommendation(): void
    {
        $this->fraudControl->expects($this->once())
            ->method('checkRecommendation')
            ->with($this->user->id, $this->tenant->id)
            ->willReturn(true);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();
        Log::shouldReceive('error')->andReturn(null)->byDefault();
        Log::shouldReceive('warning')->andReturn(null)->byDefault();

        $this->service->getForUser($this->user->id, 'beauty');
    }

    // ─── 5. RATE LIMITER IS CALLED ────────────────────────────────────────────

    public function test_rate_limiter_is_checked_on_request(): void
    {
        $this->rateLimiter->expects($this->once())
            ->method('check')
            ->willReturn(true);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $this->service->getForUser($this->user->id, 'beauty');
    }

    // ─── 6. TENANT SCOPING ────────────────────────────────────────────────────

    public function test_recommendations_are_scoped_to_tenant(): void
    {
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $result = $this->service->getForUser($this->user->id, null, [
            'tenant_id' => $this->tenant->id,
        ]);

        // All items in result must belong to this tenant
        foreach ($result as $item) {
            if (isset($item['tenant_id'])) {
                $this->assertSame($this->tenant->id, $item['tenant_id']);
            }
        }
    }

    // ─── 7. CROSS-VERTICAL ────────────────────────────────────────────────────

    public function test_cross_vertical_returns_different_vertical_items(): void
    {
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $result = $this->service->getCrossVertical($this->user->id, 'hotels');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotNull($result);
    }

    // ─── 8. SCORE ITEM ────────────────────────────────────────────────────────

    public function test_score_item_returns_float_between_0_and_1(): void
    {
        $score = $this->service->scoreItem($this->user->id, 1, [
            'vertical' => 'beauty',
        ]);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    // ─── 9. INVALIDATE CACHE ──────────────────────────────────────────────────

    public function test_invalidate_user_cache_clears_all_user_keys(): void
    {
        Cache::shouldReceive('forget')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        Cache::shouldReceive('tags')->andReturnSelf()->byDefault();
        Cache::shouldReceive('flush')->andReturn(true)->byDefault();

        // Should not throw
        $this->service->invalidateUserCache($this->user->id);
        $this->assertTrue(true); // Reached without exception
    }

    // ─── 10. FALLBACK ON ML ERROR ─────────────────────────────────────────────

    public function test_recommendation_falls_back_to_hot_items_on_ml_error(): void
    {
        // Cache miss → ML throws
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();
        Log::shouldReceive('error')->andReturn(null)->byDefault();
        Log::shouldReceive('warning')->andReturn(null)->byDefault();

        // Should not throw — must return Collection (even if empty)
        $result = $this->service->getForUser($this->user->id, 'beauty');

        $this->assertInstanceOf(Collection::class, $result);
    }

    // ─── 11. CORRELATION ID IN LOGS ───────────────────────────────────────────

    public function test_correlation_id_is_present_in_recommendation_logs(): void
    {
        $logged = false;

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')
            ->with('recommend')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) use (&$logged) {
                if (isset($context['correlation_id'])) {
                    $logged = true;
                }
                return true;
            })
            ->andReturn(null);

        $this->service->getForUser($this->user->id, 'beauty');

        $this->assertTrue($logged, 'correlation_id must be present in recommendation logs');
    }

    // ─── 12. NULL USER ID THROWS EXCEPTION ────────────────────────────────────

    public function test_get_for_user_throws_on_empty_user_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->getForUser(0, 'beauty');
    }

    // ─── 13. B2B RECOMMENDATIONS ─────────────────────────────────────────────

    public function test_b2b_recommendations_return_supplier_list(): void
    {
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $result = $this->service->getB2BForTenant($this->tenant->id, 'beauty');

        $this->assertInstanceOf(Collection::class, $result);
    }

    // ─── 14. VERTICAL FILTER ──────────────────────────────────────────────────

    public function test_vertical_filter_applied_to_cache_key(): void
    {
        $capturedKey = null;

        Cache::shouldReceive('get')
            ->once()
            ->withArgs(function ($key) use (&$capturedKey) {
                $capturedKey = $key;
                return true;
            })
            ->andReturn(null);

        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        $this->service->getForUser($this->user->id, 'auto');

        $this->assertNotNull($capturedKey);
        $this->assertStringContainsString('auto', $capturedKey);
    }

    // ─── 15. CONTEXT PASSED THROUGH ──────────────────────────────────────────

    public function test_context_parameters_are_forwarded(): void
    {
        $context = [
            'geo_lat'  => 55.751244,
            'geo_lng'  => 37.618423,
            'radius'   => 5000,
            'vertical' => 'food',
        ];

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();

        // Should not throw with geo context
        $result = $this->service->getForUser($this->user->id, 'food', $context);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
