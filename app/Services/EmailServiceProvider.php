<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Email Service - отправляет письма через Mailgun/SendGrid/SES
 * 
 * Интегрируется с Laravel Mail facade
 */
class EmailService
{
    /**
     * Отправить email
     */
    public function send(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        array $attachments = [],
        ?string $correlationId = null,
        ?int $tenantId = null
    ): bool {
        try {
            // Можно расширить для разных провайдеров
            $this->mail->send($template, $data, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)->subject($subject);
                
                // Добавить attachments
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], $attachment['options'] ?? []);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            $this->log->channel('audit')->info('Email sent', [
                'to' => $to,
                'subject' => $subject,
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->log->error('Failed to send email', [
                'to' => $to,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Отправить уведомление (для NotificationService)
     */
    public function sendNotification($notification, $user, string $correlationId): void
    {
        if (method_exists($notification, 'toMail')) {
            $data = $notification->toMail();
            $this->send(
                to: $user->email,
                subject: $data['subject'] ?? 'Notification',
                template: $data['template'] ?? 'emails.generic',
                data: $data['data'] ?? [],
                correlationId: $correlationId,
                tenantId: $user->tenant_id ?? null,
            );
        }
    }
}
