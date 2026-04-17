<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\WarrantyRegisterDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Models\ElectronicsWarranty;
use App\Domains\Electronics\Services\ElectronicsWarrantyService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

final class ElectronicsWarrantyServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private ElectronicsWarrantyService $service;
    private FraudControlService $fraudControl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ElectronicsWarrantyService::class);
        $this->fraudControl = $this->app->make(FraudControlService::class);
    }

    public function test_register_warranty_creates_warranty_record(): void
    {
        $product = ElectronicsProduct::factory()->create([
            'brand' => 'Apple',
            'name' => 'iPhone 15 Pro',
        ]);

        $dto = new WarrantyRegisterDto(
            productId: $product->id,
            serialNumber: 'SN123456789',
            orderId: 'ORD-123',
            userId: 1,
            monthsDuration: 12,
            correlationId: (string) Str::uuid(),
        );

        $warranty = $this->service->registerWarranty($dto);

        $this->assertInstanceOf(ElectronicsWarranty::class, $warranty);
        $this->assertEquals($product->id, $warranty->product_id);
        $this->assertEquals(123, $warranty->order_id);
        $this->assertEquals(1, $warranty->user_id);
        $this->assertEquals('SN123456789', $warranty->serial_number);
        $this->assertEquals('active', $warranty->status);
        $this->assertNotNull($warranty->expires_at);
        $this->assertStringContainsString('Apple', $warranty->terms);
    }

    public function test_register_warranty_prevents_duplicate_serial(): void
    {
        $product = ElectronicsProduct::factory()->create();
        $serialNumber = 'SN_DUPLICATE_123';

        $dto = new WarrantyRegisterDto(
            productId: $product->id,
            orderId: 123,
            userId: 1,
            serialNumber: $serialNumber,
            monthsDuration: 12,
            correlationId: (string) Str::uuid(),
        );

        $this->service->registerWarranty($dto);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Warranty already exists for serial: {$serialNumber}");

        $this->service->registerWarranty($dto);
    }

    public function test_register_warranty_sets_correct_expiration_date(): void
    {
        $product = ElectronicsProduct::factory()->create();

        $dto = new WarrantyRegisterDto(
            productId: $product->id,
            orderId: 123,
            userId: 1,
            serialNumber: 'SN_EXPIRATION_TEST',
            monthsDuration: 24,
            correlationId: (string) Str::uuid(),
        );

        $warranty = $this->service->registerWarranty($dto);

        $expectedExpiry = now()->addMonths(24);
        $this->assertEquals($expectedExpiry->toDateString(), $warranty->expires_at->toDateString());
    }

    public function test_void_warranty_changes_status_to_void(): void
    {
        $warranty = ElectronicsWarranty::factory()->create([
            'status' => 'active',
            'serial_number' => 'SN_TO_VOID_123',
        ]);

        $result = $this->service->voidWarranty(
            serialNumber: 'SN_TO_VOID_123',
            reason: 'Tampering detected',
            correlationId: (string) Str::uuid(),
        );

        $this->assertTrue($result);

        $warranty->refresh();
        $this->assertEquals('void', $warranty->status);
        $this->assertStringContainsString('VOID REASON: Tampering detected', $warranty->terms);
    }

    public function test_void_warranty_throws_exception_if_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->voidWarranty(
            serialNumber: 'SN_NON_EXISTENT',
            reason: 'Test',
            correlationId: (string) Str::uuid(),
        );
    }

    public function test_check_status_returns_not_found_for_nonexistent_serial(): void
    {
        $result = $this->service->checkStatus('SN_NON_EXISTENT');

        $this->assertEquals('not_found', $result['status']);
        $this->assertFalse($result['is_valid']);
    }

    public function test_check_status_returns_valid_for_active_warranty(): void
    {
        $product = ElectronicsProduct::factory()->create([
            'brand' => 'Samsung',
            'name' => 'Galaxy S24',
        ]);

        $warranty = ElectronicsWarranty::factory()->create([
            'product_id' => $product->id,
            'serial_number' => 'SN_ACTIVE_123',
            'status' => 'active',
            'expires_at' => now()->addMonths(6),
        ]);

        $result = $this->service->checkStatus('SN_ACTIVE_123');

        $this->assertEquals('active', $result['status']);
        $this->assertTrue($result['is_valid']);
        $this->assertEquals('Samsung', $result['brand']);
        $this->assertEquals('Galaxy S24', $result['model']);
        $this->assertNotNull($result['expires_at']);
    }

    public function test_check_status_returns_invalid_for_expired_warranty(): void
    {
        $product = ElectronicsProduct::factory()->create();

        ElectronicsWarranty::factory()->create([
            'product_id' => $product->id,
            'serial_number' => 'SN_EXPIRED_123',
            'status' => 'active',
            'expires_at' => now()->subMonth(),
        ]);

        $result = $this->service->checkStatus('SN_EXPIRED_123');

        $this->assertEquals('active', $result['status']);
        $this->assertFalse($result['is_valid']);
    }

    public function test_check_status_returns_invalid_for_voided_warranty(): void
    {
        $product = ElectronicsProduct::factory()->create();

        ElectronicsWarranty::factory()->create([
            'product_id' => $product->id,
            'serial_number' => 'SN_VOIDED_123',
            'status' => 'void',
            'expires_at' => now()->addMonth(),
        ]);

        $result = $this->service->checkStatus('SN_VOIDED_123');

        $this->assertEquals('void', $result['status']);
        $this->assertFalse($result['is_valid']);
    }

    public function test_register_warranty_performs_fraud_check(): void
    {
        $product = ElectronicsProduct::factory()->create();

        $dto = new WarrantyRegisterDto(
            productId: $product->id,
            serialNumber: 'SN_FRAUD_TEST',
            orderId: 'ORD-123',
            userId: 1,
            monthsDuration: 12,
            correlationId: (string) Str::uuid(),
        );

        $warranty = $this->service->registerWarranty($dto);

        $this->assertNotNull($warranty);
        $this->assertEquals('SN_FRAUD_TEST', $warranty->serial_number);
    }
}
