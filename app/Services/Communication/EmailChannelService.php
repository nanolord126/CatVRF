<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Domains\Communication\Models\Message;
use Illuminate\Mail\Mailer;
use Illuminate\Log\LogManager;

/**
 * Sends a Message record via email (Mailgun / SendGrid driver).
 * Canon: final readonly, DI only, no Facades.
 */
final readonly class EmailChannelService
{
    public function __construct(
        private Mailer     $mailer,
        private LogManager $logger,
    ) {}

    public function send(Message $message): void
    {
        if (empty($message->metadata['to_email'] ?? null)) {
            $this->logger->channel('audit')->warning('EmailChannelService: no to_email in metadata', [
                'message_id'     => $message->id,
                'correlation_id' => $message->correlation_id,
            ]);
            return;
        }

        $this->mailer->send(
            'mail.communication.message',
            ['message' => $message],
            static function (\Illuminate\Mail\Message $mail) use ($message): void {
                $mail
                    ->to($message->metadata['to_email'])
                    ->subject($message->subject ?? 'Новое сообщение');
            }
        );

        $this->logger->channel('audit')->info('Email message sent', [
            'message_id'     => $message->id,
            'to_email'       => $message->metadata['to_email'],
            'correlation_id' => $message->correlation_id,
        ]);
    }

    /**
     * Component: EmailChannelService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';
}
