<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 */
/**
 * GardenReview Model — Отзывы на садовые товары.
 */
final class GardenReview extends Model
{
    use TenantScoped;

    protected $table = 'garden_reviews';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'product_id',
        'user_id',
        'rating',
        'comment',
        'growth_updates',
    ];

    protected $casts = [
        'growth_updates' => 'json',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(GardenProduct::class, 'product_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_reviews.tenant_id', tenant()->id);
        });

        self::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
