<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StatisticsControllerApiTest extends TestCase
{
    use RefreshDatabase;

    protected BloggerProfile $blogger;
    protected Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blogger = BloggerProfile::factory()->verified()->create();
        $this->stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create([
                'status' => 'ended',
                'peak_viewers' => 1500,
                'total_revenue' => 500000,
                'platform_commission' => 70000,
            ]);
        $this->actingAs($this->blogger->user);
    }

    /**
     * GET /api/statistics/blogger/me
     * Test: Get current blogger statistics
     */
    public function test_get_blogger_stats_endpoint(): void
    {
        $response = $this->getJson('/api/statistics/blogger/me');

        $this->assertEquals(200, $response->status());
        $this->assertNotNull($response->json('data.total_streams'));
        $this->assertNotNull($response->json('data.total_earned'));
        $this->assertNotNull($response->json('data.total_viewers'));
    }

    /**
     * GET /api/statistics/streams/{streamId}
     * Test: Get stream statistics
     */
    public function test_get_stream_stats_endpoint(): void
    {
        $response = $this->getJson("/api/statistics/streams/{$this->stream->room_id}");

        $this->assertEquals(200, $response->status());
        $this->assertEquals(1500, $response->json('data.peak_viewers'));
        $this->assertEquals(500000, $response->json('data.total_revenue'));
    }

    /**
     * GET /api/statistics/leaderboard
     * Test: Get leaderboard
     */
    public function test_get_leaderboard_endpoint(): void
    {
        BloggerProfile::factory(3)->verified()->create()
            ->each(function (BloggerProfile $profile) {
                Stream::factory()->for($profile, 'blogger')->create([
                    'total_revenue' => rand(100000, 1000000),
                ]);
            });

        $response = $this->getJson('/api/statistics/leaderboard?metric=earnings&period=month');

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, count($response->json('data')));
        $this->assertEquals('earnings', $response->json('meta.metric'));
    }

    /**
     * GET /api/statistics/streams/{streamId}/hourly
     * Test: Get hourly stream analytics
     */
    public function test_get_stream_hourly_stats_endpoint(): void
    {
        $response = $this->getJson("/api/statistics/streams/{$this->stream->room_id}/hourly");

        $this->assertEquals(200, $response->status());
        $this->assertNotNull($response->json('data'));
    }

    /**
     * GET /api/statistics/blogger/me/revenue
     * Test: Get blogger revenue breakdown
     */
    public function test_get_revenue_breakdown_endpoint(): void
    {
        $response = $this->getJson('/api/statistics/blogger/me/revenue');

        $this->assertEquals(200, $response->status());
        $this->assertNotNull($response->json('data.total_revenue'));
        $this->assertNotNull($response->json('data.platform_commission'));
        $this->assertNotNull($response->json('data.net_earnings'));
    }
}
