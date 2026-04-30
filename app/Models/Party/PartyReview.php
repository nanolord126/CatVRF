<?php

declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use App\Models\User;

final class PartyReview extends Model
{
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
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
