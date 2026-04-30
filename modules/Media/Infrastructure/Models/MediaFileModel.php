<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaMimeType;
use Modules\Media\Domain\ValueObjects\MediaStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class MediaFileModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'media_files';

    protected $fillable = [
        'id',
        'tenant_id',
        'model_type',
        'model_id',
        'collection',
        'mime_type',
        'original_file_name',
        'disk',
        'path',
        'size_bytes',
        'status',
        'cdn_url',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'size_bytes' => 'integer',
        'status' => MediaStatus::class,
        'collection' => MediaCollectionType::class,
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function toDomain(): MediaFile
    {
        return new MediaFile(
            id: $this->id,
            tenantId: $this->tenant_id,
            modelType: $this->model_type,
            modelId: $this->model_id,
            collection: $this->collection,
            mimeType: MediaMimeType::fromString($this->mime_type),
            originalFileName: $this->original_file_name,
            disk: $this->disk,
            path: $this->path,
            sizeBytes: $this->size_bytes,
            status: $this->status,
            cdnUrl: $this->cdn_url,
            metadata: $this->metadata,
            createdAt: $this->created_at?->toDateTimeImmutable(),
            updatedAt: $this->updated_at?->toDateTimeImmutable(),
        );
    }

    public static function fromDomain(MediaFile $mediaFile): self
    {
        return new self([
            'id' => $mediaFile->id,
            'tenant_id' => $mediaFile->tenantId,
            'model_type' => $mediaFile->modelType,
            'model_id' => $mediaFile->modelId,
            'collection' => $mediaFile->collection,
            'mime_type' => $mediaFile->mimeType->value,
            'original_file_name' => $mediaFile->originalFileName,
            'disk' => $mediaFile->disk,
            'path' => $mediaFile->path,
            'size_bytes' => $mediaFile->sizeBytes,
            'status' => $mediaFile->status,
            'cdn_url' => $mediaFile->cdnUrl,
            'metadata' => $mediaFile->metadata,
        ]);
    }
}
