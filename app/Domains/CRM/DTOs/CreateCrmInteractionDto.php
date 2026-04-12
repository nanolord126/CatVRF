<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для записи взаимодействия с CRM-клиентом.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateCrmInteractionDto
{
    public function __construct(
        public int $tenantId,
        public int $crmClientId,
        public ?int $userId,
        public string $type,
        public ?string $channel,
        public ?string $direction,
        public ?string $subject,
        public string $content,
        public array $metadata,
        public string $correlationId,
    ) {}

    public static function fromRequest(Request $request, int $crmClientId, string $correlationId): self
    {
        return new self(
            tenantId: (int) ($request->user()->tenant_id ?? 1),
            crmClientId: $crmClientId,
            userId: $request->user()?->id,
            type: (string) $request->input('type', 'note'),
            channel: $request->input('channel'),
            direction: $request->input('direction'),
            subject: $request->input('subject'),
            content: (string) $request->input('content', ''),
            metadata: (array) $request->input('metadata', []),
            correlationId: $correlationId,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'crm_client_id' => $this->crmClientId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'type' => $this->type,
            'channel' => $this->channel,
            'direction' => $this->direction,
            'subject' => $this->subject,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'interacted_at' => now(),
        ];
    }
}
