<?php declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Quota Exceeded Mail
 *
 * Production 2026 CANON - Quota Alert System
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class QuotaExceededMail extends Mailable
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
                '[CatVRF] ПРЕВЫШЕНА квота %s - запросы блокируются',
                $this->resourceType
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quota-exceeded',
            with: [
                'tenantId' => $this->tenantId,
                'resourceType' => $this->resourceType,
                'quotaData' => $this->quotaData,
            ],
        );
    }
}
