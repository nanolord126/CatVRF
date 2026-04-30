<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Restaurant\Enums\OrderStatus;
use Modules\Restaurant\Enums\OrderType;

final class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'restaurant_orders';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'uuid',
        'order_number',
        'type',
        'status',
        'table_id',
        'user_id',
        'waiter_id',
        'courier_id',
        'crm_deal_id',
        'items_count',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'service_fee',
        'tax_amount',
        'total_amount',
        'currency',
        'order_time',
        'estimated_ready_time',
        'actual_ready_time',
        'served_time',
        'pickup_time',
        'delivery_address',
        'delivery_phone',
        'delivery_name',
        'delivery_lat',
        'delivery_lon',
        'delivery_instructions',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
        'paid_at',
        'special_requests',
        'notes',
        'cancellation_reason',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'type' => OrderType::class,
        'status' => OrderStatus::class,
        'items_count' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_time' => 'datetime',
        'estimated_ready_time' => 'datetime',
        'actual_ready_time' => 'datetime',
        'served_time' => 'datetime',
        'pickup_time' => 'datetime',
        'paid_at' => 'datetime',
        'delivery_lat' => 'float',
        'delivery_lon' => 'float',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByType($query, OrderType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDineIn($query)
    {
        return $query->where('type', OrderType::DINE_IN);
    }

    public function scopeDelivery($query)
    {
        return $query->where('type', OrderType::DELIVERY);
    }

    public function scopePickup($query)
    {
        return $query->where('type', OrderType::PICKUP);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_time', today());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            OrderStatus::CONFIRMED,
            OrderStatus::PREPARING,
            OrderStatus::READY,
            OrderStatus::DELIVERING,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        return $this->transitionTo(OrderStatus::CONFIRMED);
    }

    public function startPreparing(): bool
    {
        return $this->transitionTo(OrderStatus::PREPARING);
    }

    public function markReady(): bool
    {
        if (!$this->transitionTo(OrderStatus::READY)) {
            return false;
        }

        $this->actual_ready_time = now();
        return $this->save();
    }

    public function markServed(): bool
    {
        if (!$this->transitionTo(OrderStatus::SERVED)) {
            return false;
        }

        $this->served_time = now();
        return $this->save();
    }

    public function startDelivery(): bool
    {
        return $this->transitionTo(OrderStatus::DELIVERING);
    }

    public function markDelivered(): bool
    {
        if (!$this->transitionTo(OrderStatus::DELIVERED)) {
            return false;
        }

        $this->served_time = now();
        return $this->save();
    }

    public function markPickedUp(): bool
    {
        if (!$this->transitionTo(OrderStatus::PICKED_UP)) {
            return false;
        }

        $this->pickup_time = now();
        return $this->save();
    }

    public function complete(): bool
    {
        return $this->transitionTo(OrderStatus::COMPLETED);
    }

    public function cancel(string $reason): bool
    {
        if (!$this->transitionTo(OrderStatus::CANCELLED)) {
            return false;
        }

        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function transitionTo(OrderStatus $newStatus): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;
        return $this->save();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total_price');
        $this->items_count = $this->items()->sum('quantity');
        $this->total_amount = $this->subtotal 
            - $this->discount_amount 
            + $this->delivery_fee 
            + $this->service_fee 
            + $this->tax_amount;
    }

    public function isDineIn(): bool
    {
        return $this->type === OrderType::DINE_IN;
    }

    public function isDelivery(): bool
    {
        return $this->type === OrderType::DELIVERY;
    }

    public function isPickup(): bool
    {
        return $this->type === OrderType::PICKUP;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getPreparationTime(): ?int
    {
        if ($this->actual_ready_time === null || $this->order_time === null) {
            return null;
        }

        return $this->order_time->diffInMinutes($this->actual_ready_time);
    }

    /**
     * Получить цвет статуса для Filament.
     */
    public function getStatusColor(): string
    {
        return $this->status->filamentColor();
    }

    /**
     * Получить иконку статуса.
     */
    public function getStatusIcon(): string
    {
        return $this->status->icon();
    }

    /**
     * Получить метку статуса.
     */
    public function getStatusLabel(): string
    {
        return $this->status->label();
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

            if (!$model->order_number) {
                $model->order_number = 'ORD-' . date('Ymd-His') . '-' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
            }

            if (!$model->order_time) {
                $model->order_time = now();
            }
        });
    }
}
