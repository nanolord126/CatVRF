<?php

declare(strict_types=1);

namespace Modules\Media\Domain\Entities;

use Illuminate\Support\Facades\Auth;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaMimeType;
use Modules\Media\Domain\ValueObjects\MediaStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final readonly class MediaFile implements HasMedia
{
    use InteractsWithMedia;

    public function __construct(
        public string $id,
        public int $tenantId,
        public string $modelType,
        public string $modelId,
        public MediaCollectionType $collection,
        public MediaMimeType $mimeType,
        public string $originalFileName,
        public string $disk,
        public string $path,
        public int $sizeBytes,
        public MediaStatus $status,
        public ?string $cdnUrl = null,
        public ?array $metadata = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    public static function create(
        string $modelType,
        string $modelId,
        MediaCollectionType $collection,
        MediaMimeType $mimeType,
        string $originalFileName,
        string $disk,
        string $path,
        int $sizeBytes,
    ): self {
        return new self(
            id: (string) \Illuminate\Support\Str::uuid(),
            tenantId: tenant('id') ?? 0,
            modelType: $modelType,
            modelId: $modelId,
            collection: $collection,
            mimeType: $mimeType,
            originalFileName: $originalFileName,
            disk: $disk,
            path: $path,
            sizeBytes: $sizeBytes,
            status: MediaStatus::ACTIVE,
        );
    }

    public function withCdnUrl(string $cdnUrl): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            modelType: $this->modelType,
            modelId: $this->modelId,
            collection: $this->collection,
            mimeType: $this->mimeType,
            originalFileName: $this->originalFileName,
            disk: $this->disk,
            path: $this->path,
            sizeBytes: $this->sizeBytes,
            status: $this->status,
            cdnUrl: $cdnUrl,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function isImage(): bool
    {
        return $this->mimeType->isImage();
    }

    public function isVideo(): bool
    {
        return $this->mimeType->isVideo();
    }

    public function isDocument(): bool
    {
        return $this->mimeType->isDocument();
    }

    public function getTenantPath(): string
    {
        return "tenant/{$this->tenantId}/{$this->modelType}/{$this->modelId}/{$this->id}";
    }
}
