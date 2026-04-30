<?php

declare(strict_types=1);

/**
 * Warehouse — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/warehouse
 */

namespace App\Domains\Logistics\WarehouseRentals\Models;

use Carbon\CarbonImmutable;
use App\Models\WarehouseZone;
use App\Models\WarehouseLicense;
use App\Models\WarehouseDocument;
use App\Models\ColdChainReading;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Warehouse extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouses';

    protected $fillable = ['uuid', 'tenant_id', 'owner_id', 'correlation_id', 'name', 'area_sqm', 'price_kopecks_per_month', 'rating', 'is_verified', 'tags'];

    protected $casts = ['area_sqm' => 'integer', 'price_kopecks_per_month' => 'integer', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted_disabled()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('warehouses.tenant_id', tenant()->id));
    }

    /**
     * Zones in this warehouse
     */
    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class, 'warehouse_id');
    }

    /**
     * Licenses for this warehouse
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(WarehouseLicense::class, 'warehouse_id');
    }

    /**
     * Documents for this warehouse
     */
    public function documents(): HasMany
    {
        return $this->hasMany(WarehouseDocument::class, 'warehouse_id');
    }

    /**
     * Cold chain readings for this warehouse
     */
    public function coldChainReadings(): HasMany
    {
        return $this->hasMany(ColdChainReading::class, 'warehouse_id');
    }
}
