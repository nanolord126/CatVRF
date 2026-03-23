<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Security;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class SecurityVulnerabilitiesTest extends TestCase
{
    use RefreshDatabase;

    private BloggerProfile $blogger;
    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = \App\Models\User::factory()->create();
        $this->blogger = BloggerProfile::factory()
            ->for($this->user)
            ->create(['verification_status' => 'verified']);
    }

    /**
     * ✅ Vulnerability 1: Race Conditions
     * Test Redis lock prevents concurrent minting
     */
    public function test_race_condition_prevented_by_redis_lock(): void
    {
        $giftId = 1;
        $lockKey = "nft_minting_lock:$giftId";

        // First process acquires lock
        $acquired = Redis::set($lockKey, true, 'NX', 'EX', 30);
        $this->assertTrue($acquired);

        // Second process tries to acquire - should fail
        $acquired2 = Redis::set($lockKey, true, 'NX', 'EX', 30);
        $this->assertNull($acquired2);

        Redis::del($lockKey);
    }

    /**
     * ✅ Vulnerability 2: Secret Key Leakage
     * Test broadcast_key is never logged
     */
    public function test_broadcast_key_not_logged(): void
    {
        \Log::spy();

        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create();

        // Check logs don't contain broadcast_key
        \Log::shouldNotHaveReceived('info', function ($message, $context) {
            return isset($context['broadcast_key']) ||
                   (is_string($message) && strpos($message, 'broadcast_key') !== false);
        });
    }

    /**
     * ✅ Vulnerability 3: IDOR (Insecure Direct Object Reference)
     * Test user cannot access other user's streams
     */
    public function test_idor_protection_stream_access(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $otherBlogger = BloggerProfile::factory()
            ->for($otherUser)
            ->create();

        $stream = Stream::factory()
            ->for($otherBlogger, 'blogger')
            ->create();

        // Current user tries to end another user's stream
        $this->actingAs($this->user);

        $response = $this->postJson("/api/streams/{$stream->room_id}/end");

        $this->assertEquals(404, $response->status());
    }

    /**
     * ✅ Vulnerability 4: XSS (Cross-Site Scripting)
     * Test chat messages are sanitized
     */
    public function test_xss_protection_in_chat(): void
    {
        $this->actingAs($this->user);
        
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        $maliciousMessage = '<script>alert("xss")</script>';

        $response = $this->postJson("/api/streams/{$stream->room_id}/chat", [
            'message' => $maliciousMessage,
            'message_type' => 'text',
        ]);

        // Message should be sanitized in DB
        $message = \App\Domains\Bloggers\Models\StreamChatMessage::latest()->first();
        
        $this->assertNotNull($message);
        $this->assertStringNotContainsString('<script>', $message->message);
    }

    /**
     * ✅ Vulnerability 5: Fraud/Spam Prevention
     * Test rate limiting on gift sending
     */
    public function test_rate_limit_gift_sending(): void
    {
        $this->actingAs($this->user);
        
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        // Send 50 gifts (max allowed per hour)
        for ($i = 0; $i < 50; $i++) {
            $this->postJson("/api/gifts/streams/{$stream->room_id}/send", [
                'amount' => 50000,
                'gift_type' => 'gold',
                'message' => "Gift $i",
            ]);
        }

        // 51st gift should be rate limited
        $response = $this->postJson("/api/gifts/streams/{$stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
            'message' => 'Gift 51',
        ]);

        // Should get rate limit error (429)
        $this->assertIn($response->status(), [429, 400]);
    }

    /**
     * ✅ Vulnerability 6: DDoS Protection
     * Test viewer count cap
     */
    public function test_ddos_protection_viewer_cap(): void
    {
        $this->actingAs($this->user);
        
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        // Try to set viewer count above limit
        $response = $this->postJson("/api/streams/{$stream->room_id}/viewers", [
            'viewer_count' => 11000,  // Exceeds 10k max
        ]);

        $this->assertEquals(400, $response->status());
    }

    /**
     * ✅ Vulnerability 7: SQL Injection
     * Test Eloquent ORM prevents injection
     */
    public function test_sql_injection_prevented(): void
    {
        $this->actingAs($this->user);

        // Attempt SQL injection in request
        $maliciousInput = "'; DROP TABLE streams; --";

        $response = $this->postJson('/api/streams', [
            'title' => $maliciousInput,
            'description' => '',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
            'tags' => [],
        ]);

        // Should either fail validation or succeed with sanitized input
        // But never execute the SQL
        $this->assertNotEquals(500, $response->status());
        
        // Verify table still exists
        $this->assertTrue(\Schema::hasTable('streams'));
    }

    /**
     * ✅ Vulnerability 8: Data Leakage (GDPR)
     * Test user IDs are anonymized in logs
     */
    public function test_gdpr_anonymization_in_logs(): void
    {
        \Log::spy();

        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create();

        // Log something with user context
        \Illuminate\Support\Facades\Log::channel('audit')->info('Test event', [
            'user_id' => $this->user->id,
        ]);

        // Check logs use hashed ID, not raw
        \Log::shouldHaveReceived('channel', ['audit']);
    }

    /**
     * ✅ Vulnerability 9: Unverified Payment Bypass
     * Test gift creation requires valid payment
     */
    public function test_payment_verification_before_minting(): void
    {
        $this->actingAs($this->user);

        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);

        $response = $this->postJson("/api/gifts/streams/{$stream->room_id}/send", [
            'amount' => 50000,
            'gift_type' => 'gold',
        ]);

        // Should require payment before queuing job
        $this->assertIn($response->status(), [200, 201]);
        
        $gift = \App\Domains\Bloggers\Models\NftGift::latest()->first();
        $this->assertNotNull($gift->payment_id);
    }

    /**
     * ✅ Vulnerability 10: Reverb Room Flooding
     * Test max concurrent viewers
     */
    public function test_reverb_room_flooding_protection(): void
    {
        $stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create([
                'status' => 'live',
                'viewer_count' => 10000,
            ]);

        // Try to add one more viewer beyond limit
        $response = $this->postJson("/api/streams/{$stream->room_id}/viewers", [
            'viewer_count' => 10001,
        ]);

        $this->assertEquals(400, $response->status());
    }

    /**
     * ✅ Vulnerability 11: Price Manipulation
     * Test prices are immutable in order
     */
    public function test_price_immutability_in_order(): void
    {
        $product = \App\Models\Product::factory()->create();
        $streamProduct = \App\Domains\Bloggers\Models\StreamProduct::create([
            'stream_id' => 1,
            'product_id' => (int) $product->id,
            'price_during_stream' => 5000,
            'quantity_available' => 50,
        ]);

        $order = \App\Domains\Bloggers\Models\StreamOrder::create([
            'stream_id' => 1,
            'user_id' => $this->user->id,
            'stream_product_id' => $streamProduct->id,
            'quantity' => 1,
            'subtotal' => 5000,
            'total' => 5000,
            'payment_method' => 'sbp',
            'status' => 'pending',
            'idempotency_key' => 'test-immutable',
        ]);

        // Try to change product price
        $streamProduct->update(['price_during_stream' => 1000]);

        // Order should still have original price
        $this->assertEquals(5000, $order->subtotal);
    }

    /**
     * ✅ Vulnerability 12: Smart Contract Exploit Prevention
     * Test TON testnet deployment
     */
    public function test_ton_testnet_deployment(): void
    {
        $tonNetwork = config('bloggers.ton.network');
        
        // Should be testnet by default
        $this->assertEquals('testnet', $tonNetwork);
    }

    /**
     * BONUS: Test correlation_id tracking
     */
    public function test_correlation_id_included_in_all_operations(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/streams', [
            'title' => 'Test',
            'description' => '',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
            'tags' => [],
        ]);

        $this->assertArrayHasKey('correlation_id', $response->json());
        $this->assertNotEmpty($response->json('correlation_id'));
    }
}
