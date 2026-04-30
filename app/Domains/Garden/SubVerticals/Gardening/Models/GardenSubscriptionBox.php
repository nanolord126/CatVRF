<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 */
/**
 * GardenSubscriptionBox Model — Подписочные коробки для садоводов.
 */
final class GardenSubscriptionBox extends Model
{
    use TenantScoped;

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
        self::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_subscription_boxes.tenant_id', tenant()->id);
        });

        self::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
