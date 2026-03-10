<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Trait StrictTenantIsolation
 * Final security layer for 2026 production-ready multi-tenancy.
 */
trait StrictTenantIsolation
{
    use BelongsToTenant;

    public static function bootStrictTenantIsolation(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->getAttribute(config('tenancy.tenant_key', 'tenant_id')))) {
                $model->setAttribute(
                    config('tenancy.tenant_key', 'tenant_id'), 
                    tenant('id')
                );
            }
        });

        static::addGlobalScope('tenant_isolation', function (Builder $builder) {
            if (tenant()) {
                $builder->where(
                    config('tenancy.tenant_key', 'tenant_id'), 
                    tenant('id')
                );
            }
        });
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant_isolation');
    }
}
