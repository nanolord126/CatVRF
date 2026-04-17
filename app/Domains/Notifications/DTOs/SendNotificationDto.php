<?php declare(strict_types=1);

namespace App\Domains\Notifications\DTOs;

final readonly class SendNotificationDto
{
    public function __construct(
        public int $tenantId,
        public ?int $userId,
        public string $type,
        public string $channel,
        public string $title,
        public string $body,
        public ?array $data = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'] ?? null,
            type: $data['type'],
            channel: $data['channel'],
            title: $data['title'],
            body: $data['body'],
            data: $data['data'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'channel' => $this->channel,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }
}
