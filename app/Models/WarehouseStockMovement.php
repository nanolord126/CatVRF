<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Warehouse Stock Movement Model
 *
 * Tracks all stock movements within warehouses for audit trail and analytics.
 * Supports multiple movement types: receipt, transfer, picking, shipment, etc.
 *
 * @property int $id
 * @property string $warehouse_id
 * @property string|null $from_zone_id
 * @property string|null $to_zone_id
 * @property string $inventory_item_id
 * @property string $product_sku
 * @property int $quantity
 * @property string $movement_type
 * @property string|null $order_type
 * @property string|null $order_id
 * @property string|null $branch_id
 * @property string|null $reason
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseStockMovement extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_stock_movements';

    protected $fillable = [
        'warehouse_id',
        'from_zone_id',
        'to_zone_id',
        'inventory_item_id',
        'product_sku',
        'quantity',
        'movement_type',
        'order_type',
        'order_id',
        'branch_id',
        'reason',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'movement_type' => 'string',
        'order_type' => 'string',
        'metadata' => 'json',
    ];

    /**
     * Warehouse where movement occurred
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Source zone
     */
    public function fromZone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'from_zone_id');
    }

    /**
     * Destination zone
     */
    public function toZone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'to_zone_id');
    }

    /**
     * Inventory item
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Scope for receipts
     */
    public function scopeReceipt($query)
    {
        return $query->where('movement_type', 'receipt');
    }

    /**
     * Scope for shipments
     */
    public function scopeShipment($query)
    {
        return $query->where('movement_type', 'shipment');
    }

    /**
     * Scope for transfers
     */
    public function scopeTransfer($query)
    {
        return $query->where('movement_type', 'transfer');
    }

    /**
     * Scope for picking operations
     */
    public function scopePicking($query)
    {
        return $query->where('movement_type', 'picking');
    }

    /**
     * Scope for adjustments
     */
    public function scopeAdjustment($query)
    {
        return $query->where('movement_type', 'adjustment');
    }

    /**
     * Scope for write-offs
     */
    public function scopeWriteOff($query)
    {
        return $query->whereIn('movement_type', ['damage', 'loss']);
    }

    /**
     * Scope for specific order
     */
    public function scopeForOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if movement is inbound (increases stock)
     */
    public function isInbound(): bool
    {
        return in_array($this->movement_type, ['receipt', 'return', 'adjustment']);
    }

    /**
     * Check if movement is outbound (decreases stock)
     */
    public function isOutbound(): bool
    {
        return in_array($this->movement_type, ['shipment', 'picking', 'packing', 'damage', 'loss']);
    }

    /**
     * Check if movement is internal transfer
     */
    public function isTransfer(): bool
    {
        return $this->movement_type === 'transfer';
    }
}
