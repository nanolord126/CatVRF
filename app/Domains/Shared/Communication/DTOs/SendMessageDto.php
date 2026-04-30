<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO for sending a message through any channel.
 */
final readonly class SendMessageDto
{
    public function __construct(
        public int $tenantId,
        public int $senderId,
        public ?int $recipientId,
        public string $recipientType,   // user | business_group | broadcast
        public string $channelType,     // email | sms | push | in_app | telegram
        public string $body,
        public ?string $subject,
        public string $correlationId,
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->get('tenant_id'),
            senderId: (int) $request->user()?->id,
            recipientId: $request->input('recipient_id') !== null ? (int) $request->input('recipient_id') : null,
            recipientType: (string) $request->input('recipient_type', 'user'),
            channelType: (string) $request->input('channel_type', 'in_app'),
            body: (string) $request->input('body'),
            subject: $request->input('subject'),
            correlationId: (string) ($request->header('X-Correlation-ID') ?: Str::uuid()),
            metadata: (array) $request->input('metadata', []),
            idempotencyKey: $request->input('idempotency_key'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'sender_id'      => $this->senderId,
            'recipient_id'   => $this->recipientId,
            'recipient_type' => $this->recipientType,
            'channel_type'   => $this->channelType,
            'body'           => $this->body,
            'subject'        => $this->subject,
            'correlation_id' => $this->correlationId,
            'metadata'       => $this->metadata,
            'status'         => 'pending',
        ];
    }

    /**
     * Базовая валидация данных DTO.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (property_exists($this, 'correlationId') && $this->correlationId === '') {
            throw new \InvalidArgumentException('correlationId must not be empty');
        }
    }
}
