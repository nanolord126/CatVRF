<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Services;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Domains\Bloggers\Models\StreamStatistics;
use App\Domains\Bloggers\Services\StreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class StreamServiceTest extends TestCase
{
    use RefreshDatabase;

    private StreamService $service;
    private BloggerProfile $blogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StreamService::class);
        
        // Create test blogger
        $user = $this->createUser();
        $this->blogger = BloggerProfile::factory()
            ->for($user)
            ->create([
                'verification_status' => 'verified',
            ]);
    }

    public function test_create_stream_creates_stream_with_correct_attributes(): void
    {
        $stream = $this->service->createStream(
            blogerId: $this->blogger->user_id,
            title: 'Test Stream',
            description: 'Test description',
            scheduledAt: now()->addHour(),
            tags: ['test', 'demo'],
            correlationId: '123-456',
        );

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('Test Stream', $stream->title);
        $this->assertEquals('scheduled', $stream->status);
        $this->assertNotNull($stream->uuid);
        $this->assertNotNull($stream->room_id);
        $this->assertNotNull($stream->broadcast_key);
        
        // Check statistics created
        $this->assertNotNull($stream->statistics);
        $this->assertInstanceOf(StreamStatistics::class, $stream->statistics);
    }

    public function test_create_stream_broadcasts_event(): void
    {
        \Event::fake();

        $stream = $this->service->createStream(
            blogerId: $this->blogger->user_id,
            title: 'Test Stream',
            description: 'Test description',
            scheduledAt: now()->addHour(),
            tags: [],
            correlationId: '123-456',
        );

        \Event::assertDispatchedTimes(\App\Domains\Bloggers\Events\StreamCreated::class);
    }

    public function test_create_stream_logs_to_audit_channel(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'Stream created' &&
                    isset($context['correlation_id']) &&
                    isset($context['stream_id']);
            });

        $this->service->createStream(
            blogerId: $this->blogger->user_id,
            title: 'Test Stream',
            description: '',
            scheduledAt: now()->addHour(),
            tags: [],
            correlationId: '123-456',
        );
    }

    public function test_start_stream_transitions_from_scheduled_to_live(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'scheduled']);

        $updated = $this->service->startStream(
            streamId: (int) $stream->id,
            correlationId: '789-012',
        );

        $this->assertEquals('live', $updated->status);
        $this->assertNotNull($updated->started_at);
    }

    public function test_start_stream_broadcasts_event(): void
    {
        \Event::fake();
        
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'scheduled']);

        $this->service->startStream(
            streamId: (int) $stream->id,
            correlationId: '789-012',
        );

        \Event::assertDispatchedTimes(\App\Domains\Bloggers\Events\StreamStarted::class);
    }

    public function test_end_stream_transitions_to_ended(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create([
                'status' => 'live',
                'started_at' => now()->subMinutes(30),
            ]);

        $updated = $this->service->endStream(
            streamId: (int) $stream->id,
            correlationId: '345-678',
        );

        $this->assertEquals('ended', $updated->status);
        $this->assertNotNull($updated->ended_at);
        $this->assertEquals(30, $updated->duration_minutes);
    }

    public function test_update_viewer_count_updates_peak_viewers(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live', 'viewer_count' => 100]);

        $this->service->updateViewerCount(
            streamId: (int) $stream->id,
            viewerCount: 500,
        );

        $stream->refresh();
        $this->assertEquals(500, $stream->viewer_count);
        $this->assertGreaterThanOrEqual(500, $stream->peak_viewers);
    }

    public function test_get_active_streams_returns_only_live_streams(): void
    {
        Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'ended']);

        Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'scheduled']);

        $active = $this->service->getActiveStreams();

        $this->assertEquals(1, $active->count());
        $this->assertEquals('live', $active->first()->status);
    }

    public function test_get_blogger_streams_returns_filtered_results(): void
    {
        Stream::factory(3)
            ->for($this->blogger, 'blogger')
            ->create();

        $otherBlogger = BloggerProfile::factory()->create();
        Stream::factory(2)
            ->for($otherBlogger, 'blogger')
            ->create();

        $streams = $this->service->getBloggerStreams(
            blogerId: $this->blogger->user_id,
            status: null,
        );

        $this->assertEquals(3, $streams->count());
    }

    public function test_create_stream_includes_correlation_id(): void
    {
        $correlationId = '999-888-777';
        
        $stream = $this->service->createStream(
            blogerId: $this->blogger->user_id,
            title: 'Test',
            description: '',
            scheduledAt: now()->addHour(),
            tags: [],
            correlationId: $correlationId,
        );

        $this->assertEquals($correlationId, $stream->correlation_id);
    }

    private function createUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create();
    }
}
