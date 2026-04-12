<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

use Illuminate\Support\Str;

/**
 * DTO for sending a chat message in a room.
 */
final readonly class SendChatMessageDto
{
    public function __construct(
        public int $tenantId,
        public int $roomId,
        public int $senderId,
        public string $body,
        public string $type,            // text | image | file | system
        public string $correlationId,
        public string|null $attachmentUrl = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'room_id'        => $this->roomId,
            'sender_id'      => $this->senderId,
            'body'           => $this->body,
            'type'           => $this->type,
            'attachment_url' => $this->attachmentUrl,
            'correlation_id' => $this->correlationId,
            'metadata'       => $this->metadata,
            'is_read'        => false,
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

    /**
     * Строковое представление DTO для debug-логов.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s(%s)',
            (new \ReflectionClass($this))->getShortName(),
            json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }
}
