<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\CarbonImmutable;

/**
 * Order — Доменная модель заказа супермаркета
 */
final class Order extends Model
{
    protected $table = 'supermarket_orders';

    protected $fillable = [
        'uuid',
        'order_number',
        'customer_id',
        'seller_id',
        'tenant_id',
        'delivery_type',
        'order_status',
        'total_amount',
        'subtotal_amount',
        'delivery_fee',
        'service_fee',
        'discount_amount',
        'cashback_used',
        'loyalty_points_used',
        'courier_id',
        'courier_name',
        'courier_phone',
        'courier_location_lat',
        'courier_location_lng',
        'delivery_eta',
        'delivery_address',
        'pickup_zone',
        'locker_number',
        'pickup_window_start',
        'pickup_window_end',
        'qr_code',
        'total_calories',
        'total_proteins',
        'total_fats',
        'total_carbs',
        'allergen_warnings',
        'dietary_restrictions',
        'is_b2b',
        'honest_marks',
        'dynamic_pricing_applied',
        'platform_margin',
        'confirmed_at',
        'processing_started_at',
        'pickup_ready_at',
        'courier_assigned_at',
        'delivery_actual_at',
        'pickup_actual_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cashback_used' => 'decimal:2',
        'loyalty_points_used' => 'integer',
        'courier_location_lat' => 'decimal:8',
        'courier_location_lng' => 'decimal:8',
        'delivery_eta' => 'datetime',
        'delivery_address' => 'array',
        'pickup_window_start' => 'datetime',
        'pickup_window_end' => 'datetime',
        'total_calories' => 'integer',
        'total_proteins' => 'decimal:2',
        'total_fats' => 'decimal:2',
        'total_carbs' => 'decimal:2',
        'allergen_warnings' => 'array',
        'dietary_restrictions' => 'array',
        'is_b2b' => 'boolean',
        'honest_marks' => 'array',
        'dynamic_pricing_applied' => 'boolean',
        'platform_margin' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'pickup_ready_at' => 'datetime',
        'courier_assigned_at' => 'datetime',
        'delivery_actual_at' => 'datetime',
        'pickup_actual_at' => 'datetime',
    ];

    // Delivery types
    public const DELIVERY_COURIER = 'courier';
    public const DELIVERY_PICKUP = 'pickup';

    // Order statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    /**
     * Отношения
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'supermarket_order_items', 'order_id', 'product_id')
            ->withPivot(['quantity', 'price', 'subtotal']);
    }

    /**
     * Получить статус для отображения
     */
    public function getStatusLabel(): string
    {
        return match($this->order_status) {
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтверждён',
            self::STATUS_PROCESSING => 'В сборке',
            self::STATUS_READY => 'Готов',
            self::STATUS_DELIVERING => 'В пути',
            self::STATUS_DELIVERED => 'Доставлен',
            self::STATUS_PICKED_UP => 'Получен',
            self::STATUS_CANCELLED => 'Отменён',
            self::STATUS_FAILED => 'Ошибка',
            default => 'Неизвестно',
        };
    }

    /**
     * Проверить может ли быть возвращён
     */
    public function canBeReturned(): bool
    {
        if (in_array($this->order_status, [self::STATUS_CANCELLED, self::STATUS_FAILED])) {
            return false;
        }

        // Проверка срока (по умолчанию 7 дней)
        if ($this->delivery_actual_at || $this->pickup_actual_at) {
            $completionDate = $this->delivery_actual_at ?? $this->pickup_actual_at;
            return now()->diffInDays($completionDate) <= 7;
        }

        return false;
    }

    /**
     * Получить прогресс выполнения (0-100%)
     */
    public function getProgress(): int
    {
        return match($this->order_status) {
            self::STATUS_PENDING => 0,
            self::STATUS_CONFIRMED => 10,
            self::STATUS_PROCESSING => 30,
            self::STATUS_READY => 50,
            self::STATUS_DELIVERING => 75,
            self::STATUS_DELIVERED, self::STATUS_PICKED_UP => 100,
            self::STATUS_CANCELLED, self::STATUS_FAILED => 0,
            default => 0,
        };
    }
}
