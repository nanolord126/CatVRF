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
 * GardenSubscriptionBox Model — Подписочные коробки для садоводов.
 *
 * @package CatVRF Gardening Vertical
 */
final class GardenSubscriptionBox extends Model
{
    use HasFactory;

    protected $table = 'garden_subscription_boxes';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'frequency',
        'price',
        'contents_json',
        'is_active',
    ];

    protected $casts = [
        'contents_json' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_subscription_boxes.tenant_id', tenant()->id);
        });

        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
