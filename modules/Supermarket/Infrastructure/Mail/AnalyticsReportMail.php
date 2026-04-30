<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnalyticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public array $analytics,
        public int $periodDays = 30
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📊 Аналитический отчет супермаркета за {$this->periodDays} дней",
        );
    }

    public function content(): Content
    {
        $analytics = $this->analytics;
        
        $content = "
            <h1>📊 Аналитический отчет супермаркета</h1>
            <p><strong>Период:</strong> последние {$this->periodDays} дней</p>
            <p><strong>Tenant ID:</strong> {$this->tenantId}</p>
            " . ($this->businessGroupId ? "<p><strong>Business Group ID:</strong> {$this->businessGroupId}</p>" : "") . "
            
            <h2>👥 Клиенты</h2>
            <ul>
                <li>Всего клиентов: <strong>{$analytics['total_customers']}</strong></li>
                <li>Новых клиентов: <strong>{$analytics['new_customers']}</strong></li>
                <li>Активных клиентов: <strong>{$analytics['active_customers']}</strong></li>
                <li>Спящих клиентов: <strong>{$analytics['sleeping_customers']}</strong></li>
                <li>VIP клиентов: <strong>{$analytics['vip_customers']}</strong></li>
            </ul>
            
            <h2>💰 Выручка</h2>
            <ul>
                <li>Общая выручка: <strong>{$analytics['total_revenue']} ₽</strong></li>
            </ul>
            
            <h2>📦 Подписки и возвраты</h2>
            <ul>
                <li>Пользователей с подписками: <strong>{$analytics['subscription_users']}</strong></li>
                <li>Количество возвратов: <strong>{$analytics['returns_count']}</strong></li>
            </ul>
            
            <p><em>Отчет сгенерирован: " . now()->toIso8601String() . "</em></p>
        ";

        return new Content(
            html: $content,
        );
    }
}
