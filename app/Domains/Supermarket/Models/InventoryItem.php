<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryItem extends Model
{
    protected $table = 'supermarket_inventory_items';

    protected $fillable = [
        'product_id',
        'quantity',
        'reserved',
        'expires_at',
        'batch_number',
        'location',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->whereRaw('(quantity - reserved) <= ?', [$threshold]);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}
