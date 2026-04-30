<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 */
/**
 * GardenStore Model — Садовые магазины.
 */
final class GardenStore extends Model
{
    use TenantScoped;

    protected $table = 'garden_stores';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'location_lat_lon',
        'climate_zones',
        'tags',
    ];

    protected $casts = [
        'climate_zones' => 'json',
        'tags' => 'json',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(GardenProduct::class, 'store_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_stores.tenant_id', tenant()->id);
        });

        self::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
