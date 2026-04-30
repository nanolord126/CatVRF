<?php

declare(strict_types=1);

namespace App\Services\API;

use Illuminate\Support\Collection;

use Psr\Log\LoggerInterface;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class WebhookManagementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Регистрирует вебхук
     */
    public function registerWebhook(
        string $url,
        array $events,
        int $tenantId,
        array $headers = [],
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check(
            userId: $tenantId,
            operationType: 'webhook_register',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($url, $events, $tenantId, $headers, $correlationId) {
            $webhookId = 'wh_'.uniqid();
            $secret = hash_hmac('sha256', $webhookId.microtime(), $this->config->get('app.key'));

            $webhook = [
                'id' => $webhookId,
                'url' => $url,
                'events' => $events,
                'tenant_id' => $tenantId,
                'secret' => $secret,
                'headers' => $headers,
                'status' => 'active',
                'created_at' => CarbonImmutable::now()->toDateTimeString(),
                'last_triggered_at' => null,
                'failed_attempts' => 0,
                'correlation_id' => $correlationId,
            ];

            // Save to webhook_endpoints table
            $this->db->table('webhook_endpoints')->insert([
                'tenant_id' => $tenantId,
                'url' => $url,
                'events' => json_encode($events),
                'secret' => $secret,
                'headers' => json_encode($headers),
                'status' => 'active',
                'correlation_id' => $correlationId,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);

            $this->audit->record(
                action: 'webhook_registered',
                subjectType: 'webhook_endpoint',
                subjectId: $this->db->getPdo()->lastInsertId(),
                oldValues: [],
                newValues: $webhook,
                correlationId: $correlationId,
            );

            $this->logger->channel('webhooks')->$this->logger->info('Webhook registered', [
                'webhook_id' => $webhookId,
                'url' => $url,
                'events_count' => count($events),
                'correlation_id' => $correlationId,
            ]);

            return $webhook;
        });
    }

    /**
     * Триггерит событие
     */
    public function triggerEvent(
        string $eventName,
        array $payload,
        int $tenantId,
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $event = [
            'id' => 'evt_'.uniqid(),
            'name' => $eventName,
            'payload' => $payload,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toDateTimeString(),
        ];

        // Находим все вебхуки, подписанные на это событие
        $webhooks = $this->getWebhooksForEvent($eventName, $tenantId);

        $results = [];
        foreach ($webhooks as $webhook) {
            $results[] = $this->deliverWebhook($webhook, $event, $correlationId);
        }

        $this->logger->channel('webhooks')->$this->logger->info('Event triggered', [
            'event' => $eventName,
            'webhooks_triggered' => count($webhooks),
            'correlation_id' => $correlationId,
        ]);

        return [
            'event' => $event,
            'deliveries' => $results,
            'total' => count($results),
            'successful' => new Collection($results)->filter(fn ($r) => $r['status'] === 'success')->count(),
        ];
    }

    /**
     * Повторно доставляет вебхук с экспоненциальным backoff
     */
    public function retryDelivery(string $deliveryId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $delivery = $this->db->table('webhook_deliveries')
            ->where('id', $deliveryId)
            ->first();

        if (! $delivery) {
            return ['status' => 'not_found', 'delivery_id' => $deliveryId];
        }

        $attempt = $delivery->retry_count + 1;
        $maxRetries = 5;

        if ($attempt > $maxRetries) {
            $this->db->table('webhook_deliveries')
                ->where('id', $deliveryId)
                ->update([
                    'status' => 'failed',
                    'retry_count' => $attempt,
                    'updated_at' => CarbonImmutable::now(),
                ]);

            return ['status' => 'max_retries_exceeded', 'delivery_id' => $deliveryId];
        }

        // Exponential backoff: 1s, 2s, 4s, 8s, 16s
        $delay = min(pow(2, $attempt - 1), 16);
        sleep($delay);

        // Retry delivery logic here
        $this->logger->channel('webhooks')->$this->logger->info('Webhook retry', [
            'delivery_id' => $deliveryId,
            'attempt' => $attempt,
            'delay' => $delay,
            'correlation_id' => $correlationId,
        ]);

        return ['status' => 'retrying', 'delivery_id' => $deliveryId, 'attempt' => $attempt];
    }

    /**
     * Получает историю доставок
     */
    public function getDeliveryHistory(string $webhookId, int $limit = 50): array
    {
        $deliveries = $this->db->table('webhook_deliveries')
            ->where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();

        return [
            'webhook_id' => $webhookId,
            'deliveries' => $deliveries,
        ];
    }

    /**
     * Отключает вебхук
     */
    public function disableWebhook(string $webhookId, ?string $correlationId = null): void
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->db->table('webhook_endpoints')
            ->where('id', $webhookId)
            ->update([
                'status' => 'disabled',
                'updated_at' => CarbonImmutable::now(),
            ]);

        $this->logger->channel('webhooks')->$this->logger->info('Webhook disabled', [
            'webhook_id' => $webhookId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получает поддерживаемые события
     */
    public function getSupportedEvents(): array
    {
        return [
            'order.created' => 'When order is created',
            'order.updated' => 'When order is updated',
            'order.cancelled' => 'When order is cancelled',
            'payment.initiated' => 'When payment is initiated',
            'payment.processed' => 'When payment is processed',
            'payment.failed' => 'When payment failed',
            'payment.refunded' => 'When payment refunded',
            'user.registered' => 'When user registered',
            'user.profile_updated' => 'When user profile updated',
            'appointment.scheduled' => 'When appointment scheduled',
            'appointment.completed' => 'When appointment completed',
            'appointment.cancelled' => 'When appointment cancelled',
        ];
    }

    /**
     * Генерирует отчёт
     */
    public function generateReport(): string
    {
        $events = $this->getSupportedEvents();

        $report = "\n╔════════════════════════════════════════════════════════════╗\n";
        $report .= "║            WEBHOOK MANAGEMENT REPORT                       ║\n";
        $report .= '║            '.CarbonImmutable::now()->toDateTimeString()."                    ║\n";
        $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

        $report .= '  SUPPORTED EVENTS: '.count($events)."\n\n";

        $categories = [
            'Order' => ['order.created', 'order.updated', 'order.cancelled'],
            'Payment' => ['payment.initiated', 'payment.processed', 'payment.failed', 'payment.refunded'],
            'User' => ['user.registered', 'user.profile_updated'],
            'Appointment' => ['appointment.scheduled', 'appointment.completed', 'appointment.cancelled'],
        ];

        foreach ($categories as $category => $categoryEvents) {
            $report .= sprintf("  %s Events:\n", $category);
            foreach ($categoryEvents as $event) {
                $report .= sprintf("    - %s\n", $event);
            }
            $report .= "\n";
        }

        $report .= "\n";

        return $report;
    }

    /**
     * Доставляет вебхук
     */
    private function deliverWebhook(array $webhook, array $event, string $correlationId): array
    {
        $deliveryId = 'del_'.uniqid();
        $payload = json_encode($event);
        $signature = hash_hmac('sha256', $payload, $webhook['secret']);

        try {
            $response = $this->http->timeout(10)
                ->withHeaders(array_merge($webhook['headers'], [
                    'X-Webhook-ID' => $webhook['id'],
                    'X-Delivery-ID' => $deliveryId,
                    'X-Signature' => $signature,
                    'X-Correlation-ID' => $event['correlation_id'],
                    'Content-Type' => 'application/json',
                ]))
                ->post($webhook['url'], $event['payload']);

            $status = $response->successful() ? 'success' : 'failed';

            $this->logger->channel('webhooks')->$this->logger->info('Webhook delivery', [
                'webhook_id' => $webhook['id'],
                'delivery_id' => $deliveryId,
                'status' => $status,
                'response_code' => $response->status(),
            ]);

            return [
                'delivery_id' => $deliveryId,
                'webhook_id' => $webhook['id'],
                'status' => $status,
                'response_code' => $response->status(),
                'timestamp' => CarbonImmutable::now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            $this->logger->channel('webhooks')->warning('Webhook delivery failed', [
                'webhook_id' => $webhook['id'],
                'error' => $e->getMessage(),
            ]);

            return [
                'delivery_id' => $deliveryId,
                'webhook_id' => $webhook['id'],
                'status' => 'failed',
                'error' => $e->getMessage(),
                'timestamp' => CarbonImmutable::now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Получает вебхуки для события
     */
    private function getWebhooksForEvent(string $eventName, int $tenantId): array
    {
        return $this->db->table('webhook_endpoints')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereJsonContains('events', $eventName)
            ->get()
            ->toArray();
    }
}
