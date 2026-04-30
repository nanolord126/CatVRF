<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Restaurants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Restaurant Order — Заказ в вертикали Рестораны
 * 
 * Расширяет базовую Deal модель специфичными полями для ресторанов.
 */
final class RestaurantOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'restaurant_id',
        'order_number',
        'order_type',
        'order_status',
        'table_id',
        'guest_count',
        'order_date',
        'order_time',
        'estimated_ready_time',
        'actual_ready_time',
        'served_time',
        'pickup_time',
        'delivery_address',
        'delivery_phone',
        'delivery_name',
        'special_requests',
        'allergies',
        'dietary_restrictions',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'service_fee',
        'tax_amount',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
        'notes',
        'cancellation_reason',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'order_time' => 'datetime',
        'estimated_ready_time' => 'datetime',
        'actual_ready_time' => 'datetime',
        'served_time' => 'datetime',
        'pickup_time' => 'datetime',
        'guest_count' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'allergies' => 'json',
        'dietary_restrictions' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_restaurant_orders';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('order_status', $status);
    }

    public function scopeDineIn($query)
    {
        return $query->where('order_type', 'dine_in');
    }

    public function scopeDelivery($query)
    {
        return $query->where('order_type', 'delivery');
    }

    public function scopePickup($query)
    {
        return $query->where('order_type', 'pickup');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('order_date', '>=', now())
            ->whereIn('order_status', ['pending', 'confirmed']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('order_status', ['confirmed', 'preparing', 'ready', 'delivering']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('order_status', 'cancelled');
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->order_status = 'confirmed';
        return $this->save();
    }

    public function startPreparing(): bool
    {
        $this->order_status = 'preparing';
        return $this->save();
    }

    public function markReady(): bool
    {
        $this->order_status = 'ready';
        $this->actual_ready_time = now();
        return $this->save();
    }

    public function markServed(): bool
    {
        $this->order_status = 'served';
        $this->served_time = now();
        return $this->save();
    }

    public function startDelivery(): bool
    {
        $this->order_status = 'delivering';
        return $this->save();
    }

    public function markDelivered(): bool
    {
        $this->order_status = 'delivered';
        $this->served_time = now();
        return $this->save();
    }

    public function markPickedUp(): bool
    {
        $this->order_status = 'picked_up';
        $this->pickup_time = now();
        return $this->save();
    }

    public function complete(): bool
    {
        $this->order_status = 'completed';
        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->order_status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function getPreparationTime(): ?int
    {
        if ($this->actual_ready_time === null || $this->order_time === null) {
            return null;
        }

        return $this->order_time->diffInMinutes($this->actual_ready_time);
    }

    public function isDineIn(): bool
    {
        return $this->order_type === 'dine_in';
    }

    public function isDelivery(): bool
    {
        return $this->order_type === 'delivery';
    }

    public function isPickup(): bool
    {
        return $this->order_type === 'pickup';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Генерация order_number
            if (!$model->order_number) {
                $model->order_number = 'RST-' . date('Ymd') . '-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            // Автоматический расчёт total_amount
            if ($model->total_amount === 0 && $model->subtotal > 0) {
                $model->total_amount = $model->subtotal 
                    - $model->discount_amount 
                    + $model->delivery_fee 
                    + $model->service_fee 
                    + $model->tax_amount;
            }
        });
    }
}
