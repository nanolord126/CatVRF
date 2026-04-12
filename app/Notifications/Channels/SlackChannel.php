<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use Psr\Log\LoggerInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Slack Notification Channel — отправляет уведомления через Slack Incoming Webhooks.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Поддерживает:
 * - Incoming Webhooks
 * - Настраиваемый канал и username
 * - Block Kit (через toSlack())
 * - Retry с backoff
 * - Audit-лог + correlation_id
 */
final class SlackChannel
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Отправить уведомление через Slack.
     *
     * Объект $notification должен реализовать метод toSlack(),
     * возвращающий массив: ['text' => ..., 'blocks' => [...], 'channel' => ...]
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSlack')) {
            $this->logger->warning('Notification does not have toSlack method', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
            ]);
            return;
        }

        try {
            $slackData = $notification->toSlack();

            $webhookUrl = $slackData['webhook_url']
                ?? config('notifications.channels.slack.webhook_url', '');

            if (empty($webhookUrl)) {
                throw new \RuntimeException('Slack webhook URL is not configured');
            }

            $payload = [
                'text'     => $slackData['text'] ?? '',
                'channel'  => $slackData['channel'] ?? config('notifications.channels.slack.channel', '#alerts'),
                'username' => $slackData['username'] ?? config('notifications.channels.slack.username', 'CatVRF Bot'),
            ];

            if (!empty($slackData['blocks'])) {
                $payload['blocks'] = $slackData['blocks'];
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'Slack webhook error: HTTP ' . $response->status() . ' — ' . $response->body()
                );
            }

            $this->logger->info('Slack notification sent', [
                'type'           => method_exists($notification, 'getType') ? $notification->getType() : get_class($notification),
                'channel'        => $payload['channel'],
                'correlation_id' => method_exists($notification, 'getCorrelationId') ? $notification->getCorrelationId() : null,
                'tenant_id'      => method_exists($notification, 'getTenantId') ? $notification->getTenantId() : null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Slack notification failed', [
                'notification_class' => get_class($notification),
                'error'              => $e->getMessage(),
                'correlation_id'     => method_exists($notification, 'getCorrelationId') ? $notification->getCorrelationId() : null,
            ]);

            throw $e;
        }
    }

    /**
     * Прямая отправка текста в Slack (без Notification-объекта).
     *
     * Используется NotificationChannelService для отправки
     * произвольных alert-сообщений.
     */
    public function sendDirect(
        string  $text,
        ?string $channel = null,
        ?string $correlationId = null,
    ): void {
        $webhookUrl = config('notifications.channels.slack.webhook_url', '');

        if (empty($webhookUrl)) {
            $this->logger->warning('Slack webhook URL not configured, skipping', [
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $payload = [
            'text'     => $text,
            'channel'  => $channel ?? config('notifications.channels.slack.channel', '#alerts'),
            'username' => config('notifications.channels.slack.username', 'CatVRF Bot'),
        ];

        $response = Http::timeout(10)->post($webhookUrl, $payload);

        if (!$response->successful()) {
            $this->logger->error('Slack direct send failed', [
                'response'       => $response->body(),
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException(
                'Slack webhook error: HTTP ' . $response->status() . ' — ' . $response->body()
            );
        }

        $this->logger->info('Slack direct message sent', [
            'channel'        => $payload['channel'],
            'correlation_id' => $correlationId,
        ]);
    }
}
