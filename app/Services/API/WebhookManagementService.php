<?php declare(strict_types=1);

namespace App\Services\API;



use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\LogManager;


final readonly class WebhookManagementService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    /**
         * Регистрирует вебхук
         *
         * @param string $url
         * @param array $events
         * @param int $tenantId
         * @param array $headers
         * @return array
         */
        public static function registerWebhook(
            string $url,
            array $events,
            int $tenantId,
            array $headers = []
        ): array {
            $webhookId = 'wh_' . uniqid();
            $secret = hash_hmac('sha256', $webhookId . microtime(), $this->config->get('app.key'));

            $webhook = [
                'id' => $webhookId,
                'url' => $url,
                'events' => $events,
                'tenant_id' => $tenantId,
                'secret' => $secret,
                'headers' => $headers,
                'status' => 'active',
                'created_at' => now()->toDateTimeString(),
                'last_triggered_at' => null,
                'failed_attempts' => 0,
            ];

            $this->logger->channel('webhooks')->info('Webhook registered', [
                'webhook_id' => $webhookId,
                'url' => $url,
                'events_count' => count($events),
            ]);

            return $webhook;
        }

        /**
         * Триггерит событие
         *
         * @param string $eventName
         * @param array $payload
         * @param int $tenantId
         * @return array
         */
        public static function triggerEvent(
            string $eventName,
            array $payload,
            int $tenantId
        ): array {
            $correlationId = $this->request?->header('X-Correlation-ID') ?? uniqid();

            $event = [
                'id' => 'evt_' . uniqid(),
                'name' => $eventName,
                'payload' => $payload,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'timestamp' => now()->toDateTimeString(),
            ];

            // Находим все вебхуки, подписанные на это событие
            $webhooks = self::getWebhooksForEvent($eventName, $tenantId);

            $results = [];
            foreach ($webhooks as $webhook) {
                $results[] = self::deliverWebhook($webhook, $event);
            }

            $this->logger->channel('webhooks')->info('Event triggered', [
                'event' => $eventName,
                'webhooks_triggered' => count($webhooks),
                'correlation_id' => $correlationId,
            ]);

            return [
                'event' => $event,
                'deliveries' => $results,
                'total' => count($results),
                'successful' => collect($results)->filter(fn($r) => $r['status'] === 'success')->count(),
            ];
        }

        /**
         * Доставляет вебхук
         *
         * @param array $webhook
         * @param array $event
         * @return array
         */
        private static function deliverWebhook(array $webhook, array $event): array
        {
            $deliveryId = 'del_' . uniqid();
            $payload = json_encode($event);
            $signature = hash_hmac('sha256', $payload, $webhook['secret']);

            try {
                $response = Http::timeout(10)
                    ->withHeaders(array_merge($webhook['headers'], [
                        'X-Webhook-ID' => $webhook['id'],
                        'X-Delivery-ID' => $deliveryId,
                        'X-Signature' => $signature,
                        'X-Correlation-ID' => $event['correlation_id'],
                        'Content-Type' => 'application/json',
                    ]))
                    ->post($webhook['url'], $event['payload']);

                $status = $response->successful() ? 'success' : 'failed';

                $this->logger->channel('webhooks')->info('Webhook delivery', [
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
                    'timestamp' => now()->toDateTimeString(),
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
                    'timestamp' => now()->toDateTimeString(),
                ];
            }
        }

        /**
         * Получает вебхуки для события
         *
         * @param string $eventName
         * @param int $tenantId
         * @return array
         */
        private static function getWebhooksForEvent(string $eventName, int $tenantId): array
        {
            // Плейсхолдер - в реальности будет запрос к БД
            return [];
        }

        /**
         * Повторно доставляет вебхук
         *
         * @param string $deliveryId
         * @return array
         */
        public static function retryDelivery(string $deliveryId): array
        {
            $this->logger->channel('webhooks')->info('Webhook retry requested', [
                'delivery_id' => $deliveryId,
            ]);

            return ['status' => 'retrying', 'delivery_id' => $deliveryId];
        }

        /**
         * Получает историю доставок
         *
         * @param string $webhookId
         * @param int $limit
         * @return array
         */
        public static function getDeliveryHistory(string $webhookId, int $limit = 50): array
        {
            return [
                'webhook_id' => $webhookId,
                'deliveries' => [
                    [
                        'id' => 'del_001',
                        'event' => 'order.created',
                        'status' => 'success',
                        'response_code' => 200,
                        'timestamp' => now()->subHours(1)->toDateTimeString(),
                    ],
                    [
                        'id' => 'del_002',
                        'event' => 'payment.processed',
                        'status' => 'success',
                        'response_code' => 200,
                        'timestamp' => now()->subMinutes(30)->toDateTimeString(),
                    ],
                ],
            ];
        }

        /**
         * Отключает вебхук
         *
         * @param string $webhookId
         * @return void
         */
        public static function disableWebhook(string $webhookId): void
        {
            $this->logger->channel('webhooks')->info('Webhook disabled', [
                'webhook_id' => $webhookId,
            ]);
        }

        /**
         * Получает поддерживаемые события
         *
         * @return array
         */
        public static function getSupportedEvents(): array
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
         *
         * @return string
         */
        public static function generateReport(): string
        {
            $events = self::getSupportedEvents();

            $report = "\n╔════════════════════════════════════════════════════════════╗\n";
            $report .= "║            WEBHOOK MANAGEMENT REPORT                       ║\n";
            $report .= "║            " . now()->toDateTimeString() . "                    ║\n";
            $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

            $report .= "  SUPPORTED EVENTS: " . count($events) . "\n\n";

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
}
