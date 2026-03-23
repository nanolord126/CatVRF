<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class StreamControllerApiTest extends TestCase
{
    use RefreshDatabase;

    protected BloggerProfile $blogger;
    protected Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->blogger = BloggerProfile::factory()->verified()->create();
        $this->actingAs($this->blogger->user);
    }

    /**
     * POST /api/streams
     * Test: Create stream endpoint
     */
    public function test_create_stream_endpoint(): void
    {
        $response = $this->postJson('/api/streams', [
            'title' => 'Test Stream',
            'description' => 'Test Description',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
            'category' => 'beauty',
            'tags' => ['test', 'api'],
        ]);

        $this->assertEquals(201, $response->status());
        $this->assertNotNull($response->json('data.room_id'));
        $this->assertNotNull($response->json('data.broadcast_key'));
        $this->assertEquals('Test Stream', $response->json('data.title'));
    }

    /**
     * GET /api/streams/{roomId}
     * Test: Get stream details
     */
    public function test_get_stream_endpoint(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create();

        $response = $this->getJson("/api/streams/{$stream->room_id}");

        $this->assertEquals(200, $response->status());
        $this->assertEquals($stream->title, $response->json('data.title'));
        $this->assertEquals($stream->status, $response->json('data.status'));
    }

    /**
     * POST /api/streams/{roomId}/start
     * Test: Start stream endpoint
     */
    public function test_start_stream_endpoint(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'scheduled']);

        $response = $this->postJson("/api/streams/{$stream->room_id}/start");

        $this->assertEquals(200, $response->status());
        $stream->refresh();
        $this->assertEquals('live', $stream->status);
        $this->assertNotNull($stream->started_at);
    }

    /**
     * POST /api/streams/{roomId}/end
     * Test: End stream endpoint
     */
    public function test_end_stream_endpoint(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live', 'started_at' => now()->subHour()]);

        $response = $this->postJson("/api/streams/{$stream->room_id}/end");

        $this->assertEquals(200, $response->status());
        $stream->refresh();
        $this->assertEquals('ended', $stream->status);
        $this->assertNotNull($stream->ended_at);
    }

    /**
     * POST /api/streams/{roomId}/viewer-update
     * Test: Update viewer count endpoint
     */
    public function test_update_viewer_count_endpoint(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        $response = $this->postJson("/api/streams/{$stream->room_id}/viewer-update", [
            'viewer_count' => 150,
        ]);

        $this->assertEquals(200, $response->status());
        $stream->refresh();
        $this->assertEquals(150, $stream->current_viewers);
        $this->assertEquals(150, $stream->peak_viewers);
    }

    /**
     * GET /api/streams
     * Test: List active streams endpoint
     */
    public function test_list_streams_endpoint(): void
    {
        Stream::factory(3)->for($this->blogger, 'blogger')->create(['status' => 'live']);

        $response = $this->getJson('/api/streams');

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * GET /api/bloggers/{bloggerId}/streams
     * Test: Get blogger streams endpoint
     */
    public function test_get_blogger_streams_endpoint(): void
    {
        Stream::factory(2)->for($this->blogger, 'blogger')->create();

        $response = $this->getJson("/api/bloggers/{$this->blogger->id}/streams");

        $this->assertEquals(200, $response->status());
        $this->assertEquals(2, count($response->json('data')));
    }
}
