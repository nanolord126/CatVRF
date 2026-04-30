<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderDeliveredMail extends Mailable
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
            subject: "🎉 Ваш заказ №{$this->orderId} доставлен",
        );
    }

    public function content(): Content
    {
        $content = "
            <h1>🎉 Заказ доставлен!</h1>
            <p>Номер заказа: <strong>{$this->orderId}</strong></p>
            <p>Спасибо за покупку в CatVRF!</p>
            <p>Пожалуйста, оцените ваш заказ в приложении.</p>
        ";

        return new Content(
            html: $content,
        );
    }
}
