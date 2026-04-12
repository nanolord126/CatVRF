<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для создания сегмента CRM-клиентов.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateCrmSegmentDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $description,
        public ?string $vertical,
        public bool $isDynamic,
        public array $rules,
        public string $correlationId,
        public array $tags = [],
    ) {}

    public static function fromRequest(Request $request, string $correlationId): self
    {
        return new self(
            tenantId: (int) ($request->user()->tenant_id ?? 1),
            name: (string) $request->input('name', ''),
            description: $request->input('description'),
            vertical: $request->input('vertical'),
            isDynamic: (bool) $request->input('is_dynamic', true),
            rules: (array) $request->input('rules', []),
            correlationId: $correlationId,
            tags: (array) $request->input('tags', []),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'vertical' => $this->vertical,
            'is_dynamic' => $this->isDynamic,
            'rules' => $this->rules,
            'correlation_id' => $this->correlationId,
            'tags' => $this->tags,
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
