<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Склад (Warehouse).
 *
 * tenant-aware, с координатами и рабочими часами.
 * Каждый tenant может иметь несколько складов.
 *
 * @property int         $id
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property string      $uuid
 * @property string      $name
 * @property string      $address
 * @property float       $lat
 * @property float       $lon
 * @property array|null  $working_hours
 * @property bool        $is_active
 * @property string|null $correlation_id
 * @property array|null  $tags
 */
final class Warehouse extends Model
{

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'name',
        'address',
        'lat',
        'lon',
        'working_hours',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'working_hours' => 'json',
        'tags'          => 'json',
        'is_active'     => 'boolean',
        'lat'           => 'decimal:8',
        'lon'           => 'decimal:8',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'warehouse_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
