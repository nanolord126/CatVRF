<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Top Item DTO
 *
 * Represents a single top item with its metrics.
 */
final readonly class TopItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float|int $value,
        public readonly ?string $imageUrl = null,
        public readonly ?string $url = null,
        public readonly array $metadata = [],
    ) {}

    public static function create(
        int $id,
        string $name,
        float|int $value,
        ?string $imageUrl = null,
        ?string $url = null,
        array $metadata = [],
    ): self {
        return new self(
            $id,
            $name,
            $value,
            $imageUrl,
            $url,
            $metadata,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->value,
            'image_url' => $this->imageUrl,
            'url' => $this->url,
            'metadata' => $this->metadata,
        ];
    }
}
