<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait JewelryDomainTrait
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }


    protected static function booted_disabled(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()->id ?? 0;
            }
        });

        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant')) {
                $builder->where('tenant_id', tenant()->id ?? 0);
            }
        });
    }
}
