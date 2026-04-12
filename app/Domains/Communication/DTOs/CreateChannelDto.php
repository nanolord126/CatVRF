<?php

declare(strict_types=1);

namespace App\Domains\Communication\DTOs;

use Illuminate\Http\Request;

/**
 * DTO for creating/updating a communication channel.
 */
final readonly class CreateChannelDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $type,            // email | sms | push | telegram | in_app
        public array $config,
        public string $status,
        public string $correlationId,
        public array $tags = [],
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->get('tenant_id'),
            name: (string) $request->input('name'),
            type: (string) $request->input('type'),
            config: (array) $request->input('config', []),
            status: (string) $request->input('status', 'active'),
            correlationId: (string) ($request->header('X-Correlation-ID') ?: \Illuminate\Support\Str::uuid()),
            tags: (array) $request->input('tags', []),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'name'           => $this->name,
            'type'           => $this->type,
            'config'         => $this->config,
            'status'         => $this->status,
            'correlation_id' => $this->correlationId,
            'tags'           => $this->tags,
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
