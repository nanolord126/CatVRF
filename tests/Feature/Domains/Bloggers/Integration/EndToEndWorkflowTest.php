<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Integration;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Complete workflow: Create stream → Broadcast → Orders → End
     */
    public function test_complete_stream_workflow(): void
    {
        Queue::fake();

        // Setup: Create blogger
        $blogger = BloggerProfile::factory()->create(['verification_status' => 'verified']);
        $this->actingAs($blogger->user);

        // Step 1: Create scheduled stream
        $response = $this->postJson('/api/streams', [
            'title' => 'E2E Test Stream',
            'description' => 'End-to-end workflow test',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
            'tags' => ['test', 'e2e'],
        ]);

        $this->assertEquals(201, $response->status());
        $stream = Stream::where('room_id', $response->json('data.room_id'))->first();
        $this->assertEquals('scheduled', $stream->status);

        // Step 2: Start stream
        $response = $this->postJson("/api/streams/{$stream->room_id}/start");
        $this->assertEquals(200, $response->status());
        
        $stream->refresh();
        $this->assertEquals('live', $stream->status);

        // Step 3: Add products
        $product = \App\Models\Product::factory()->create();
        $response = $this->postJson("/api/streams/{$stream->room_id}/products", [
            'product_id' => $product->id,
            'price_override' => 4990,
            'quantity' => 100,
        ]);
        $this->assertEquals(201, $response->status());

        // Step 4: Simulate viewers (other user)
        $viewer = \App\Models\User::factory()->create();
        $this->actingAs($viewer);

        // Get stream details
        $response = $this->getJson("/api/streams/{$stream->room_id}");
        $this->assertEquals(200, $response->status());

        // Send chat message
        $response = $this->postJson("/api/streams/{$stream->room_id}/chat", [
            'message' => 'Great stream!',
            'message_type' => 'text',
        ]);
        $this->assertEquals(201, $response->status());

        // Send gift
        $response = $this->postJson("/api/gifts/streams/{$stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
            'message' => 'Love your content!',
        ]);
        $this->assertEquals(201, $response->status());

        // Create order
        $streamProducts = $stream->products;
        $response = $this->postJson('/api/orders', [
            'product_id' => $streamProducts->first()->id,
            'quantity' => 2,
            'payment_method' => 'sbp',
        ]);
        $this->assertEquals(201, $response->status());
        $orderId = $response->json('data.id');

        // Confirm payment
        $response = $this->postJson("/api/orders/{$orderId}/confirm-payment", [
            'payment_id' => $response->json('data.payment_id'),
        ]);
        $this->assertEquals(200, $response->status());

        // Step 5: End stream (as blogger)
        $this->actingAs($blogger->user);
        $response = $this->postJson("/api/streams/{$stream->room_id}/end");
        $this->assertEquals(200, $response->status());

        $stream->refresh();
        $this->assertEquals('ended', $stream->status);
        $this->assertNotNull($stream->ended_at);

        // Step 6: Verify statistics
        $response = $this->getJson("/api/statistics/blogger/me");
        $this->assertEquals(200, $response->status());
        $this->assertGreaterThan(0, $response->json('data.total_earned'));
    }

    /**
     * Workflow: Blogger verification → Streaming → Withdrawal
     */
    public function test_blogger_onboarding_workflow(): void
    {
        // Step 1: Create user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Step 2: Create blogger profile
        $profile = BloggerProfile::create([
            'user_id' => $user->id,
            'tenant_id' => tenant()->id,
            'verification_status' => 'pending',
            'wallet_balance' => 0,
        ]);

        // Step 3: Submit verification documents
        // (In real app, this would be file upload)
        $profile->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        // Step 4: Create and run stream
        $stream = Stream::factory()
            ->for($profile, 'blogger')
            ->create(['status' => 'live']);

        // Simulate earnings
        $stream->update([
            'total_revenue' => 100000,
            'platform_commission' => 14000,
        ]);

        // Update wallet
        $profile->increment('wallet_balance', 86000);

        // Step 5: Request withdrawal
        $profile->refresh();
        $this->assertGreaterThan(0, $profile->wallet_balance);

        // Simulate withdrawal processed
        $profile->decrement('wallet_balance', 86000);
        $profile->refresh();

        $this->assertEquals(0, $profile->wallet_balance);
    }

    /**
     * Workflow: NFT Gift creation → Minting → Upgrade
     */
    public function test_nft_gift_lifecycle(): void
    {
        Queue::fake();

        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $sender = \App\Models\User::factory()->create();
        $this->actingAs($sender);

        // Step 1: Send NFT gift
        $response = $this->postJson("/api/gifts/streams/{$stream->room_id}/send", [
            'amount' => 100000,
            'gift_type' => 'diamond',
            'message' => 'Amazing content!',
        ]);

        $this->assertEquals(201, $response->status());
        $gift = \App\Domains\Bloggers\Models\NftGift::latest()->first();

        // Verify queued for minting
        Queue::assertPushed(\App\Domains\Bloggers\Jobs\MintNftGiftJob::class);
        $this->assertEquals('pending', $gift->minting_status);

        // Step 2: Simulate minting
        $gift->update([
            'minting_status' => 'minted',
            'minted_at' => now(),
            'nft_address' => '0x1234567890',
            'upgrade_eligible_at' => now()->addDays(14),
        ]);

        // Step 3: Check gift status
        $response = $this->getJson("/api/gifts/{$gift->id}/status");
        $this->assertEquals(200, $response->status());
        $this->assertEquals('minted', $response->json('data.minting_status'));

        // Step 4: Wait 14+ days and upgrade
        $gift->update([
            'upgrade_eligible_at' => now()->subDays(1),
        ]);

        $response = $this->postJson("/api/gifts/{$gift->id}/upgrade");
        $this->assertEquals(200, $response->status());

        $gift->refresh();
        $this->assertTrue($gift->is_upgraded);
        $this->assertNotNull($gift->upgraded_at);
    }

    /**
     * Workflow: Chat moderation
     */
    public function test_chat_moderation_workflow(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();

        // Step 1: User 1 sends message
        $this->actingAs($user1);
        $response = $this->postJson("/api/streams/{$stream->room_id}/chat", [
            'message' => 'Hello community!',
            'message_type' => 'text',
        ]);
        $this->assertEquals(201, $response->status());
        $message1Id = \App\Domains\Bloggers\Models\StreamChatMessage::latest()->first()->id;

        // Step 2: User 2 sends message
        $this->actingAs($user2);
        $response = $this->postJson("/api/streams/{$stream->room_id}/chat", [
            'message' => 'Great!',
            'message_type' => 'text',
        ]);
        $this->assertEquals(201, $response->status());

        // Step 3: Blogger pins message (user 1's)
        $this->actingAs($blogger->user);
        $response = $this->postJson("/api/streams/{$stream->room_id}/chat/{$message1Id}/pin");
        $this->assertEquals(200, $response->status());

        // Verify pinned
        $message = \App\Domains\Bloggers\Models\StreamChatMessage::find($message1Id);
        $this->assertTrue($message->is_pinned);

        // Step 4: User 1 deletes own message
        $this->actingAs($user1);
        $response = $this->deleteJson("/api/streams/{$stream->room_id}/chat/{$message1Id}");
        $this->assertEquals(200, $response->status());

        // Verify deleted
        $this->assertNull(\App\Domains\Bloggers\Models\StreamChatMessage::find($message1Id));
    }

    /**
     * Workflow: Product pinning and unpinning
     */
    public function test_product_pinning_workflow(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $this->actingAs($blogger->user);

        // Add 3 products
        $products = \App\Models\Product::factory(3)->create();
        $streamProducts = [];

        foreach ($products as $product) {
            $response = $this->postJson("/api/streams/{$stream->room_id}/products", [
                'product_id' => $product->id,
                'quantity' => 100,
            ]);
            $streamProducts[] = $response->json('data.id');
        }

        // Pin first 2 products
        $response = $this->postJson("/api/streams/{$stream->room_id}/products/{$streamProducts[0]}/pin");
        $this->assertEquals(200, $response->status());

        $response = $this->postJson("/api/streams/{$stream->room_id}/products/{$streamProducts[1]}/pin");
        $this->assertEquals(200, $response->status());

        // Get pinned products
        $response = $this->getJson("/api/streams/{$stream->room_id}/products");
        $this->assertEquals(200, $response->status());
        $this->assertEquals(2, count($response->json('data')));

        // Unpin first product
        $response = $this->postJson("/api/streams/{$stream->room_id}/products/{$streamProducts[0]}/unpin");
        $this->assertEquals(200, $response->status());

        // Verify only 1 pinned now
        $response = $this->getJson("/api/streams/{$stream->room_id}/products");
        $this->assertEquals(1, count($response->json('data')));
    }
}
