<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Единица учёта на складе (InventoryItem).
 *
 * Связывает product ↔ warehouse, хранит quantity/reserved.
 * Virtual column `available` = quantity − reserved.
 *
 * @property int         $id
 * @property int         $warehouse_id
 * @property int         $product_id
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property string      $uuid
 * @property int         $quantity
 * @property int         $reserved
 * @property string|null $cost_price
 * @property string|null $correlation_id
 * @property array|null  $tags
 */
final class InventoryItem extends Model
{

    protected $table = 'inventories';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'tenant_id',
        'business_group_id',
        'uuid',
        'quantity',
        'reserved',
        'cost_price',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'reserved'   => 'integer',
        'cost_price' => 'decimal:2',
        'tags'       => 'json',
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
    /*  Computed                                                           */
    /* ------------------------------------------------------------------ */

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'inventory_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
