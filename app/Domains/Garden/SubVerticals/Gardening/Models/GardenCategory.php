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
 * GardenCategory Model — Категории садовых товаров.
 */
final class GardenCategory extends Model
{
    use TenantScoped;

    protected $table = 'garden_categories';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'slug',
        'care_guide_summary',
    ];

    protected $casts = [];

    public function products(): HasMany
    {
        return $this->hasMany(GardenProduct::class, 'category_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_categories.tenant_id', tenant()->id);
        });

        self::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
