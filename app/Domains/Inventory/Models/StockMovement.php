<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Движение остатков (StockMovement).
 *
 * Фиксирует каждое изменение количества на складе:
 * in, out, reserve, release, return, adjustment.
 *
 * @property int         $id
 * @property int         $inventory_id
 * @property int         $warehouse_id
 * @property int         $tenant_id
 * @property string      $uuid
 * @property string      $type
 * @property int         $quantity
 * @property string      $source_type
 * @property int|null    $source_id
 * @property string|null $correlation_id
 * @property array|null  $tags
 * @property array|null  $metadata
 */
final class StockMovement extends Model
{

    protected $table = 'stock_movements';

    protected $fillable = [
        'inventory_id',
        'warehouse_id',
        'tenant_id',
        'uuid',
        'type',
        'quantity',
        'source_type',
        'source_id',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'tags'     => 'json',
        'metadata' => 'json',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
