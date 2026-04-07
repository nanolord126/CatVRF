<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VerticalReview — отзывы на товары вертикали VerticalName.
 *
 * Tenant-aware, привязан к VerticalItem и User.
 *
 * CANON 2026 — Layer 1: Models.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int         $user_id
 * @property int         $vertical_item_id
 * @property int         $rating
 * @property string|null $title
 * @property string|null $body
 * @property bool        $is_verified_purchase
 * @property bool        $is_published
 * @property array|null  $tags
 * @property array|null  $metadata
 * @property string|null $correlation_id
 */
final class VerticalReview extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'vertical_name_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'vertical_item_id',
        'rating',
        'title',
        'body',
        'is_verified_purchase',
        'is_published',
        'tags',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_verified_purchase' => 'boolean',
        'is_published' => 'boolean',
        'rating' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Tenant scoping + автогенерация uuid/correlation_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Товар, к которому относится отзыв.
     */
    public function verticalItem(): BelongsTo
    {
        return $this->belongsTo(
            VerticalItem::class,
            'vertical_item_id',
        );
    }

    /**
     * Пользователь — автор отзыва.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'user_id',
        );
    }

    /**
     * Tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Tenant::class,
            'tenant_id',
        );
    }

    /**
     * Scope для опубликованных отзывов.
     */
    public function scopePublished($query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Scope для верифицированных покупок.
     */
    public function scopeVerifiedPurchase($query): void
    {
        $query->where('is_verified_purchase', true);
    }

    /**
     * Рейтинг в допустимом диапазоне (1-5).
     */
    public function isValidRating(): bool
    {
        return $this->rating >= 1 && $this->rating <= 5;
    }
}
