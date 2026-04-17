<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Services;

use App\Domains\Webhooks\DTOs\CreateWebhookDto;
use App\Domains\Webhooks\Models\Webhook;
use App\Domains\Webhooks\Models\WebhookDelivery;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\Webhooks\Events\WebhookTriggered;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final readonly class WebhookService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Create webhook
     */
    public function create(CreateWebhookDto $dto, string $correlationId): Webhook
    {
        $this->fraud->check([
            'operation' => 'webhook_create',
            'tenant_id' => $dto->tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $webhook = Webhook::create([
                'tenant_id' => $dto->tenantId,
                'name' => $dto->name,
                'url' => $dto->url,
                'events' => $dto->events,
                'secret' => $dto->secret,
                'is_active' => $dto->isActive,
                'retry_count' => $dto->retryCount,
                'timeout' => $dto->timeout,
                'headers' => $dto->headers,
            ]);

            $this->audit->record(
                action: 'webhook_created',
                subjectType: Webhook::class,
                subjectId: $webhook->id,
                newValues: $webhook->toArray(),
                correlationId: $correlationId,
            );

            return $webhook;
        });
    }

    /**
     * Trigger webhook for event
     */
    public function trigger(int $tenantId, string $eventType, array $payload, string $correlationId): void
    {
        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($webhook) => $webhook->triggersEvent($eventType));

        foreach ($webhooks as $webhook) {
            dispatch(function () use ($webhook, $eventType, $payload, $correlationId) {
                $this->deliver($webhook, $eventType, $payload, $correlationId);
            })->onQueue('webhooks');

            event(new WebhookTriggered($webhook, $eventType, $correlationId));
        }
    }

    /**
     * Deliver webhook to endpoint
     */
    private function deliver(Webhook $webhook, string $eventType, array $payload, string $correlationId): void
    {
        $delivery = WebhookDelivery::create([
            'tenant_id' => $webhook->tenant_id,
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'correlation_id' => $correlationId,
        ]);

        try {
            $signature = $this->generateSignature($payload, $webhook->secret);

            $response = Http::timeout($webhook->timeout)
                ->withHeaders(array_merge($webhook->headers ?? [], [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $eventType,
                    'X-Webhook-ID' => $delivery->uuid,
                    'X-Correlation-ID' => $correlationId,
                ]))
                ->post($webhook->url, $payload);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'delivered_at' => now(),
            ]);

            $this->logger->channel('webhooks')->info('Webhook delivered', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'status' => $response->status(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $delivery->update([
                'response_code' => 0,
                'response_body' => $e->getMessage(),
                'failed_at' => now(),
                'retry_count' => $delivery->retry_count + 1,
                'next_retry_at' => now()->addMinutes(pow(2, $delivery->retry_count)),
            ]);

            $this->logger->channel('webhooks')->error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            if ($delivery->shouldRetry()) {
                dispatch(function () use ($webhook, $eventType, $payload, $correlationId) {
                    $this->deliver($webhook, $eventType, $payload, $correlationId);
                })->delay($delivery->next_retry_at)->onQueue('webhooks');
            }
        }
    }

    /**
     * Generate HMAC signature for webhook
     */
    private function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Delete webhook
     */
    public function delete(int $tenantId, int $webhookId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($tenantId, $webhookId, $correlationId) {
            $webhook = Webhook::where('id', $webhookId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$webhook) {
                return false;
            }

            $webhook->delete();

            $this->audit->record(
                action: 'webhook_deleted',
                subjectType: Webhook::class,
                subjectId: $webhookId,
                correlationId: $correlationId,
            );

            return true;
        });
    }

    /**
     * Get webhooks for tenant
     */
    public function getWebhooks(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Webhook::where('tenant_id', $tenantId)->get();
    }

    /**
     * Get webhook deliveries
     */
    public function getDeliveries(int $tenantId, ?int $webhookId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = WebhookDelivery::where('tenant_id', $tenantId);
        
        if ($webhookId) {
            $query->where('webhook_id', $webhookId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
