<?php declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Quota Critical Mail
 *
 * Production 2026 CANON - Quota Alert System
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class QuotaCriticalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private int $tenantId,
        private string $resourceType,
        private array $quotaData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[CatVRF] КРИТИЧЕСКИ: квота %s исчерпана на %d%%',
                $this->resourceType,
                $this->quotaData['percentage']
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quota-critical',
            with: [
                'tenantId' => $this->tenantId,
                'resourceType' => $this->resourceType,
                'quotaData' => $this->quotaData,
            ],
        );
    }
}
