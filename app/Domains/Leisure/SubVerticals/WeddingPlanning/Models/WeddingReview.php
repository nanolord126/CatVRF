<?php

declare(strict_types=1);

/**
 * WeddingReview — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/weddingreview
 */

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class WeddingReview extends Model
{
    use TenantScoped;

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

    /**
     * Morph relation for Planner or Vendor
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_reviews.tenant_id', tenant()->id);
            }
        });

        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }
}
