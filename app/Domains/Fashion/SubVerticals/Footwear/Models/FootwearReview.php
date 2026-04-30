<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class FootwearReview extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'footwear_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'footwear_product_id',
        'footwear_variant_id',
        'order_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'size_accuracy',
        'comfort_rating',
        'quality_rating',
        'durability_rating',
        'value_rating',
        'style_rating',
        'images',
        'videos',
        'size_purchased',
        'width_purchased',
        'foot_type',
        'usage_duration_months',
        'usage_frequency',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'not_helpful_count',
        'reported_count',
        'moderation_status',
        'moderated_by',
        'moderated_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'size_accuracy' => 'integer',
        'comfort_rating' => 'integer',
        'quality_rating' => 'integer',
        'durability_rating' => 'integer',
        'value_rating' => 'integer',
        'style_rating' => 'integer',
        'images' => 'json',
        'videos' => 'json',
        'usage_duration_months' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'reported_count' => 'integer',
        'moderated_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const MODERATION_STATUS_PENDING = 'pending';
    public const MODERATION_STATUS_APPROVED = 'approved';
    public const MODERATION_STATUS_REJECTED = 'rejected';
    public const MODERATION_STATUS_FLAGGED = 'flagged';

    public const SIZE_ACCURACY_TOO_SMALL = 'too_small';
    public const SIZE_ACCURACY_SLIGHTLY_SMALL = 'slightly_small';
    public const SIZE_ACCURACY_TRUE_TO_SIZE = 'true_to_size';
    public const SIZE_ACCURACY_SLIGHTLY_LARGE = 'slightly_large';
    public const SIZE_ACCURACY_TOO_LARGE = 'too_large';

    public const USAGE_FREQUENCY_DAILY = 'daily';
    public const USAGE_FREQUENCY_WEEKLY = 'weekly';
    public const USAGE_FREQUENCY_MONTHLY = 'monthly';
    public const USAGE_FREQUENCY_RARELY = 'rarely';

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('moderation_status', self::MODERATION_STATUS_PENDING);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    public function scopeBySizeAccuracy(Builder $query, string $accuracy): Builder
    {
        return $query->where('size_accuracy', $accuracy);
    }

    public function scopeMostHelpful(Builder $query): Builder
    {
        return $query->orderByDesc('helpful_count');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FootwearProduct::class, 'footwear_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(FootwearVariant::class, 'footwear_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(\App\Models\Media::class, 'mediable');
    }

    public function getAverageRatingAttribute(): float
    {
        $ratings = [
            $this->comfort_rating,
            $this->quality_rating,
            $this->durability_rating,
            $this->value_rating,
            $this->style_rating,
        ];

        return round(array_sum($ratings) / count(array_filter($ratings)), 1);
    }

    public function getHelpfulnessScoreAttribute(): float
    {
        if ($this->helpful_count + $this->not_helpful_count === 0) {
            return 0.0;
        }

        return round(
            ($this->helpful_count / ($this->helpful_count + $this->not_helpful_count)) * 100,
            1
        );
    }

    public function getSizeAccuracyLabelAttribute(): string
    {
        return match($this->size_accuracy) {
            self::SIZE_ACCURACY_TOO_SMALL => 'Too Small',
            self::SIZE_ACCURACY_SLIGHTLY_SMALL => 'Slightly Small',
            self::SIZE_ACCURACY_TRUE_TO_SIZE => 'True to Size',
            self::SIZE_ACCURACY_SLIGHTLY_LARGE => 'Slightly Large',
            self::SIZE_ACCURACY_TOO_LARGE => 'Too Large',
            default => 'Not Specified',
        };
    }

    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
        $this->clearCache();
    }

    public function markAsNotHelpful(): void
    {
        $this->increment('not_helpful_count');
        $this->clearCache();
    }

    public function report(): void
    {
        $this->increment('reported_count');
        $this->update(['moderation_status' => self::MODERATION_STATUS_FLAGGED]);
        $this->clearCache();
    }

    public function approve(int $moderatorId): void
    {
        $this->update([
            'is_approved' => true,
            'moderation_status' => self::MODERATION_STATUS_APPROVED,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
        ]);
        $this->clearCache();
        $this->product->clearCache();
    }

    public function reject(int $moderatorId, string $reason): void
    {
        $this->update([
            'is_approved' => false,
            'moderation_status' => self::MODERATION_STATUS_REJECTED,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['rejection_reason' => $reason]),
        ]);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::tags(['footwear_reviews', "footwear_review:{$this->id}"])->flush();
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
            if (empty($model->moderation_status)) {
                $model->moderation_status = self::MODERATION_STATUS_PENDING;
            }
        });

        static::updated(function ($model) {
            if ($model->isDirty(['is_approved', 'rating'])) {
                $model->clearCache();
                if ($model->product) {
                    $model->product->clearCache();
                }
            }
        });

        static::deleted(function ($model) {
            $model->clearCache();
            if ($model->product) {
                $model->product->clearCache();
            }
        });
    }
}
