<?php

declare(strict_types=1);

namespace Modules\Media\Domain\DTOs;

use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final readonly class UploadMediaDTO extends Data
{
    public function __construct(
        public string $modelType,
        public string $modelId,
        #[WithCast(EnumCast::class)]
        public MediaCollectionType $collection,
        public ?string $disk = null,
        public ?array $metadata = null,
        public bool $optimize = true,
        public bool $generateConversions = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            modelType: $data['model_type'],
            modelId: $data['model_id'],
            collection: MediaCollectionType::from($data['collection']),
            disk: $data['disk'] ?? null,
            metadata: $data['metadata'] ?? null,
            optimize: $data['optimize'] ?? true,
            generateConversions: $data['generate_conversions'] ?? true,
        );
    }
}
