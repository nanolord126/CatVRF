<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Http;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class GiftControllerApiTest extends TestCase
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
     * POST /api/gifts/streams/{roomId}/send
     * Test: Send NFT gift endpoint
     */
    public function test_send_gift_endpoint(): void
    {
        $response = $this->postJson("/api/gifts/streams/{$this->stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
            'message' => 'Love your content!',
        ]);

        $this->assertEquals(201, $response->status());
        $this->assertNotNull($response->json('data.id'));
        $this->assertEquals('pending', $response->json('data.minting_status'));
        Queue::assertPushed(\App\Domains\Bloggers\Jobs\MintNftGiftJob::class);
    }

    /**
     * GET /api/gifts/{giftId}/status
     * Test: Get gift status endpoint
     */
    public function test_get_gift_status_endpoint(): void
    {
        $createResponse = $this->postJson("/api/gifts/streams/{$this->stream->room_id}/send", [
            'amount' => 100000,
            'gift_type' => 'diamond',
            'message' => 'Amazing!',
        ]);

        $giftId = $createResponse->json('data.id');
        $response = $this->getJson("/api/gifts/{$giftId}/status");

        $this->assertEquals(200, $response->status());
        $this->assertEquals('pending', $response->json('data.minting_status'));
    }

    /**
     * POST /api/gifts/{giftId}/upgrade
     * Test: Upgrade gift to Collector NFT endpoint
     */
    public function test_upgrade_gift_endpoint(): void
    {
        $createResponse = $this->postJson("/api/gifts/streams/{$this->stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
        ]);

        $giftId = $createResponse->json('data.id');
        $gift = \App\Domains\Bloggers\Models\NftGift::find($giftId);

        // Simulate minting and waiting 14 days
        $gift->update([
            'minting_status' => 'minted',
            'minted_at' => now()->subDays(14),
            'upgrade_eligible_at' => now()->subDays(1),
            'nft_address' => '0x1234567890',
        ]);

        $response = $this->postJson("/api/gifts/{$giftId}/upgrade");

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('data.is_upgraded'));
    }

    /**
     * GET /api/gifts/{giftId}/metadata
     * Test: Get gift metadata endpoint
     */
    public function test_get_gift_metadata_endpoint(): void
    {
        $createResponse = $this->postJson("/api/gifts/streams/{$this->stream->room_id}/send", [
            'amount' => 25000,
            'gift_type' => 'silver',
        ]);

        $giftId = $createResponse->json('data.id');
        $response = $this->getJson("/api/gifts/{$giftId}/metadata");

        $this->assertEquals(200, $response->status());
        $this->assertNotNull($response->json('data.metadata'));
    }

    /**
     * GET /api/bloggers/{bloggerId}/gifts
     * Test: Get blogger gifts endpoint
     */
    public function test_get_blogger_gifts_endpoint(): void
    {
        $this->postJson("/api/gifts/streams/{$this->stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
        ]);

        $response = $this->getJson("/api/bloggers/{$this->blogger->id}/gifts");

        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, count($response->json('data')));
    }
}
