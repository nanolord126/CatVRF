<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Quota Limit Model
 * 
 * Production 2026 CANON - Tenant Quota Configuration
 * 
 * Manages per-tenant quota limits for different resource types.
 * 
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $business_group_id
 * @property string $resource_type
 * @property string|null $vertical_code
 * @property string $period
 * @property int $limit
 * @property int|null $soft_limit
 * @property bool $is_hard_limit
 * @property string|null $plan_type
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class QuotaLimit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'resource_type',
        'vertical_code',
        'period',
        'limit',
        'soft_limit',
        'is_hard_limit',
        'plan_type',
        'metadata',
    ];

    protected $casts = [
        'limit' => 'integer',
        'soft_limit' => 'integer',
        'is_hard_limit' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the quota limit.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the business group that owns the quota limit.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    /**
     * Scope for tenant-specific limits.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for business group limits.
     */
    public function scopeForBusinessGroup($query, int $businessGroupId)
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    /**
     * Scope for resource type.
     */
    public function scopeForResource($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope for period.
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope for vertical code.
     */
    public function scopeForVertical($query, ?string $verticalCode)
    {
        return $query->where('vertical_code', $verticalCode);
    }

    /**
     * Scope for plan type.
     */
    public function scopeForPlan($query, ?string $planType)
    {
        return $query->where('plan_type', $planType);
    }

    /**
     * Get effective limit for a tenant with fallback to default.
     */
    public static function getEffectiveLimit(int $tenantId, string $resourceType, string $period = 'hourly', ?string $verticalCode = null): ?int
    {
        // Try tenant-specific limit first
        $limit = self::forTenant($tenantId)
            ->forResource($resourceType)
            ->forPeriod($period)
            ->forVertical($verticalCode)
            ->first();

        if ($limit) {
            return $limit->limit;
        }

        // Try default limit (tenant_id = null)
        return self::whereNull('tenant_id')
            ->whereNull('business_group_id')
            ->forResource($resourceType)
            ->forPeriod($period)
            ->forVertical($verticalCode)
            ->value('limit');
    }

    /**
     * Get soft limit for a tenant.
     */
    public static function getSoftLimit(int $tenantId, string $resourceType, string $period = 'hourly', ?string $verticalCode = null): ?int
    {
        // Try tenant-specific limit first
        $limit = self::forTenant($tenantId)
            ->forResource($resourceType)
            ->forPeriod($period)
            ->forVertical($verticalCode)
            ->first();

        if ($limit && $limit->soft_limit) {
            return $limit->soft_limit;
        }

        // Try default limit
        return self::whereNull('tenant_id')
            ->whereNull('business_group_id')
            ->forResource($resourceType)
            ->forPeriod($period)
            ->forVertical($verticalCode)
            ->value('soft_limit');
    }

    /**
     * Check if limit is hard (blocks on exceed) or soft (just warns).
     */
    public static function isHardLimit(int $tenantId, string $resourceType, string $period = 'hourly'): bool
    {
        $limit = self::forTenant($tenantId)
            ->forResource($resourceType)
            ->forPeriod($period)
            ->first();

        if ($limit) {
            return $limit->is_hard_limit;
        }

        // Default to hard limit
        return true;
    }
}
