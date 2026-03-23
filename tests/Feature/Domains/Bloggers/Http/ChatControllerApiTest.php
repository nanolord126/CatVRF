<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ChatControllerApiTest extends TestCase
{
    use RefreshDatabase;

    protected BloggerProfile $blogger;
    protected Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->blogger = BloggerProfile::factory()->verified()->create();
        $this->stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);
        $this->actingAs(\App\Models\User::factory()->create());
    }

    /**
     * POST /api/streams/{roomId}/chat
     * Test: Send chat message endpoint
     */
    public function test_send_chat_message_endpoint(): void
    {
        $response = $this->postJson("/api/streams/{$this->stream->room_id}/chat", [
            'message' => 'Great stream!',
            'message_type' => 'text',
        ]);

        $this->assertEquals(201, $response->status());
        $this->assertNotNull($response->json('data.id'));
        $this->assertEquals('Great stream!', $response->json('data.message'));
    }

    /**
     * GET /api/streams/{roomId}/chat
     * Test: Get chat messages endpoint
     */
    public function test_get_chat_messages_endpoint(): void
    {
        $this->postJson("/api/streams/{$this->stream->room_id}/chat", [
            'message' => 'Hello!',
            'message_type' => 'text',
        ]);

        $response = $this->getJson("/api/streams/{$this->stream->room_id}/chat");

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * DELETE /api/streams/{roomId}/chat/{messageId}
     * Test: Delete chat message endpoint
     */
    public function test_delete_chat_message_endpoint(): void
    {
        $sender = auth()->user();
        $createResponse = $this->postJson("/api/streams/{$this->stream->room_id}/chat", [
            'message' => 'Test message',
            'message_type' => 'text',
        ]);

        $messageId = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/streams/{$this->stream->room_id}/chat/{$messageId}");

        $this->assertEquals(200, $response->status());
    }

    /**
     * POST /api/streams/{roomId}/chat/{messageId}/pin
     * Test: Pin chat message endpoint
     */
    public function test_pin_chat_message_endpoint(): void
    {
        $user = \App\Models\User::factory()->create();
        $createResponse = $this->actingAs($user)
            ->postJson("/api/streams/{$this->stream->room_id}/chat", [
                'message' => 'Important message',
                'message_type' => 'text',
            ]);

        $messageId = $createResponse->json('data.id');

        // Pin as blogger
        $response = $this->actingAs($this->blogger->user)
            ->postJson("/api/streams/{$this->stream->room_id}/chat/{$messageId}/pin");

        $this->assertEquals(200, $response->status());
    }
}
