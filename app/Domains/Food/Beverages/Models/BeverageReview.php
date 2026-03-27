<?php

declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class BeverageReview extends Model
{
    protected $table = 'beverage_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'shop_id',
        'item_id',
        'user_id',
        'rating',
        'comment',
        'media',
        'correlation_id',
    ];

    protected $casts = [
        'media' => 'json',
        'rating' => 'integer',
        'tenant_id' => 'string',
        'shop_id' => 'integer',
        'item_id' => 'integer',
        'user_id' => 'integer',
        'uuid' => 'string',
        'correlation_id' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = (string) Str::uuid();
        });

        // 2026 Canon: Global Scope Tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Parent shop.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(BeverageShop::class, 'shop_id');
    }

    /**
     * Specific drink item reviewed (nullable).
     */
    public function drink(): BelongsTo
    {
        return $this->belongsTo(BeverageItem::class, 'item_id');
    }

    /**
     * User who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Scope for high rated reviews.
     */
    public function scopeTopRated(Builder $query): Builder
    {
        return $query->where('rating', '>=', 4);
    }
}
