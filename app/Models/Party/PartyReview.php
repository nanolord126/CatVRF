<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PartyReview Model.
 * Represents customer feedback for stores or products.
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $user_id
 * @property int $reviewable_id
 * @property string $reviewable_type
 * @property float $rating
 * @property string $comment
 * @property bool $is_verified
 * @property bool $is_active
 */
final class PartyReview extends Model
{
    use SoftDeletes;

    protected $table = 'party_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'comment',
        'photos',
        'is_verified',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'photos' => 'json',
        'tags' => 'json',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Polymorphic relationship to Store or Product.
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship: Associated user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
