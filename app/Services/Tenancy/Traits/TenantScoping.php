<?php declare(strict_types=1);

namespace App\Services\Tenancy\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant Scoping Trait
 * 
 * Provides consistent tenant scoping for all models
 * 
 * Usage:
 * class MyModel extends Model {
 *     use TenantScoping;
 *     
 *     protected static function bootTenantScoping() {
 *         static::addGlobalScope('tenant', function (Builder $query) {
 *             return $query->where('tenant_id', tenant('id'));
 *         });
 *     }
 * }
 */
trait TenantScoping
{
    /**
     * Boot the tenant scoping trait
     */
    protected static function bootTenantScoping(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (self::shouldApplyTenantScope()) {
                $tenantId = self::getTenantId();
                
                if ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }
            }

            return $query;
        });
    }

    /**
     * Determine if tenant scope should be applied
     */
    protected static function shouldApplyTenantScope(): bool
    {
        // Don't apply scope for landlord/admin operations
        if (app()->runningInConsole()) {
            $command = request()->server->get('argv')[1] ?? '';
            
            // Allow tenant scope for specific console commands
            $allowedCommands = [
                'migrate',
                'db:seed',
                'queue:work',
                'schedule:run',
            ];

            return in_array($command, $allowedCommands);
        }

        return true;
    }

    /**
     * Get current tenant ID
     */
    protected static function getTenantId(): ?int
    {
        // Try multiple methods
        if (function_exists('tenant') && tenant('id')) {
            return (int) tenant('id');
        }

        if (request()->attributes->has('tenant_id')) {
            return (int) request()->attributes->get('tenant_id');
        }

        if (auth()->check() && auth()->user()->tenant_id) {
            return (int) auth()->user()->tenant_id;
        }

        return null;
    }

    /**
     * Scope to specific tenant
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);
    }

    /**
     * Scope without tenant restriction (admin only)
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope to business group
     */
    public function scopeForBusinessGroup(Builder $query, int $businessGroupId): Builder
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    /**
     * Scope to business group with all its tenants
     */
    public function scopeForBusinessGroupWithTenants(Builder $query, int $businessGroupId): Builder
    {
        return $query->where(function ($q) use ($businessGroupId) {
            $q->where('business_group_id', $businessGroupId)
              ->orWhereIn('tenant_id', function ($subQuery) use ($businessGroupId) {
                  $subQuery->select('id')
                      ->from('tenants')
                      ->where('business_group_id', $businessGroupId);
              });
        });
    }
}
