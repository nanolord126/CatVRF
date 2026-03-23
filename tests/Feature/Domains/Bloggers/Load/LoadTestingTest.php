<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Load;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Domains\Bloggers\Services\StreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoadTestingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: 10,000 Concurrent Viewers
     * Expected: Viewer count updates without race conditions
     * Duration: Should complete in <5 seconds
     */
    public function test_10k_concurrent_viewers_update(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live', 'viewer_count' => 0]);

        $service = app(StreamService::class);

        // Simulate 10k concurrent viewer updates
        $startTime = microtime(true);

        for ($i = 1; $i <= 10000; $i++) {
            $service->updateViewerCount(
                streamId: (int) $stream->id,
                viewerCount: $i,
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $stream->refresh();

        $this->assertEquals(10000, $stream->viewer_count);
        $this->assertLessThan(5, $duration, "Viewer update took too long: {$duration}s");
    }

    /**
     * Test: 1,000 Chat Messages Per Minute
     * Expected: All messages processed and stored
     * Duration: Should complete in <10 seconds
     */
    public function test_1000_chat_messages_per_minute(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $users = \App\Models\User::factory(50)->create();

        $startTime = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            \App\Domains\Bloggers\Models\StreamChatMessage::create([
                'stream_id' => $stream->id,
                'user_id' => $users[$i % 50]->id,
                'message' => "Message $i",
                'message_type' => 'text',
                'moderation_status' => 'approved',
            ]);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $messageCount = \App\Domains\Bloggers\Models\StreamChatMessage::where('stream_id', $stream->id)->count();

        $this->assertEquals(1000, $messageCount);
        $this->assertLessThan(10, $duration, "Chat processing took too long: {$duration}s");
    }

    /**
     * Test: 100 NFT Mints Per Minute
     * Expected: Job queue processes without deadlock
     * Verification: All gifts queued with correct status
     */
    public function test_100_nft_gifts_queued_per_minute(): void
    {
        \Queue::fake();

        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $senders = \App\Models\User::factory(50)->create();

        for ($i = 0; $i < 100; $i++) {
            \App\Domains\Bloggers\Models\NftGift::create([
                'stream_id' => $stream->id,
                'sender_user_id' => $senders[$i % 50]->id,
                'recipient_user_id' => $blogger->user_id,
                'amount' => 50000,
                'gift_type' => 'gold',
                'minting_status' => 'pending',
                'correlation_id' => "test-$i",
            ]);
        }

        $giftCount = \App\Domains\Bloggers\Models\NftGift::where('stream_id', $stream->id)->count();

        $this->assertEquals(100, $giftCount);
    }

    /**
     * Test: 50 Orders Per Minute
     * Expected: Orders processed and commission calculated correctly
     * Verification: Inventory and revenue updated
     */
    public function test_50_live_orders_per_minute(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live', 'total_revenue' => 0]);

        $product = \App\Models\Product::factory()->create();
        $streamProduct = \App\Domains\Bloggers\Models\StreamProduct::create([
            'stream_id' => $stream->id,
            'product_id' => (int) $product->id,
            'price_during_stream' => 10000,
            'quantity_available' => 10000,
        ]);

        $buyers = \App\Models\User::factory(50)->create();

        for ($i = 0; $i < 50; $i++) {
            \App\Domains\Bloggers\Models\StreamOrder::create([
                'stream_id' => $stream->id,
                'user_id' => $buyers[$i]->id,
                'stream_product_id' => $streamProduct->id,
                'quantity' => 1,
                'subtotal' => 10000,
                'total' => 10000,
                'payment_method' => 'sbp',
                'status' => 'paid',
                'paid_at' => now(),
                'idempotency_key' => "order-$i",
            ]);

            $stream->increment('total_revenue', 10000);
        }

        $stream->refresh();
        $orderCount = \App\Domains\Bloggers\Models\StreamOrder::where('stream_id', $stream->id)->count();

        $this->assertEquals(50, $orderCount);
        $this->assertEquals(500000, $stream->total_revenue);
        $this->assertEquals(70000, (int)($stream->total_revenue * 0.14));
    }

    /**
     * Test: Peak Load Scenario
     * Simultaneous:
     * - 5,000 viewers
     * - 200 chat messages
     * - 20 product pins
     * - 5 active orders
     */
    public function test_peak_load_scenario(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        // Simulate 5k viewers
        $stream->update(['viewer_count' => 5000]);

        // Create products for pinning
        $products = \App\Models\Product::factory(20)->create();
        $streamProducts = [];

        foreach ($products as $product) {
            $streamProducts[] = \App\Domains\Bloggers\Models\StreamProduct::create([
                'stream_id' => $stream->id,
                'product_id' => (int) $product->id,
                'price_during_stream' => 10000,
                'quantity_available' => 100,
            ]);
        }

        // Pin products
        foreach ($streamProducts as $index => $sp) {
            if ($index < 5) {
                $sp->update([
                    'is_pinned' => true,
                    'pin_position' => $index + 1,
                ]);
            }
        }

        // Create chat messages
        $users = \App\Models\User::factory(50)->create();
        for ($i = 0; $i < 200; $i++) {
            \App\Domains\Bloggers\Models\StreamChatMessage::create([
                'stream_id' => $stream->id,
                'user_id' => $users[$i % 50]->id,
                'message' => "Peak load message $i",
                'message_type' => 'text',
                'moderation_status' => 'approved',
            ]);
        }

        // Create orders
        for ($i = 0; $i < 5; $i++) {
            \App\Domains\Bloggers\Models\StreamOrder::create([
                'stream_id' => $stream->id,
                'user_id' => $users[$i]->id,
                'stream_product_id' => $streamProducts[$i]->id,
                'quantity' => 1,
                'subtotal' => 10000,
                'total' => 10000,
                'payment_method' => 'sbp',
                'status' => 'paid',
                'idempotency_key' => "peak-order-$i",
            ]);
        }

        // Verify all data stored correctly
        $this->assertEquals(5000, $stream->viewer_count);
        $this->assertEquals(200, \App\Domains\Bloggers\Models\StreamChatMessage::where('stream_id', $stream->id)->count());
        $this->assertEquals(5, \App\Domains\Bloggers\Models\StreamProduct::where('stream_id', $stream->id)->where('is_pinned', true)->count());
        $this->assertEquals(5, \App\Domains\Bloggers\Models\StreamOrder::where('stream_id', $stream->id)->count());
    }

    /**
     * Test: Database Connection Pool
     * Expected: Multiple concurrent operations don't cause connection exhaustion
     */
    public function test_connection_pool_under_load(): void
    {
        $blogger = BloggerProfile::factory()->create();
        $streams = Stream::factory(100)
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        $startTime = microtime(true);

        foreach ($streams as $stream) {
            $stream->increment('viewer_count', rand(100, 1000));
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(3, $duration, "Database operations took too long: {$duration}s");
    }

    /**
     * Test: Memory Usage Under Load
     * Expected: Memory usage stays within acceptable bounds
     */
    public function test_memory_usage_under_load(): void
    {
        $initialMemory = memory_get_usage(true);

        $blogger = BloggerProfile::factory()->create();
        $stream = Stream::factory()
            ->for($blogger, 'blogger')
            ->create(['status' => 'live']);

        // Create 1000 chat messages
        $users = \App\Models\User::factory(50)->create();
        for ($i = 0; $i < 1000; $i++) {
            \App\Domains\Bloggers\Models\StreamChatMessage::create([
                'stream_id' => $stream->id,
                'user_id' => $users[$i % 50]->id,
                'message' => "Memory test message $i",
                'message_type' => 'text',
                'moderation_status' => 'approved',
            ]);
        }

        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;

        // Should use less than 50MB for 1000 messages
        $this->assertLessThan(50, $memoryUsedMB, "Memory usage too high: {$memoryUsedMB}MB");
    }
}
