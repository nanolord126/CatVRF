<?php declare(strict_types=1);

namespace App\Services;


use Illuminate\Support\Facades\Mail;
use Illuminate\Log\LogManager;

/**
 * Email Service - отправляет письма через Mailgun/SendGrid/SES
 *
 * Интегрируется с Laravel Mail facade
 */
abstract class EmailService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

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
            Mail::send($template, $data, function ($message) use ($to, $subject, $attachments) {
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

            $this->logger->channel('audit')->info('Email sent', [
                'to' => $to,
                'subject' => $subject,
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->error('Failed to send email', [
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
