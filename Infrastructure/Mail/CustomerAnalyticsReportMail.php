<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for customer analytics report
 */
final class CustomerAnalyticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $analytics,
        public readonly array $topCustomers,
        public readonly int $days
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Customer Analytics Report - Last {$this->days} Days",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.crm.customer-analytics-report',
            with: [
                'analytics' => $this->analytics,
                'topCustomers' => $this->topCustomers,
                'days' => $this->days,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
