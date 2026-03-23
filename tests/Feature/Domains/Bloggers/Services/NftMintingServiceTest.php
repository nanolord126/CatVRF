<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Bloggers\Services;

use App\Domains\Bloggers\Models\NftGift;
use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\BloggerProfile;
use App\Domains\Bloggers\Services\NftMintingService;
use App\Domains\Bloggers\Jobs\MintNftGiftJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class NftMintingServiceTest extends TestCase
{
    use RefreshDatabase;

    private NftMintingService $service;
    private Stream $stream;
    private BloggerProfile $blogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NftMintingService::class);
        
        $bloggerUser = \App\Models\User::factory()->create();
        $this->blogger = BloggerProfile::factory()
            ->for($bloggerUser)
            ->create(['verification_status' => 'verified']);

        $this->stream = Stream::factory()
            ->for($this->blogger, 'blogger')
            ->create(['status' => 'live']);
    }

    public function test_create_gift_creates_nft_gift_record(): void
    {
        $gift = $this->service->createGift(
            streamId: (int) $this->stream->id,
            senderUserId: 1,
            recipientUserId: $this->blogger->user_id,
            amount: 50000,
            giftType: 'gold',
            message: 'Test gift',
            correlationId: '123-456',
        );

        $this->assertInstanceOf(NftGift::class, $gift);
        $this->assertEquals('pending', $gift->minting_status);
        $this->assertEquals(50000, $gift->amount);
        $this->assertEquals('gold', $gift->gift_type);
        $this->assertNotNull($gift->uuid);
        $this->assertNotNull($gift->correlation_id);
    }

    public function test_create_gift_queues_minting_job(): void
    {
        \Queue::fake();

        $gift = $this->service->createGift(
            streamId: (int) $this->stream->id,
            senderUserId: 1,
            recipientUserId: $this->blogger->user_id,
            amount: 50000,
            giftType: 'gold',
            message: '',
            correlationId: '123-456',
        );

        \Queue::assertPushed(MintNftGiftJob::class);
    }

    public function test_create_gift_validates_amount_range(): void
    {
        // Min amount
        $this->expectException(\Exception::class);
        $this->service->createGift(
            streamId: (int) $this->stream->id,
            senderUserId: 1,
            recipientUserId: $this->blogger->user_id,
            amount: 50,  // Below minimum
            giftType: 'gold',
            message: '',
            correlationId: '123-456',
        );
    }

    public function test_create_gift_rate_limiting(): void
    {
        // Create multiple gifts rapidly
        for ($i = 0; $i < 51; $i++) {
            try {
                $this->service->createGift(
                    streamId: (int) $this->stream->id,
                    senderUserId: 1,
                    recipientUserId: $this->blogger->user_id,
                    amount: 50000,
                    giftType: 'gold',
                    message: '',
                    correlationId: "test-$i",
                );
            } catch (\Exception $e) {
                $this->assertStringContainsString('rate limit', strtolower($e->getMessage()));
                return;
            }
        }

        $this->fail('Rate limiting not enforced');
    }

    public function test_mint_gift_acquires_redis_lock(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create(['minting_status' => 'pending']);

        $this->service->mintGift($gift->id);

        // Verify lock was acquired (by checking gift status updated)
        $gift->refresh();
        $this->assertNotNull($gift->minted_at);
    }

    public function test_mint_gift_prevents_race_condition(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create(['minting_status' => 'pending']);

        // Set Redis lock
        Redis::set("nft_minting_lock:{$gift->id}", true, 'EX', 30);

        // Try to mint - should skip due to lock
        $this->service->mintGift($gift->id);

        $gift->refresh();
        // Should still be pending due to lock
        $this->assertEquals('pending', $gift->minting_status);
    }

    public function test_mint_gift_updates_status_to_minted(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create(['minting_status' => 'pending']);

        $this->service->mintGift($gift->id);

        $gift->refresh();
        $this->assertEquals('minted', $gift->minting_status);
        $this->assertNotNull($gift->minted_at);
        $this->assertNotNull($gift->nft_address);
    }

    public function test_mint_gift_sets_upgrade_eligible_at_14_days(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create(['minting_status' => 'pending']);

        $this->service->mintGift($gift->id);

        $gift->refresh();
        $expectedUpgradeDate = now()->addDays(14);
        
        $this->assertNotNull($gift->upgrade_eligible_at);
        $this->assertTrue(
            $gift->upgrade_eligible_at->diffInDays($expectedUpgradeDate) <= 1
        );
    }

    public function test_build_metadata_returns_valid_json(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create();

        $metadata = $this->service->buildMetadata($gift->id);

        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('image', $metadata);
        $this->assertArrayHasKey('attributes', $metadata);
        $this->assertArrayHasKey('external_url', $metadata);
    }

    public function test_upgrade_to_collector_nft_checks_eligibility(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create([
                'minting_status' => 'minted',
                'minted_at' => now()->subDays(5),  // Only 5 days old
                'upgrade_eligible_at' => now()->addDays(9),
            ]);

        $this->expectException(\Exception::class);
        $this->service->upgradeToCollectorNft($gift->id, 'test-correlation');
    }

    public function test_upgrade_to_collector_nft_marks_as_upgraded(): void
    {
        $gift = NftGift::factory()
            ->for($this->stream)
            ->create([
                'minting_status' => 'minted',
                'minted_at' => now()->subDays(15),  // 15 days old
                'upgrade_eligible_at' => now()->subDays(1),
            ]);

        $upgraded = $this->service->upgradeToCollectorNft($gift->id, 'test-correlation');

        $this->assertTrue($upgraded->is_upgraded);
        $this->assertNotNull($upgraded->upgraded_at);
    }

    public function test_get_failed_mint_attempts_returns_recent_failures(): void
    {
        NftGift::factory(3)
            ->for($this->stream)
            ->create([
                'minting_status' => 'failed',
                'created_at' => now()->subHours(12),
            ]);

        NftGift::factory(2)
            ->for($this->stream)
            ->create([
                'minting_status' => 'failed',
                'created_at' => now()->subDays(2),
            ]);

        $failures = $this->service->getFailedMintAttempts();

        $this->assertEquals(3, $failures->count());
    }

    public function test_create_gift_logs_to_audit(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf()
            ->shouldReceive('info');

        $this->service->createGift(
            streamId: (int) $this->stream->id,
            senderUserId: 1,
            recipientUserId: $this->blogger->user_id,
            amount: 50000,
            giftType: 'gold',
            message: '',
            correlationId: '123-456',
        );
    }
}
