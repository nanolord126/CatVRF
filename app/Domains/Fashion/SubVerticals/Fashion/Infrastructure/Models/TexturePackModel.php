<?php

declare(strict_types=1);

namespace Modules\Fashion\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fashion\Domain\Entities\TexturePack;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;

final class TexturePackModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'texture_packs';

    protected $fillable = [
        'uuid',
        'model_3d_id',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'material_type',
        'status',
        'albedo_path',
        'normal_path',
        'roughness_path',
        'metallic_path',
        'ao_path',
        'displacement_path',
        'generation_metadata',
        'quality_metrics',
        'correlation_id',
        'error_message',
        'retry_count',
        'started_at',
        'completed_at',
        'generation_time_ms',
        'is_locked',
        'locked_at',
        'tags',
    ];

    protected $casts = [
        'generation_metadata' => 'array',
        'quality_metrics' => 'array',
        'tags' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
        'retry_count' => 'integer',
        'generation_time_ms' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function model3d(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Model3D::class, 'model_3d_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function toDomain(): TexturePack
    {
        return new TexturePack(
            uuid: $this->uuid,
            model3dId: $this->model_3d_id,
            name: $this->name,
            description: $this->description,
            materialType: TextureType::from($this->material_type),
            status: TextureGenerationStatus::from($this->status),
            albedoPath: $this->albedo_path,
            normalPath: $this->normal_path,
            roughnessPath: $this->roughness_path,
            metallicPath: $this->metallic_path,
            aoPath: $this->ao_path,
            displacementPath: $this->displacement_path,
            generationMetadata: $this->generation_metadata ?? [],
            correlationId: $this->correlation_id,
            errorMessage: $this->error_message,
            createdAt: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->created_at->format('Y-m-d H:i:s')),
            completedAt: $this->completed_at
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->completed_at->format('Y-m-d H:i:s'))
                : null,
        );
    }

    public static function fromDomain(TexturePack $texturePack): self
    {
        return new self([
            'uuid' => $texturePack->getUuid(),
            'model_3d_id' => $texturePack->getModel3dId(),
            'name' => $texturePack->getName(),
            'description' => $texturePack->getDescription(),
            'material_type' => $texturePack->getMaterialType()->value,
            'status' => $texturePack->getStatus()->value,
            'albedo_path' => $texturePack->getAlbedoPath(),
            'normal_path' => $texturePack->getNormalPath(),
            'roughness_path' => $texturePack->getRoughnessPath(),
            'metallic_path' => $texturePack->getMetallicPath(),
            'ao_path' => $texturePack->getAoPath(),
            'displacement_path' => $texturePack->getDisplacementPath(),
            'generation_metadata' => $texturePack->getGenerationMetadata(),
            'correlation_id' => $texturePack->getCorrelationId(),
            'error_message' => $texturePack->getErrorMessage(),
            'completed_at' => $texturePack->getCompletedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function scopeByStatus($query, TextureGenerationStatus $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeByMaterialType($query, TextureType $materialType)
    {
        return $query->where('material_type', $materialType->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', TextureGenerationStatus::PENDING->value);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', TextureGenerationStatus::PROCESSING->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TextureGenerationStatus::COMPLETED->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', TextureGenerationStatus::FAILED->value);
    }

    public function scopeRetryable($query, int $maxRetries = 3)
    {
        return $query->where('status', TextureGenerationStatus::FAILED->value)
            ->where('retry_count', '<', $maxRetries);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeStuck($query, int $thresholdMinutes = 30)
    {
        return $query->where('status', TextureGenerationStatus::PROCESSING->value)
            ->where('started_at', '<', now()->subMinutes($thresholdMinutes));
    }

    public function lock(): void
    {
        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'is_locked' => false,
            'locked_at' => null,
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === TextureGenerationStatus::FAILED->value
            && $this->retry_count < $maxRetries;
    }

    public function getGenerationTimeSeconds(): float
    {
        return $this->generation_time_ms ? $this->generation_time_ms / 1000 : 0;
    }
}
