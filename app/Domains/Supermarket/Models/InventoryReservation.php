<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryReservation extends Model
{
    protected $table = 'supermarket_inventory_reservations';

    protected $fillable = [
        'inventory_item_id',
        'order_id',
        'quantity',
        'reserved_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupermarketOrder::class, 'order_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'active');
    }
}
