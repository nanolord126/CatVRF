<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * CollectibleReview — Trust and verification feedback.
 */
final class CollectibleReview extends Model
{
    protected $table = 'collectible_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reviewable_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectibleReview $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Polymorphic relation to Reviewable entities (Store, Item).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
