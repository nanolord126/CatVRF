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

final class Product3DAsset extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'product_3d_assets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'product_type',
        'variant_id',
        'variant_type',
        'file_url',
        'preview_image',
        'low_poly_url',
        'material_maps',
        'albedo_map',
        'normal_map',
        'roughness_map',
        'metallic_map',
        'ao_map',
        'emissive_map',
        'opacity_map',
        'displacement_map',
        'rigged',
        'body_type_compatibility',
        'animation_presets',
        'polygon_count',
        'texture_resolution',
        'compression_format',
        'file_size_bytes',
        'file_size_compressed_bytes',
        'compression_ratio',
        'lod_levels',
        'bounding_box',
        'pivot_point',
        'scale_factor',
        'rotation_offset',
        'generation_method',
        'generation_confidence',
        'quality_score',
        'optimization_level',
        'is_optimized',
        'is_processed',
        'processing_status',
        'processing_time_ms',
        'generated_at',
        'generated_by',
        'error_message',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'material_maps' => 'json',
        'animation_presets' => 'json',
        'lod_levels' => 'json',
        'bounding_box' => 'json',
        'pivot_point' => 'json',
        'rotation_offset' => 'json',
        'scale_factor' => 'float',
        'generation_confidence' => 'float',
        'quality_score' => 'float',
        'optimization_level' => 'integer',
        'is_optimized' => 'boolean',
        'is_processed' => 'boolean',
        'processing_time_ms' => 'integer',
        'generated_at' => 'datetime',
        'file_size_bytes' => 'integer',
        'file_size_compressed_bytes' => 'integer',
        'compression_ratio' => 'float',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const GENERATION_METHOD_MANUAL = 'manual';
    public const GENERATION_METHOD_PHOTOGRAMMETRY = 'photogrammetry';
    public const GENERATION_METHOD_TRIPOSR = 'triposr';
    public const GENERATION_METHOD_LUMA_AI = 'luma_ai';
    public const GENERATION_METHOD_MESHY = 'meshy';
    public const GENERATION_METHOD_INSTANT_MESH = 'instant_mesh';
    public const GENERATION_METHOD_ZERO123 = 'zero123';

    public const COMPRESSION_FORMAT_DRACO = 'draco';
    public const COMPRESSION_FORMAT_MESHOPT = 'meshopt';
    public const COMPRESSION_FORMAT_KTX2 = 'ktx2';
    public const COMPRESSION_FORMAT_BASIS = 'basis';
    public const COMPRESSION_FORMAT_NONE = 'none';

    public const PROCESSING_STATUS_PENDING = 'pending';
    public const PROCESSING_STATUS_UPLOADING = 'uploading';
    public const PROCESSING_STATUS_PROCESSING = 'processing';
    public const PROCESSING_STATUS_OPTIMIZING = 'optimizing';
    public const PROCESSING_STATUS_COMPRESSING = 'compressing';
    public const PROCESSING_STATUS_COMPLETED = 'completed';
    public const PROCESSING_STATUS_FAILED = 'failed';

    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByVariant(Builder $query, int $variantId): Builder
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeOptimized(Builder $query): Builder
    {
        return $query->where('is_optimized', true);
    }

    public function scopeRigged(Builder $query): Builder
    {
        return $query->where('rigged', true);
    }

    public function scopeHighQuality(Builder $query): Builder
    {
        return $query->where('quality_score', '>=', 0.8);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('processing_status', self::PROCESSING_STATUS_COMPLETED);
    }

    public function scopeByGenerationMethod(Builder $query, string $method): Builder
    {
        return $query->where('generation_method', $method);
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFileUrlAttribute(): string
    {
        return $this->low_poly_url ?? $this->file_url;
    }

    public function getFileSizeMBAttribute(): float
    {
        $size = $this->file_size_compressed_bytes ?? $this->file_size_bytes;
        return round($size / 1024 / 1024, 2);
    }

    public function getFileSizeCompressedMBAttribute(): float
    {
        return round($this->file_size_compressed_bytes / 1024 / 1024, 2);
    }

    public function getCompressionPercentageAttribute(): float
    {
        if (!$this->file_size_bytes || !$this->file_size_compressed_bytes) {
            return 0.0;
        }

        return round((1 - ($this->file_size_compressed_bytes / $this->file_size_bytes)) * 100, 1);
    }

    public function getProcessingTimeSecondsAttribute(): float
    {
        return round($this->processing_time_ms / 1000, 2);
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

    public function getPolygonCountFormattedAttribute(): string
    {
        if ($this->polygon_count >= 1000000) {
            return round($this->polygon_count / 1000000, 1) . 'M';
        } elseif ($this->polygon_count >= 1000) {
            return round($this->polygon_count / 1000, 1) . 'K';
        }

        return (string) $this->polygon_count;
    }

    public function getHasLODAttribute(): bool
    {
        return !empty($this->lod_levels) && count($this->lod_levels) > 1;
    }

    public function getLODCountAttribute(): int
    {
        return count($this->lod_levels ?? []);
    }

    public function getMaterialMapsCountAttribute(): int
    {
        $maps = array_filter([
            $this->albedo_map,
            $this->normal_map,
            $this->roughness_map,
            $this->metallic_map,
            $this->ao_map,
            $this->emissive_map,
            $this->opacity_map,
            $this->displacement_map,
        ]);

        return count($maps);
    }

    public function isCompatibleWithBodyType(string $bodyType): bool
    {
        if (empty($this->body_type_compatibility)) {
            return true;
        }

        return in_array($bodyType, $this->body_type_compatibility, true);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_STATUS_PROCESSING,
            'processing_time_ms' => null,
        ]);
    }

    public function markAsOptimizing(): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_STATUS_OPTIMIZING,
        ]);
    }

    public function markAsCompleted(array $data): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_STATUS_COMPLETED,
            'file_url' => $data['file_url'] ?? $this->file_url,
            'low_poly_url' => $data['low_poly_url'] ?? $this->low_poly_url,
            'preview_image' => $data['preview_image'] ?? $this->preview_image,
            'albedo_map' => $data['albedo_map'] ?? $this->albedo_map,
            'normal_map' => $data['normal_map'] ?? $this->normal_map,
            'roughness_map' => $data['roughness_map'] ?? $this->roughness_map,
            'metallic_map' => $data['metallic_map'] ?? $this->metallic_map,
            'polygon_count' => $data['polygon_count'] ?? $this->polygon_count,
            'file_size_bytes' => $data['file_size_bytes'] ?? $this->file_size_bytes,
            'file_size_compressed_bytes' => $data['file_size_compressed_bytes'] ?? $this->file_size_compressed_bytes,
            'compression_ratio' => $data['compression_ratio'] ?? $this->compression_ratio,
            'generation_confidence' => $data['confidence'] ?? $this->generation_confidence,
            'quality_score' => $data['quality_score'] ?? $this->quality_score,
            'is_optimized' => $data['is_optimized'] ?? $this->is_optimized,
            'is_processed' => true,
            'processing_time_ms' => $data['processing_time_ms'] ?? $this->processing_time_ms,
            'generated_at' => now(),
            'error_message' => null,
        ]);
        $this->clearCache();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'processing_status' => self::PROCESSING_STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
        $this->clearCache();
    }

    public function deleteFiles(): void
    {
        try {
            $files = [
                $this->file_url,
                $this->low_poly_url,
                $this->preview_image,
                $this->albedo_map,
                $this->normal_map,
                $this->roughness_map,
                $this->metallic_map,
                $this->ao_map,
                $this->emissive_map,
                $this->opacity_map,
                $this->displacement_map,
            ];

            foreach ($files as $file) {
                if ($file) {
                    Storage::disk('cdn')->delete($file);
                }
            }

            if (!empty($this->lod_levels)) {
                foreach ($this->lod_levels as $lod) {
                    if (isset($lod['file_url'])) {
                        Storage::disk('cdn')->delete($lod['file_url']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete 3D asset files', [
                'asset_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearCache(): void
    {
        Cache::tags(['product_3d_assets', "product_3d_asset:{$this->id}"])->flush();
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
            if (empty($model->processing_status)) {
                $model->processing_status = self::PROCESSING_STATUS_PENDING;
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
