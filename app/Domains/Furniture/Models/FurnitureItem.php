<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class FurnitureItem extends Model
{
    use TenantScoped;

    /**
     * Boot the model to handle automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        if (function_exists('tenant') && tenant()) {
            self::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', tenant()->id);
            });
        }
    }
}
