<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $recipientType,
        public array $data = []
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->recipientType === 'buyer'
            ? "✅ Ваш заказ №{$this->orderId} оформлен"
            : "🛒 Новый заказ №{$this->orderId}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $trackingUrl = url("/orders/{$this->orderId}");
        $amount = $this->data['amount'] ?? '0';

        if ($this->recipientType === 'buyer') {
            $content = "
                <h1>✅ Ваш заказ оформлен!</h1>
                <p>Номер заказа: <strong>{$this->orderId}</strong></p>
                <p>Сумма: <strong>{$amount} ₽</strong></p>
                <p>Вы можете отслеживать статус заказа по ссылке ниже:</p>
                <p><a href='{$trackingUrl}'>Отследить заказ</a></p>
                <p>Спасибо за ваш заказ в CatVRF!</p>
            ";
        } else {
            $content = "
                <h1>🛒 Новый заказ в супермаркете!</h1>
                <p>Номер заказа: <strong>{$this->orderId}</strong></p>
                <p>Сумма: <strong>{$amount} ₽</strong></p>
                <p>Пожалуйста, проверьте панель управления для деталей заказа.</p>
            ";
        }

        return new Content(
            html: $content,
        );
    }
}
