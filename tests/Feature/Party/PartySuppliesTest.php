<?php

declare(strict_types=1);

namespace Tests\Feature\Party;

use Tests\TestCase;
use App\Models\Party\PartyStore;
use App\Models\Party\PartyProduct;
use App\Models\Party\PartyOrder;
use App\Models\User;
use App\Services\Party\PartySuppliesService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * PartySuppliesTest.
 * Validates 2026 Canonical requirements for PartySupplies vertical.
 */
final class PartySuppliesTest extends TestCase
{
    use RefreshDatabase;

    private string $correlationId;
    private PartySuppliesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->correlationId = (string) Str::uuid();
        
        // Mocks for mandatory core services
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->andReturn(true);
        
        $walletMock = Mockery::mock(WalletService::class);
        $walletMock->shouldReceive('credit')->andReturn(true);

        $this->service = new PartySuppliesService($fraudMock, $walletMock, $this->correlationId);
    }

    /**
     * Test: Creating festive order with full audit logging and correlation_id.
     */
    public function testOrderCreation(): void
    {
        $user = User::factory()->create();
        $store = PartyStore::factory()->create();
        $product = PartyProduct::factory()->create([
            'party_store_id' => $store->id,
            'current_stock' => 100,
            'price_cents' => 1000,
        ]);

        $data = [
            'user_id' => $user->id,
            'party_store_id' => $store->id,
            'total_cents' => 50000,
            'event_date' => now()->addDays(10),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2]
            ]
        ];

        $order = $this->service->createOrder($data);

        $this->assertDatabaseHas('party_orders', [
            'uuid' => $order->uuid,
            'status' => 'pending',
            'correlation_id' => $this->correlationId,
        ]);

        $this->assertEquals(49996, $product->fresh()->current_stock + 100 - 2 + 49898); // stock decrement logic check
        // Above stock check is simulation, checking actual stock:
        $this->assertEquals(98, $product->fresh()->current_stock);
    }

    /**
     * Test: Automatic prepayment calculation for large orders (>50000).
     */
    public function testPrepaymentCalculation(): void
    {
        $user = User::factory()->create();
        $store = PartyStore::factory()->create();
        $product = PartyProduct::factory()->create(['party_store_id' => $store->id, 'current_stock' => 500, 'price_cents' => 100]);

        $data = [
            'user_id' => $user->id,
            'party_store_id' => $store->id,
            'total_cents' => 100000, // 1000.00 RUB
            'event_date' => now()->addDays(5),
            'items' => [['product_id' => $product->id, 'quantity' => 10]]
        ];

        $order = $this->service->createOrder($data);
        
        $this->assertEquals(30000, $order->prepayment_cents); // 30% of 100000
    }

    /**
     * Test: Seasonal catalog filters.
     */
    public function testCatalogFiltering(): void
    {
        PartyProduct::factory()->count(3)->create(['is_active' => true]);
        PartyProduct::factory()->create(['is_active' => false]);

        $catalog = $this->service->getCatalog();
        $this->assertCount(3, $catalog);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
