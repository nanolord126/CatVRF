<?php declare(strict_types=1);

namespace Tests\Integration\Webhooks;

use App\Services\Webhook\WebhookManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WebhookIntegrationTest extends TestCase
{
    private WebhookManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WebhookManagementService::class);
    }

    public function test_webhook_delivery_to_external_endpoint(): void
    {
        // Create webhook endpoint
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'External Test Webhook',
            url: 'https://webhook.site/test-endpoint',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        // Mock HTTP client
        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200),
        ]);

        // Trigger delivery
        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1, 'total' => 1000],
            correlationId: 'test-123',
        );

        // Verify HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.site/test-endpoint' &&
                   $request->hasHeader('X-Webhook-Signature') &&
                   $request->hasHeader('Content-Type');
        });

        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $deliveryId,
            'status' => 'delivered',
            'http_status_code' => 200,
        ]);
    }

    public function test_webhook_retry_on_failure(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Flaky Webhook',
            url: 'https://webhook.site/flaky',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        // Mock HTTP client to fail first time, succeed second time
        Http::fakeSequence()
            ->push(['error' => 'server error'], 500)
            ->push(['success' => true], 200);

        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        // First attempt should fail
        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $deliveryId,
            'attempt_count' => 1,
            'status' => 'failed',
        ]);

        // Retry
        $this->service->retryDelivery($deliveryId, 'test-123');

        // Should succeed on retry
        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $deliveryId,
            'attempt_count' => 2,
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_signature_validation(): void
    {
        $secret = 'test-secret-123';
        $payload = json_encode(['order_id' => 1, 'total' => 1000]);
        $signature = hash_hmac('sha256', $payload, $secret);

        // Create webhook endpoint
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Signed Webhook',
            url: 'https://webhook.site/signed',
            events: ['order.created'],
            secret: $secret,
            correlationId: 'test-123',
        );

        Http::fake([
            'webhook.site/*' => Http::response(['verified' => true], 200),
        ]);

        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: json_decode($payload, true),
            correlationId: 'test-123',
        );

        // Verify signature was sent
        Http::assertSent(function ($request) use ($signature) {
            $receivedSignature = $request->header('X-Webhook-Signature')[0] ?? '';
            return hash_equals($signature, $receivedSignature);
        });
    }

    public function test_webhook_timeout_handling(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Slow Webhook',
            url: 'https://webhook.site/slow',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        // Mock timeout
        Http::fake([
            'webhook.site/slow' => Http::timeout(1)->response([], 200),
        ]);

        $deliveryId = $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        // Should handle timeout gracefully
        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $deliveryId,
        ]);
    }

    public function test_multiple_webhooks_for_single_event(): void
    {
        // Create multiple endpoints for same event
        $webhookId1 = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Webhook 1',
            url: 'https://webhook.site/endpoint1',
            events: ['order.created'],
            secret: 'secret-1',
            correlationId: 'test-123',
        );

        $webhookId2 = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Webhook 2',
            url: 'https://webhook.site/endpoint2',
            events: ['order.created'],
            secret: 'secret-2',
            correlationId: 'test-123',
        );

        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200),
        ]);

        // Trigger to both
        $deliveryId1 = $this->service->triggerDelivery(
            webhookId: $webhookId1,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        $deliveryId2 = $this->service->triggerDelivery(
            webhookId: $webhookId2,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        // Both should be delivered
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $deliveryId1, 'status' => 'delivered']);
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $deliveryId2, 'status' => 'delivered']);

        // Verify both HTTP requests were made
        Http::assertSentCount(2);
    }

    public function test_webhook_deactivation_prevents_delivery(): void
    {
        $webhookId = $this->service->createEndpoint(
            tenantId: 1,
            name: 'Inactive Webhook',
            url: 'https://webhook.site/inactive',
            events: ['order.created'],
            secret: 'test-secret-123',
            correlationId: 'test-123',
        );

        // Deactivate
        $this->service->deactivateEndpoint($webhookId, 'test-123');

        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200),
        ]);

        // Try to trigger delivery
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Webhook endpoint is not active');

        $this->service->triggerDelivery(
            webhookId: $webhookId,
            event: 'order.created',
            payload: ['order_id' => 1],
            correlationId: 'test-123',
        );

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    protected function tearDown(): void
    {
        $this->db->table('webhook_deliveries')->truncate();
        $this->db->table('webhook_endpoints')->truncate();
        parent::tearDown();
    }
}
