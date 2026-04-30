<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Warehouse Product Model
 *
 * Represents products stored in warehouses with full tracking capabilities.
 * Supports hazard tracking, temperature control requirements, and dimensional data.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string|null $barcode
 * @property string|null $description
 * @property string $unit
 * @property float $weight
 * @property string $weight_unit
 * @property array|null $dimensions
 * @property bool $is_hazardous
 * @property bool $is_fragile
 * @property bool $requires_temperature_control
 * @property float|null $min_temperature
 * @property float|null $max_temperature
 * @property string|null $category
 * @property string|null $brand
 * @property bool $is_active
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseProduct extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_products';

    protected $fillable = [
        'sku',
        'name',
        'barcode',
        'description',
        'unit',
        'weight',
        'weight_unit',
        'dimensions',
        'is_hazardous',
        'is_fragile',
        'requires_temperature_control',
        'min_temperature',
        'max_temperature',
        'category',
        'brand',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'dimensions' => 'json',
        'is_hazardous' => 'boolean',
        'is_fragile' => 'boolean',
        'requires_temperature_control' => 'boolean',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Batches of this product
     */
    public function batches(): HasMany
    {
        return $this->hasMany(WarehouseBatch::class, 'product_id');
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for hazardous products
     */
    public function scopeHazardous($query)
    {
        return $query->where('is_hazardous', true);
    }

    /**
     * Scope for temperature controlled products
     */
    public function scopeTemperatureControlled($query)
    {
        return $query->where('requires_temperature_control', true);
    }
}
