<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\EmailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Email Notification Channel - отправляет уведомления через Email
 *
 * Поддерживает:
 * - Mailgun
 * - SendGrid
 * - AWS SES
 * - Собственный SMTP
 *
 * С помощью Laravel Mail facade
 */
class EmailChannel
{
    /**
     * Инстанс EmailService
     */
    protected EmailService $emailService;

    /**
     * Конструктор
     */
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Отправить уведомление через email
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Проверить, что объект имеет метод toMail
        if (!method_exists($notification, 'toMail')) {
            Log::warning('Notification does not have toMail method', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
            ]);
            return;
        }

        try {
            // Получить email address
            $email = $this->getEmail($notifiable);
            if (!$email) {
                throw new \Exception("No email address found for notifiable: {$notifiable->id}");
            }

            // Получить данные письма
            $mailData = $notification->toMail();

            // Отправить через EmailService
            $this->emailService->send(
                to: $email,
                subject: $mailData['subject'] ?? 'Notification',
                template: $mailData['template'] ?? 'emails.generic',
                data: $mailData['data'] ?? [],
                attachments: $mailData['attachments'] ?? [],
                correlationId: $notification->getCorrelationId(),
                tenantId: $notification->getTenantId(),
            );

            Log::channel('audit')->info('Email notification sent', [
                'type' => $notification->getType(),
                'email' => $email,
                'correlation_id' => $notification->getCorrelationId(),
                'tenant_id' => $notification->getTenantId(),
            ]);

        } catch (\Exception $e) {
            Log::channel('notifications')->error('Failed to send email notification', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить email адрес уведомляемого объекта
     */
    protected function getEmail(object $notifiable): ?string
    {
        // Проверить разные методы получения email
        if (isset($notifiable->email)) {
            return $notifiable->email;
        }

        if (method_exists($notifiable, 'getEmailForNotifications')) {
            return $notifiable->getEmailForNotifications();
        }

        if (method_exists($notifiable, 'routeNotificationForEmail')) {
            return $notifiable->routeNotificationForEmail();
        }

        return null;
    }
}
