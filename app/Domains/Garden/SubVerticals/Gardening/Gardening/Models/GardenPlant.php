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
 * GardenPlant Model — Биологические свойства растений.
 */
final class GardenPlant extends Model
{
    use TenantScoped;

    protected $table = 'garden_plants';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'product_id',
        'botanical_name',
        'hardiness_zone',
        'light_requirement',
        'water_needs',
        'care_calendar',
        'is_seedling',
        'sowing_start',
        'harvest_start',
    ];

    protected $casts = [
        'care_calendar' => 'json',
        'is_seedling' => 'boolean',
        'sowing_start' => 'date',
        'harvest_start' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(GardenProduct::class, 'product_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_plants.tenant_id', tenant()->id);
        });

        self::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
