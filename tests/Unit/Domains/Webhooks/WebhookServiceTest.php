<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Webhooks;

use App\Services\Webhook\WebhookManagementService;
use App\Services\Webhook\WebhookSignatureValidator;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Tests\TestCase;

final class WebhookServiceTest extends TestCase
{
    private WebhookManagementService $service;
    private WebhookSignatureValidator $validator;
    private DatabaseManager $db;
    private AuditService $audit;
    private FraudControlService $fraud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = app(DatabaseManager::class);
        $this->audit = app(AuditService::class);
        $this->fraud = app(FraudControlService::class);
        
        $this->service = new WebhookManagementService(
            $this->db,
            app(LogManager::class),
            $this->audit,
            $this->fraud,
        );

        $this->validator = new WebhookSignatureValidator();
    }

    public function test_create_webhook_endpoint(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Test Webhook',
            url: 'https://example.com/webhook',
            events: ['order.created', 'order.updated'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $this->assertIsInt($webhookId);
        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $webhookId,
            'tenant_id' => 1,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);
    }

    public function test_create_webhook_endpoint_with_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createEndpoint(
            tenantId: 1,
            name: 'Invalid Webhook',
            url: 'not-a-valid-url',
            events: ['order.created'],
            secret: 'test-secret',
            correlationId: 'test-123',
        );
    }

    public function test_trigger_webhook_delivery(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Test Webhook',
            url: 'https://example.com/webhook',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1, 'total' => 1000],
            correlationId: 'test-123',
        );

        $this->assertIsInt($deliveryId);
        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $deliveryId,
            'webhook_id' => $webhookId,
            'event' => 'order.created',
        ]);
    }

    public function test_retry_failed_delivery(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Test Webhook',
            url: 'https://example.com/webhook',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        // Mark as failed
        $this->db->table('webhook_deliveries')
            ->where('id', $deliveryId)
            ->update(['status' => 'failed', 'attempt_count' => 3]);

        $result = $this->service->retryDelivery($deliveryId, 'test-123');

        $this->assertTrue($result);
    }

    public function test_validate_webhook_signature(): void
    {
        $secret = 'test-secret-123';
        $payload = json_encode(['order_id' => 1, 'total' => 1000]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $isValid = $this->validator->validate(
            payload: $payload,
            signature: $signature,
            secret: $secret,
        );

        $this->assertTrue($isValid);
    }

    public function test_validate_invalid_webhook_signature(): void
    {
        $secret = 'test-secret-123';
        $payload = json_encode(['order_id' => 1]);
        $signature = 'invalid-signature';

        $isValid = $this->validator->validate(
            payload: $payload,
            signature: $signature,
            secret: $secret,
        );

        $this->assertFalse($isValid);
    }

    public function test_deactivate_webhook_endpoint(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Test Webhook',
            url: 'https://example.com/webhook',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $result = $this->service->deactivateEndpoint($webhookId, 'test-123');

        $this->assertTrue($result);
        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $webhookId,
            'is_active' => false,
        ]);
    }

    public function test_get_active_endpoints_for_event(): void
    {
        $this->service->createEndpoint(
            tenantId: 1,
            name: 'Order Webhook',
            url: 'https://example.com/order-webhook',
            events: ['order.created', 'order.updated'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $this->service->createEndpoint(
            tenantId: 1,
            name: 'Payment Webhook',
            url: 'https://example.com/payment-webhook',
            events: ['payment.completed'],
            secret: 'test-secret-456',
            correlationId: 'test-123',
        );

        $endpoints = $this->service->getActiveEndpointsForEvent(1, 'order.created');

        $this->assertCount(1, $endpoints);
        $this->assertEquals('Order Webhook', $endpoints[0]['name']);
    }

    public function test_get_delivery_stats(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Test Webhook',
            url: 'https://example.com/webhook',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        $this->service->triggerDelivery($webhookId, 'order.created', ['order_id' => 1], 'test-123');
        $this->service->triggerDelivery($webhookId, 'order.created', ['order_id' => 2], 'test-123');

        $stats = $this->service->getDeliveryStats($webhookId, 'month');

        $this->assertArrayHasKey('total_deliveries', $stats);
        $this->assertArrayHasKey('successful_deliveries', $stats);
        $this->assertArrayHasKey('failed_deliveries', $stats);
        $this->assertEquals(2, $stats['total_deliveries']);
    }

    protected function tearDown(): void
    {
        $this->db->table('webhook_deliveries')->truncate();
        $this->db->table('webhook_endpoints')->truncate();
        parent::tearDown();
    }
}
