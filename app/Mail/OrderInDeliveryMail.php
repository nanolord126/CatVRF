<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderInDeliveryMail extends Mailable
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
            subject: "🚚 Курьер в пути! Заказ №{$this->orderId}",
        );
    }

    public function content(): Content
    {
        $eta = $this->data['eta'] ?? '30';

        $content = "
            <h1>🚚 Курьер в пути!</h1>
            <p>Номер заказа: <strong>{$this->orderId}</strong></p>
            <p>Примерное время доставки: <strong>{$eta} минут</strong></p>
            <p>Пожалуйста, будьте готовы к приему заказа.</p>
        ";

        return new Content(
            html: $content,
        );
    }
}
