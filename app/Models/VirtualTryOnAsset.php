<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class VirtualTryOnAsset extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'virtual_try_on_assets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'product_type',
        'variant_id',
        'variant_type',
        'type',
        'model_2d_url',
        'model_3d_url',
        'segmentation_mask',
        'category_mask',
        'texture_map',
        'normal_map',
        'roughness_map',
        'metallic_map',
        'alpha_map',
        'body_type_compatibility',
        'size_compatibility',
        'gender_compatibility',
        'skin_tone_compatibility',
        'pose_presets',
        'lighting_presets',
        'background_presets',
        'generation_method',
        'generation_confidence',
        'quality_score',
        'is_optimized',
        'is_rigged',
        'polygon_count',
        'texture_resolution',
        'file_size_bytes',
        'processing_time_ms',
        'generated_at',
        'generated_by',
        'status',
        'error_message',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'body_type_compatibility' => 'json',
        'size_compatibility' => 'json',
        'gender_compatibility' => 'json',
        'skin_tone_compatibility' => 'json',
        'pose_presets' => 'json',
        'lighting_presets' => 'json',
        'background_presets' => 'json',
        'generation_confidence' => 'float',
        'quality_score' => 'float',
        'is_optimized' => 'boolean',
        'is_rigged' => 'boolean',
        'polygon_count' => 'integer',
        'texture_resolution' => 'string',
        'file_size_bytes' => 'integer',
        'processing_time_ms' => 'integer',
        'generated_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const TYPE_CLOTHING_TOP = 'clothing_top';
    public const TYPE_CLOTHING_BOTTOM = 'clothing_bottom';
    public const TYPE_CLOTHING_FULL_BODY = 'clothing_full_body';
    public const TYPE_CLOTHING_DRESS = 'clothing_dress';
    public const TYPE_CLOTHING_OUTERWEAR = 'clothing_outerwear';
    public const TYPE_FOOTWEAR = 'footwear';
    public const TYPE_ACCESSORY = 'accessory';
    public const TYPE_HEADWEAR = 'headwear';

    public const GENERATION_METHOD_MANUAL = 'manual';
    public const GENERATION_METHOD_AUTO_2D = 'auto_2d';
    public const GENERATION_METHOD_AUTO_3D = 'auto_3d';
    public const GENERATION_METHOD_AI_GENERATED = 'ai_generated';
    public const GENERATION_METHOD_PHOTOGRAMMETRY = 'photogrammetry';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_OPTIMIZING = 'optimizing';

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeHighQuality(Builder $query): Builder
    {
        return $query->where('quality_score', '>=', 0.8);
    }

    public function scopeOptimized(Builder $query): Builder
    {
        return $query->where('is_optimized', true);
    }

    public function scopeRigged(Builder $query): Builder
    {
        return $query->where('is_rigged', true);
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCompatibleWithBodyType(string $bodyType): bool
    {
        if (empty($this->body_type_compatibility)) {
            return true;
        }

        return in_array($bodyType, $this->body_type_compatibility, true);
    }

    public function getCompatibleWithSize(string $size): bool
    {
        if (empty($this->size_compatibility)) {
            return true;
        }

        return in_array($size, $this->size_compatibility, true);
    }

    public function getCompatibleWithGender(string $gender): bool
    {
        if (empty($this->gender_compatibility)) {
            return true;
        }

        return in_array($gender, $this->gender_compatibility, true);
    }

    public function getQualityRatingAttribute(): string
    {
        return match(true) {
            $this->quality_score >= 0.9 => 'excellent',
            $this->quality_score >= 0.75 => 'very_good',
            $this->quality_score >= 0.6 => 'good',
            $this->quality_score >= 0.4 => 'fair',
            default => 'poor',
        };
    }

    public function getFileSizeMBAttribute(): float
    {
        return round($this->file_size_bytes / 1024 / 1024, 2);
    }

    public function getProcessingTimeSecondsAttribute(): float
    {
        return round($this->processing_time_ms / 1000, 2);
    }

    public function getModelUrlAttribute(): ?string
    {
        return $this->model_3d_url ?? $this->model_2d_url;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_time_ms' => null,
        ]);
    }

    public function markAsCompleted(array $data): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'model_2d_url' => $data['model_2d_url'] ?? $this->model_2d_url,
            'model_3d_url' => $data['model_3d_url'] ?? $this->model_3d_url,
            'segmentation_mask' => $data['segmentation_mask'] ?? $this->segmentation_mask,
            'generation_confidence' => $data['confidence'] ?? $this->generation_confidence,
            'quality_score' => $data['quality_score'] ?? $this->quality_score,
            'polygon_count' => $data['polygon_count'] ?? $this->polygon_count,
            'file_size_bytes' => $data['file_size_bytes'] ?? $this->file_size_bytes,
            'processing_time_ms' => $data['processing_time_ms'] ?? $this->processing_time_ms,
            'generated_at' => now(),
            'error_message' => null,
        ]);
        $this->clearCache();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
        $this->clearCache();
    }

    public function deleteFiles(): void
    {
        try {
            if ($this->model_2d_url) {
                Storage::disk('cdn')->delete($this->model_2d_url);
            }
            if ($this->model_3d_url) {
                Storage::disk('cdn')->delete($this->model_3d_url);
            }
            if ($this->segmentation_mask) {
                Storage::disk('cdn')->delete($this->segmentation_mask);
            }
            if ($this->texture_map) {
                Storage::disk('cdn')->delete($this->texture_map);
            }
            if ($this->normal_map) {
                Storage::disk('cdn')->delete($this->normal_map);
            }
            if ($this->roughness_map) {
                Storage::disk('cdn')->delete($this->roughness_map);
            }
            if ($this->metallic_map) {
                Storage::disk('cdn')->delete($this->metallic_map);
            }
            if ($this->alpha_map) {
                Storage::disk('cdn')->delete($this->alpha_map);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete virtual try-on asset files', [
                'asset_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearCache(): void
    {
        Cache::tags(['virtual_try_on_assets', "virtual_try_on_asset:{$this->id}"])->flush();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant()->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::deleted(function ($model) {
            $model->deleteFiles();
            $model->clearCache();
        });
    }
}
