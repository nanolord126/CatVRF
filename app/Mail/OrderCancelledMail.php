<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $recipientType,
        public array $data = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "❌ Заказ №{$this->orderId} отменён",
        );
    }

    public function content(): Content
    {
        $reason = $this->data['reason'] ?? 'Не указана';

        $content = "
            <h1>❌ Заказ отменён</h1>
            <p>Номер заказа: <strong>{$this->orderId}</strong></p>
            <p>Причина отмены: <strong>{$reason}</strong></p>
            <p>Если у вас есть вопросы, пожалуйста, свяжитесь с поддержкой.</p>
        ";

        return new Content(
            html: $content,
        );
    }
}
