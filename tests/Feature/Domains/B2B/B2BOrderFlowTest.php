<?php declare(strict_types=1);

namespace Tests\Feature\Domains\B2B;

use App\Services\B2B\B2BOrderService;
use App\Services\B2B\B2BApiKeyService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class B2BOrderFlowTest extends TestCase
{
    private B2BOrderService $orderService;
    private B2BApiKeyService $apiKeyService;
    private FraudControlService $fraud;
    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = app(B2BOrderService::class);
        $this->apiKeyService = app(B2BApiKeyService::class);
        $this->fraud = app(FraudControlService::class);
        $this->audit = app(AuditService::class);
    }

    public function test_create_b2b_api_key(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Test API Key',
            permissions: ['orders:create', 'orders:read'],
            correlationId: 'test-123',
        );

        $this->assertIsString($apiKey);
        $this->assertNotEmpty($apiKey);
    }

    public function test_validate_b2b_api_key(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Test API Key',
            permissions: ['orders:create'],
            correlationId: 'test-123',
        );

        $isValid = $this->apiKeyService->validateKey($apiKey, 'orders:create', 'test-123');

        $this->assertTrue($isValid);
    }

    public function test_create_b2b_order(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Test API Key',
            permissions: ['orders:create'],
            correlationId: 'test-123',
        );

        $orderId = $this->orderService->createOrder(
            tenantId: 1,
            businessGroupId: 1,
            apiKey: $apiKey,
            vertical: 'food',
            items: [
                ['product_id' => 1, 'quantity' => 10, 'price' => 1000],
                ['product_id' => 2, 'quantity' => 5, 'price' => 2000],
            ],
            metadata: ['b2b_reference' => 'REF-001'],
            correlationId: 'test-123',
        );

        $this->assertIsInt($orderId);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'tenant_id' => 1,
            'business_group_id' => 1,
            'is_b2b' => true,
        ]);
    }

    public function test_b2b_order_with_tier_pricing(): void
    {
        // Create business group with tier
        $this->db->table('business_groups')->insert([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'name' => 'Gold Tier Business',
            'tier' => 'gold',
            'monthly_volume' => 2000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessGroupId = $this->db->table('business_groups')->where('name', 'Gold Tier Business')->value('id');

        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: $businessGroupId,
            name: 'Gold Tier API Key',
            permissions: ['orders:create'],
            correlationId: 'test-123',
        );

        $orderId = $this->orderService->createOrder(
            tenantId: 1,
            businessGroupId: $businessGroupId,
            apiKey: $apiKey,
            vertical: 'food',
            items: [
                ['product_id' => 1, 'quantity' => 10, 'price' => 1000],
            ],
            metadata: [],
            correlationId: 'test-123',
        );

        $order = $this->db->table('orders')->where('id', $orderId)->first();

        // Gold tier should get 10% discount
        $expectedTotal = 10000 * 0.9; // 9000 копеек
        $this->assertEquals($expectedTotal, $order->total);
    }

    public function test_b2b_order_fraud_check(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Test API Key',
            permissions: ['orders:create'],
            correlationId: 'test-123',
        );

        // Simulate fraud scenario
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fraud check failed');

        // This should trigger fraud check
        $this->orderService->createOrder(
            tenantId: 1,
            businessGroupId: 1,
            apiKey: $apiKey,
            vertical: 'food',
            items: [
                ['product_id' => 1, 'quantity' => 99999, 'price' => 1], // Suspicious high quantity
            ],
            metadata: [],
            correlationId: 'test-123',
        );
    }

    public function test_b2b_order_in_transaction(): void
    {
        $apiKey = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Test API Key',
            permissions: ['orders:create'],
            correlationId: 'test-123',
        );

        $orderId = $this->orderService->createOrder(
            tenantId: 1,
            businessGroupId: 1,
            apiKey: $apiKey,
            vertical: 'food',
            items: [
                ['product_id' => 1, 'quantity' => 10, 'price' => 1000],
            ],
            metadata: [],
            correlationId: 'test-123',
        );

        // Verify transaction was used by checking audit log
        $auditLogs = $this->db->table('audit_logs')
            ->where('action', 'order_created')
            ->where('subject_id', $orderId)
            ->count();

        $this->assertGreaterThan(0, $auditLogs);
    }

    public function test_b2b_order_scoped_to_business_group(): void
    {
        $apiKey1 = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 1,
            name: 'Group 1 API Key',
            permissions: ['orders:create', 'orders:read'],
            correlationId: 'test-123',
        );

        $apiKey2 = $this->apiKeyService->createKey(
            tenantId: 1,
            businessGroupId: 2,
            name: 'Group 2 API Key',
            permissions: ['orders:create', 'orders:read'],
            correlationId: 'test-123',
        );

        $orderId1 = $this->orderService->createOrder(
            tenantId: 1,
            businessGroupId: 1,
            apiKey: $apiKey1,
            vertical: 'food',
            items: [['product_id' => 1, 'quantity' => 1, 'price' => 1000]],
            metadata: [],
            correlationId: 'test-123',
        );

        // Group 2 should not see Group 1's orders
        $orders = $this->orderService->getOrders(
            tenantId: 1,
            businessGroupId: 2,
            apiKey: $apiKey2,
            correlationId: 'test-123',
        );

        $this->assertEmpty($orders);
    }

    protected function tearDown(): void
    {
        $this->db->table('orders')->truncate();
        $this->db->table('b2b_api_keys')->truncate();
        $this->db->table('business_groups')->truncate();
        parent::tearDown();
    }
}
