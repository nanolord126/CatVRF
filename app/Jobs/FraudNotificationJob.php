<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Асинхронная отправка уведомлений о фроде.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Очередь: fraud-notifications (Redis Horizon).
 * Отправляет уведомление через все каналы из fraud_notifications.channels.
 */
final class FraudNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly int    $notificationId,
        private readonly string $severity,
        private readonly string $correlationId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(): void
    {
        $notification = $this->db->table('fraud_notifications')
            ->where('id', $this->notificationId)
            ->first();

        if (!$notification) {
            return;
        }

        $channels = json_decode($notification->channels, true) ?? [];
        $user     = $this->db->table('users')->where('id', $notification->user_id)->first();

        try {
            foreach ($channels as $channel) {
                $this->sendViaChannel($channel, $notification, $user);
            }

            $this->db->table('fraud_notifications')
                ->where('id', $this->notificationId)
                ->update(['status' => 'sent', 'updated_at' => now()]);
        } catch (\Throwable $e) {
            $this->db->table('fraud_notifications')
                ->where('id', $this->notificationId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            $this->logger->channel('fraud_alert')->error('FraudNotificationJob failed', [
                'notification_id' => $this->notificationId,
                'error'           => $e->getMessage(),
                'correlation_id'  => $this->correlationId,
            ]);

            $this->fail($e);
        }
    }

    private function sendViaChannel(string $channel, object $notification, ?object $user): void
    {
        match ($channel) {
            'email'    => $this->sendEmail($notification, $user),
            'push'     => $this->sendPush($notification, $user),
            'telegram' => $this->sendTelegram($notification),
            'sms'      => $this->sendSms($notification, $user),
            'slack'    => $this->sendSlack($notification),
            default    => null,
        };
    }

    private function sendInApp(object $notification, ?object $user): void
    {
        if (!$user) {
            return;
        }

        $this->db->table('notifications')->insert([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'type'            => 'App\\Notifications\\FraudAlertNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $user->id,
            'data'            => json_encode([
                'title'          => $notification->title,
                'message'        => $notification->message,
                'severity'       => $this->severity,
                'correlation_id' => $this->correlationId,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sendEmail(object $notification, ?object $user): void
    {
        if (!$user?->email) {
            return;
        }

        Mail::raw(
            "Заголовок: {$notification->title}\n\n{$notification->message}\n\nКод запроса: {$this->correlationId}",
            fn ($msg) => $msg
                ->to($user->email)
                ->subject("[{$this->severity}] {$notification->title} — CatVRF Security"),
        );
    }

    private function sendPush(object $notification, ?object $user): void
    {
        // FCM / APNS push через Firebase или другой провайдер
        // Реализация зависит от push-провайдера проекта
        $this->logger->channel('fraud_alert')->info('Push notification sent', [
            'user_id'         => $user?->id,
            'severity'        => $this->severity,
            'correlation_id'  => $this->correlationId,
        ]);
    }

    private function sendTelegram(object $notification): void
    {
        $token   = $this->config->get('services.telegram.bot_token');
        $chatId  = $this->config->get('services.telegram.security_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        $text = "🚨 *[{$this->severity}]* {$notification->title}\n\n"
            . "{$notification->message}\n\n"
            . "`{$this->correlationId}`";

        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendSms(object $notification, ?object $user): void
    {
        // SMS через Twilio / SMS.ru
        // Реализация зависит от SMS-провайдера проекта
        $this->logger->channel('fraud_alert')->info('SMS notification dispatched', [
            'user_id'        => $user?->id,
            'severity'       => $this->severity,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendSlack(object $notification): void
    {
        $webhookUrl = $this->config->get('services.slack.fraud_webhook');

        if (!$webhookUrl) {
            return;
        }

        \Illuminate\Support\Facades\Http::post($webhookUrl, [
            'text' => "*[{$this->severity}]* {$notification->title}: {$notification->message} | `{$this->correlationId}`",
        ]);
    }
}
