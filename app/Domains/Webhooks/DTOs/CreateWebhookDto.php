<?php declare(strict_types=1);

namespace App\Domains\Webhooks\DTOs;

final readonly class CreateWebhookDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $url,
        public array $events,
        public ?string $secret = null,
        public bool $isActive = true,
        public int $retryCount = 3,
        public int $timeout = 30,
        public ?array $headers = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            url: $data['url'],
            events: $data['events'],
            secret: $data['secret'] ?? null,
            isActive: $data['is_active'] ?? true,
            retryCount: $data['retry_count'] ?? 3,
            timeout: $data['timeout'] ?? 30,
            headers: $data['headers'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'url' => $this->url,
            'events' => $this->events,
            'secret' => $this->secret,
            'is_active' => $this->isActive,
            'retry_count' => $this->retryCount,
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ];
    }
}
