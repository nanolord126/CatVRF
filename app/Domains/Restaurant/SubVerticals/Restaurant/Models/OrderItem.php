<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'restaurant_order_items';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'order_id',
        'uuid',
        'menu_item_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
        'kitchen_status',
        'kitchen_started_at',
        'kitchen_ready_at',
        'modifiers',
        'addons',
        'allergens',
        'special_instructions',
        'cancellation_reason',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'kitchen_started_at' => 'datetime',
        'kitchen_ready_at' => 'datetime',
        'modifiers' => 'json',
        'addons' => 'json',
        'allergens' => 'json',
        'metadata' => 'json',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopePending($query)
    {
        return $query->where('kitchen_status', 'pending');
    }

    public function scopePreparing($query)
    {
        return $query->where('kitchen_status', 'preparing');
    }

    public function scopeReady($query)
    {
        return $query->where('kitchen_status', 'ready');
    }

    public function scopeServed($query)
    {
        return $query->where('kitchen_status', 'served');
    }

    public function scopeCancelled($query)
    {
        return $query->where('kitchen_status', 'cancelled');
    }

    // ========================
    // METHODS
    // ========================

    public function startPreparing(): bool
    {
        $this->kitchen_status = 'preparing';
        $this->kitchen_started_at = now();
        return $this->save();
    }

    public function markReady(): bool
    {
        $this->kitchen_status = 'ready';
        $this->kitchen_ready_at = now();
        return $this->save();
    }

    public function markServed(): bool
    {
        $this->kitchen_status = 'served';
        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->kitchen_status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function calculateTotal(): void
    {
        $this->total_price = ($this->unit_price * $this->quantity) - $this->discount_amount;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });

        self::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }

            $model->calculateTotal();
        });
    }
}
