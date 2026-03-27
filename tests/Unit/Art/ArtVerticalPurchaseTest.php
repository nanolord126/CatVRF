<?php

declare(strict_types=1);

namespace Tests\Unit\Art;

use App\Models\Art\ArtGallery;
use App\Models\Art\Artist;
use App\Models\Art\Artwork;
use App\Models\Art\ArtOrder;
use App\Services\Art\ArtService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * ArtVerticalPurchaseTest — Logic validation for artwork transaction flows.
 * Ensures safety, commission calculation, and status updates.
 */
final class ArtVerticalPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private ArtService $artService;
    private $fraudMock;
    private $walletMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mocking dependencies for the service
        $this->fraudMock = Mockery::mock(FraudControlService::class);
        $this->walletMock = Mockery::mock(WalletService::class);

        $this->artService = new ArtService(
            $this->fraudMock,
            $this->walletMock,
            'test-correlation-id'
        );
    }

    /** @test */
    public function it_processes_artwork_purchase_with_correct_commission(): void
    {
        // 1. Arrange Data
        $gallery = ArtGallery::factory()->create(['tenant_id' => 1]);
        $artist = Artist::factory()->create(['gallery_id' => $gallery->id, 'tenant_id' => 1]);
        $artwork = Artwork::factory()->create([
            'gallery_id' => $gallery->id,
            'artist_id' => $artist->id,
            'price_cents' => 100000, // 1000 RUB
            'status' => 'available',
            'tenant_id' => 1
        ]);

        $userId = 999;
        $expectedCommission = 14000; // 14% of 100000
        $expectedSellerAmount = 86000; // 86% of 100000

        // 2. Mock Expectations
        $this->fraudMock->shouldReceive('check')->once();
        
        $this->walletMock->shouldReceive('debit')
            ->with($userId, 100000, Mockery::type('string'))
            ->once();

        $this->walletMock->shouldReceive('credit')
            ->with($gallery->tenant_id, $expectedSellerAmount, Mockery::type('string'))
            ->once();

        // 3. Act
        $order = $this->artService->purchaseArtwork($userId, $artwork->id);

        // 4. Assert
        $this->assertInstanceOf(ArtOrder::class, $order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(100000, $order->total_cents);
        
        // Check Artwork status
        $artwork->refresh();
        $this->assertEquals('sold', $artwork->status);

        // Verify Database has order
        $this->assertDatabaseHas('art_orders', [
            'id' => $order->id,
            'status' => 'paid',
            'artwork_id' => $artwork->id,
            'correlation_id' => 'test-correlation-id'
        ]);
    }

    /** @test */
    public function it_prevents_purchasing_already_sold_artwork(): void
    {
        $artwork = Artwork::factory()->create(['status' => 'sold']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Artwork is not available for purchase.');

        $this->artService->purchaseArtwork(1, $artwork->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
