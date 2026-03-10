<?php

namespace App\Traits\Common;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;

trait HasEcosystemAuth
{
    use HasRoles;

    public function canAccessPanel($panel): bool
    {
        return match($panel->getId()) {
            'admin' => $this->hasRole('super-admin'),
            'tenant' => $this->hasAnyRole(['tenant-owner', 'tenant-manager', 'support-agent']),
            'b2b' => $this->hasRole('b2b-supplier'),
            default => false,
        };
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        return $query->where('tenant_id', tenant('id'));
    }
}
