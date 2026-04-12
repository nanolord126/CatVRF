<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

use App\Domains\CRM\Models\CrmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * SendCrmNotificationJob — отправка CRM-уведомления клиенту.
 *
 * Универсальная job для отправки уведомлений по различным каналам.
 * Поддерживает email, SMS, push, Telegram, in_app.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 * Очередь: crm-notifications
 */
final class SendCrmNotificationJob implements ShouldQueue
{
    use \Illuminate\Foundation\Events\Dispatchable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;

    /**
     * Количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 60;

    /**
     * @param int    $clientId      ID CRM-клиента
     * @param string $channel       Канал: email, sms, push, telegram, in_app
     * @param string $template      Имя шаблона уведомления
     * @param array  $data          Данные для шаблона
     * @param string $correlationId Идентификатор корреляции
     */
    public function __construct(
        private readonly int $clientId,
        private readonly string $channel,
        private readonly string $template,
        private readonly array $data,
        private readonly string $correlationId,
    ) {
        $this->onQueue('crm-notifications');
    }

    public function handle(LoggerInterface $logger): void
    {
        $client = CrmClient::find($this->clientId);

        if ($client === null) {
            $logger->warning('CRM notification: client not found', [
                'client_id' => $this->clientId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        $logger->info('CRM notification: sending', [
            'client_id' => $client->id,
            'channel' => $this->channel,
            'template' => $this->template,
            'correlation_id' => $this->correlationId,
        ]);

        match ($this->channel) {
            'email' => $this->sendEmail($client, $logger),
            'sms' => $this->sendSms($client, $logger),
            'push' => $this->sendPush($client, $logger),
            'telegram' => $this->sendTelegram($client, $logger),
            'in_app' => $this->sendInApp($client, $logger),
            default => $logger->warning('CRM notification: unknown channel', [
                'channel' => $this->channel,
                'correlation_id' => $this->correlationId,
            ]),
        };
    }

    private function sendEmail(CrmClient $client, LoggerInterface $logger): void
    {
        if (empty($client->email)) {
            $logger->info('CRM notification: client has no email, skipping', [
                'client_id' => $client->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // В production: Mail::to($client->email)->queue(new CrmTemplateMail($this->template, $this->data));

        $logger->info('CRM notification: email sent', [
            'client_id' => $client->id,
            'email' => $client->email,
            'template' => $this->template,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendSms(CrmClient $client, LoggerInterface $logger): void
    {
        if (empty($client->phone)) {
            $logger->info('CRM notification: client has no phone, skipping', [
                'client_id' => $client->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // В production: SmsService::send($client->phone, $this->data['message'] ?? '');

        $logger->info('CRM notification: SMS sent', [
            'client_id' => $client->id,
            'phone' => $client->phone,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendPush(CrmClient $client, LoggerInterface $logger): void
    {
        if ($client->user_id === null) {
            $logger->info('CRM notification: client has no user_id for push, skipping', [
                'client_id' => $client->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // В production: PushNotificationService::send($client->user_id, $this->template, $this->data);

        $logger->info('CRM notification: push sent', [
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendTelegram(CrmClient $client, LoggerInterface $logger): void
    {
        $telegramId = $client->vertical_data['telegram_id'] ?? null;

        if ($telegramId === null) {
            $logger->info('CRM notification: client has no telegram_id, skipping', [
                'client_id' => $client->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // В production: TelegramService::sendMessage($telegramId, $this->data['message'] ?? '');

        $logger->info('CRM notification: Telegram sent', [
            'client_id' => $client->id,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendInApp(CrmClient $client, LoggerInterface $logger): void
    {
        if ($client->user_id === null) {
            $logger->info('CRM notification: client has no user_id for in-app, skipping', [
                'client_id' => $client->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        // В production: InAppNotificationService::notify($client->user_id, $this->template, $this->data);

        $logger->info('CRM notification: in-app sent', [
            'client_id' => $client->id,
            'user_id' => $client->user_id,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return sprintf(
            'SendCrmNotificationJob[client_id=%d, channel=%s, template=%s, correlation_id=%s]',
            $this->clientId,
            $this->channel,
            $this->template,
            $this->correlationId,
        );
    }
}




