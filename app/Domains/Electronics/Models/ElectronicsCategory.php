<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ElectronicsCategory - Product classification.
 */
final class ElectronicsCategory extends Model
{
    use TenantScoped;

    protected $table = 'electronics_categories';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'icon',
        'correlation_id',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(ElectronicsProduct::class, 'category_id');
    }

    protected static function booted(): void
    {
        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
