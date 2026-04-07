<?php
declare(strict_types=1);

namespace App\Domains\Gardening\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 *
 * @package CatVRF Gardening Vertical
 */
/**
 * GardenReview Model — Отзывы на садовые товары.
 *
 * @package CatVRF Gardening Vertical
 */
final class GardenReview extends Model
{
    use HasFactory;

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_reviews.tenant_id', tenant()->id);
        });

        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GardenProduct::class, 'product_id');
    }
}
