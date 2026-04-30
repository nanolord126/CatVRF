<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
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
            subject: "✅ Заказ №{$this->orderId} подтверждён",
        );
    }

    public function content(): Content
    {
        $content = "
            <h1>✅ Заказ подтверждён!</h1>
            <p>Номер заказа: <strong>{$this->orderId}</strong></p>
            <p>Товары зарезервированы.</p>
            <p>Вы можете отслеживать статус заказа в приложении.</p>
        ";

        return new Content(
            html: $content,
        );
    }
}
