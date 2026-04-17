<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\Models\ElectronicOrder;
use App\Domains\Electronics\Models\WarrantyClaim;
use App\Domains\Electronics\Services\WarrantyService;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

final class WarrantyServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private WarrantyService $service;
    private FraudControlService $fraudControl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(WarrantyService::class);
        $this->fraudControl = $this->app->make(FraudControlService::class);
    }

    public function test_create_warranty_claim_creates_claim_record(): void
    {
        $order = ElectronicOrder::factory()->create([
            'created_at' => now()->subMonth(),
        ]);

        $claim = $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_TEST_123',
            issueDescription: 'Screen not working',
            correlationId: (string) Str::uuid(),
        );

        $this->assertInstanceOf(WarrantyClaim::class, $claim);
        $this->assertEquals($order->id, $claim->order_id);
        $this->assertEquals('SN_TEST_123', $claim->serial_number);
        $this->assertEquals('Screen not working', $claim->description);
        $this->assertEquals('pending', $claim->status);
        $this->assertNotNull($claim->uuid);
    }

    public function test_create_warranty_claim_respects_rate_limiting(): void
    {
        $order = ElectronicOrder::factory()->create();

        // Create 5 claims (hit the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->service->createWarrantyClaim(
                orderId: $order->id,
                serialNumber: "SN_RATE_{$i}",
                issueDescription: "Test issue {$i}",
                correlationId: (string) Str::uuid(),
            );
        }

        // 6th attempt should fail due to rate limiting
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Слишком много заявок');

        $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_RATE_5',
            issueDescription: 'Should fail',
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_create_warranty_claim_performs_fraud_check(): void
    {
        $order = ElectronicOrder::factory()->create();

        // Mock fraud check to return allow decision
        $this->fraudControl->shouldReceive('check')
            ->once()
            ->with(
                userId: \Mockery::type('int'),
                operationType: 'mutation',
                amount: 0,
                correlationId: \Mockery::type('string')
            )
            ->andReturn(['decision' => 'allow', 'score' => 10]);

        $claim = $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_FRAUD_TEST',
            issueDescription: 'Test',
            correlationId: (string) Str::uuid(),
        );

        $this->assertNotNull($claim);
    }

    public function test_create_warranty_claim_blocks_on_high_fraud_score(): void
    {
        $order = ElectronicOrder::factory()->create();

        // Mock fraud check to return block decision
        $this->fraudControl->shouldReceive('check')
            ->once()
            ->with(
                userId: \Mockery::type('int'),
                operationType: 'mutation',
                amount: 0,
                correlationId: \Mockery::type('string')
            )
            ->andReturn(['decision' => 'block', 'score' => 95]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Операция заблокирована системой безопасности');

        $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_BLOCK_TEST',
            issueDescription: 'Test',
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_accept_for_repairs_updates_status(): void
    {
        $claim = WarrantyClaim::factory()->create([
            'status' => 'pending',
        ]);

        $this->service->acceptForRepair(
            claimId: $claim->id,
            correlationId: (string) Str::uuid(),
        );

        $claim->refresh();
        $this->assertEquals('in_repair', $claim->status);
        $this->assertNotNull($claim->accepted_at);
    }

    public function test_finish_repairs_updates_status_to_completed(): void
    {
        $claim = WarrantyClaim::factory()->create([
            'status' => 'in_repair',
        ]);

        $this->service->finishRepair(
            claimId: $claim->id,
            isReturnToStock: false,
            correlationId: (string) Str::uuid(),
        );

        $claim->refresh();
        $this->assertEquals('completed', $claim->status);
        $this->assertNotNull($claim->finished_at);
    }

    public function test_finish_repairs_with_return_to_stock(): void
    {
        $claim = WarrantyClaim::factory()->create([
            'status' => 'in_repair',
            'product_id' => 123,
        ]);

        $inventoryService = $this->app->make(InventoryManagementService::class);
        $inventoryService->shouldReceive('addStock')
            ->once()
            ->with(
                itemId: 123,
                quantity: 1,
                reason: 'Warranty replacement / return to stock',
                sourceType: 'electronics_warranty',
                sourceId: $claim->id
            );

        $this->service->finishRepair(
            claimId: $claim->id,
            isReturnToStock: true,
            correlationId: (string) Str::uuid(),
        );

        $claim->refresh();
        $this->assertEquals('completed', $claim->status);
    }

    public function test_create_warranty_claim_throws_for_expired_warranty(): void
    {
        $order = ElectronicOrder::factory()->create([
            'created_at' => now()->subMonths(13), // 13 months ago (expired)
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Гарантийный срок истек');

        $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_EXPIRED',
            issueDescription: 'Test',
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_create_warranty_claim_throws_for_nonexistent_order(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->createWarrantyClaim(
            orderId: 99999,
            serialNumber: 'SN_NO_ORDER',
            issueDescription: 'Test',
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_accept_for_repairs_throws_for_nonexistent_claim(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->acceptForRepair(
            claimId: 99999,
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_finish_repairs_throws_for_nonexistent_claim(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->finishRepair(
            claimId: 99999,
            isReturnToStock: false,
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_rate_limit_key_uses_order_id(): void
    {
        $order = ElectronicOrder::factory()->create();

        $claim = $this->service->createWarrantyClaim(
            orderId: $order->id,
            serialNumber: 'SN_LIMIT_KEY',
            issueDescription: 'Test',
            correlationId: (string) Str::uuid(),
        );

        $this->assertNotNull($claim);

        // Verify rate limiter was hit by checking cache
        $rateLimitKey = "electronics:warranty:{$order->id}";
        $this->assertTrue(Cache::store('rate-limiter')->has($rateLimitKey));
    }
}
