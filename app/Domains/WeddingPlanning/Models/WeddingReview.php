<?php

declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * WeddingReview Model
 */
final class WeddingReview extends Model
{
    protected $table = 'wedding_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'media_urls',
        'is_published',
        'correlation_id',
    ];

    protected $casts = [
        'media_urls' => 'json',
        'rating' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_reviews.tenant_id', tenant()->id);
            }
        });

        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    /**
     * Morph relation for Planner or Vendor
     */
    public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
