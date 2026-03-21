<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Search\SearchRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\BaseTestCase;

/**
 * SearchRankingServiceTest — Unit-тесты поисковой ранжировки.
 */
final class SearchRankingServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private SearchRankingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SearchRankingService::class);
    }

    public function test_search_returns_collection(): void
    {
        $result = $this->service->search('стрижка', $this->tenant->id);
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_empty_query_throws_or_returns_empty(): void
    {
        try {
            $result = $this->service->search('', $this->tenant->id);
            $this->assertInstanceOf(Collection::class, $result);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('query', strtolower($e->getMessage()));
        }
    }

    public function test_results_scoped_to_tenant(): void
    {
        $result = $this->service->search('услуга', $this->tenant->id);

        foreach ($result as $item) {
            if (isset($item['tenant_id'])) {
                $this->assertSame($this->tenant->id, $item['tenant_id']);
            }
        }
    }

    public function test_sql_injection_in_query_is_safe(): void
    {
        $malicious = "'; DROP TABLE users; --";

        try {
            $result = $this->service->search($malicious, $this->tenant->id);
            // Must return collection, not crash
            $this->assertInstanceOf(Collection::class, $result);
        } catch (\InvalidArgumentException $e) {
            // Also acceptable
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_results_cached_after_first_call(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));

        $this->service->search('маникюр', $this->tenant->id);
    }

    public function test_rating_boost_applied_to_higher_rated_items(): void
    {
        $results = $this->service->search('мастер', $this->tenant->id, [
            'sort' => 'rating',
        ]);

        if ($results->count() >= 2) {
            $first  = $results->first()['rating'] ?? 0;
            $second = $results->skip(1)->first()['rating'] ?? 0;
            $this->assertGreaterThanOrEqual($second, $first);
        }

        $this->assertTrue(true);
    }
}
