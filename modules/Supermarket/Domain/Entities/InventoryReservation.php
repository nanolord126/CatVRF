<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryReservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'order_id',
        'order_type',
        'sku',
        'quantity',
        'status',
        'reserved_at',
        'expires_at',
        'released_at',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $table = 'inventory_reservations';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(UnifiedWarehouse::class, 'warehouse_id');
    }

    public function scopeByOrderType($query, string $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '<=', now());
    }

    public function release(): bool
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
        ]);

        return true;
    }

    public function isExpired(): bool
    {
        return $this->status === 'reserved' && $this->expires_at->isPast();
    }
}
