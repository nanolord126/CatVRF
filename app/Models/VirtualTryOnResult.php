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
use Illuminate\Support\Facades\Storage;

final class VirtualTryOnResult extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'virtual_try_on_results';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'user_profile_id',
        'user_profile_type',
        'product_id',
        'product_type',
        'variant_id',
        'variant_type',
        'try_on_asset_id',
        'mode',
        'result_image_url',
        'result_video_url',
        'result_3d_scene_url',
        'thumbnail_url',
        'pose',
        'lighting',
        'background',
        'camera_angle',
        'confidence_score',
        'quality_score',
        'fit_score',
        'size_recommendation',
        'size_accuracy',
        'color_accuracy',
        'texture_quality',
        'processing_time_ms',
        'generation_method',
        'is_saved',
        'is_shared',
        'share_token',
        'shared_at',
        'shared_count',
        'view_count',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'quality_score' => 'float',
        'fit_score' => 'float',
        'size_recommendation' => 'json',
        'processing_time_ms' => 'integer',
        'is_saved' => 'boolean',
        'is_shared' => 'boolean',
        'shared_at' => 'datetime',
        'shared_count' => 'integer',
        'view_count' => 'integer',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const MODE_2D = '2d';
    public const MODE_AR = 'ar';
    public const MODE_3D = '3d';

    public const GENERATION_METHOD_AI_DIFFUSION = 'ai_diffusion';
    public const GENERATION_METHOD_GAN = 'gan';
    public const GENERATION_METHOD_NEURAL_RENDERING = 'neural_rendering';
    public const GENERATION_METHOD_TRADITIONAL = 'traditional';

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSaved(Builder $query): Builder
    {
        return $query->where('is_saved', true);
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    public function scopeByMode(Builder $query, string $mode): Builder
    {
        return $query->where('mode', $mode);
    }

    public function scopeHighQuality(Builder $query): Builder
    {
        return $query->where('quality_score', '>=', 0.8);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userProfile(): MorphTo
    {
        return $this->morphTo();
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): MorphTo
    {
        return $this->morphTo();
    }

    public function tryOnAsset(): BelongsTo
    {
        return $this->belongsTo(VirtualTryOnAsset::class, 'try_on_asset_id');
    }

    public function getResultUrlAttribute(): ?string
    {
        return $this->result_video_url ?? $this->result_image_url ?? $this->result_3d_scene_url;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_url ?? $this->result_image_url;
    }

    public function getProcessingTimeSecondsAttribute(): float
    {
        return round($this->processing_time_ms / 1000, 2);
    }

    public function getOverallRatingAttribute(): float
    {
        $ratings = [
            $this->confidence_score,
            $this->quality_score,
            $this->fit_score,
        ];

        return round(array_sum($ratings) / count(array_filter($ratings)), 2);
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

    public function getFitRatingAttribute(): string
    {
        return match(true) {
            $this->fit_score >= 0.9 => 'perfect',
            $this->fit_score >= 0.75 => 'very_good',
            $this->fit_score >= 0.6 => 'good',
            $this->fit_score >= 0.4 => 'acceptable',
            default => 'poor',
        };
    }

    public function getSizeRecommendationTextAttribute(): ?string
    {
        if (!$this->size_recommendation) {
            return null;
        }

        $size = $this->size_recommendation['size'] ?? null;
        $confidence = $this->size_recommendation['confidence'] ?? null;
        $message = $this->size_recommendation['message'] ?? null;

        if ($size && $confidence) {
            return "Recommended size: {$size} (confidence: {$confidence}%)";
        }

        return $message;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        $this->clearCache();
    }

    public function saveResult(): void
    {
        $this->update(['is_saved' => true]);
        $this->clearCache();
    }

    public function unsaveResult(): void
    {
        $this->update(['is_saved' => false]);
        $this->clearCache();
    }

    public function share(): string
    {
        if (!$this->is_shared) {
            $this->update([
                'is_shared' => true,
                'share_token' => \Illuminate\Support\Str::random(32),
                'shared_at' => now(),
            ]);
        }

        $this->increment('shared_count');
        $this->clearCache();

        return url("/shared/try-on/{$this->share_token}");
    }

    public function unshare(): void
    {
        $this->update([
            'is_shared' => false,
            'share_token' => null,
            'shared_at' => null,
        ]);
        $this->clearCache();
    }

    public function deleteResultFiles(): void
    {
        try {
            if ($this->result_image_url) {
                Storage::disk('cdn')->delete($this->result_image_url);
            }
            if ($this->result_video_url) {
                Storage::disk('cdn')->delete($this->result_video_url);
            }
            if ($this->result_3d_scene_url) {
                Storage::disk('cdn')->delete($this->result_3d_scene_url);
            }
            if ($this->thumbnail_url) {
                Storage::disk('cdn')->delete($this->thumbnail_url);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete try-on result files', [
                'result_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearCache(): void
    {
        Cache::tags(['virtual_try_on_results', "virtual_try_on_result:{$this->id}"])->flush();
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
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::deleted(function ($model) {
            $model->deleteResultFiles();
            $model->clearCache();
        });
    }
}
