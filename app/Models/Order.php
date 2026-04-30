<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;

/**
 * Центральная модель заказа маркетплейса CatVRF.
 * Канон 2026 — PRODUCTION MANDATORY.
 *
 * Все суммы хранятся в КОПЕЙКАХ (unsignedBigInteger).
 * Tenant-scoped через global scope.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $user_id
 * @property int|null $business_group_id
 * @property string $vertical
 * @property string $status
 * @property int $subtotal Копейки
 * @property int $shipping_cost Копейки
 * @property int $discount_amount Копейки
 * @property int $total Копейки
 * @property int $platform_commission Копейки
 * @property int $seller_earnings Копейки
 * @property string $currency
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $payment_id
 * @property string $is_b2b
 * @property string|null $inn
 * @property string|null $business_card_id
 * @property string|null $delivery_address
 * @property float|null $delivery_lat
 * @property float|null $delivery_lon
 * @property string|null $tracking_number
 * @property string $refund_status
 * @property int $refund_amount
 * @property string|null $correlation_id
 * @property int|null $fulfillment_id Unified Logistics Core - ID курьера или ПВЗ
 * @property string|null $fulfillment_type Unified Logistics Core - Courier|PickupPoint
 */
final class Order extends Model
{
    /** Статусы заказа */
    public const STATUS_PENDING    = 'pending';

    public const STATUS_CONFIRMED  = 'confirmed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED    = 'shipped';

    public const STATUS_DELIVERED  = 'delivered';

    public const STATUS_CANCELLED  = 'cancelled';

    public const STATUS_REFUNDED   = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING    => 'Ожидает подтверждения',
        self::STATUS_CONFIRMED  => 'Подтверждён',
        self::STATUS_PROCESSING => 'В обработке',
        self::STATUS_SHIPPED    => 'Отправлен',
        self::STATUS_DELIVERED  => 'Доставлен',
        self::STATUS_CANCELLED  => 'Отменён',
        self::STATUS_REFUNDED   => 'Возврат',
    ];

    /** Статусы оплаты */
    public const PAYMENT_PENDING        = 'pending';

    public const PAYMENT_PAID           = 'paid';

    public const PAYMENT_FAILED         = 'failed';

    public const PAYMENT_REFUNDED       = 'refunded';

    public const PAYMENT_PARTIAL_REFUND = 'partial_refund';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING        => 'Ожидает',
        self::PAYMENT_PAID           => 'Оплачен',
        self::PAYMENT_FAILED         => 'Ошибка',
        self::PAYMENT_REFUNDED       => 'Возвращён',
        self::PAYMENT_PARTIAL_REFUND => 'Частичный возврат',
    ];

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'business_group_id',
        'vertical',
        'status',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'total',
        'platform_commission',
        'seller_earnings',
        'currency',
        'payment_status',
        'payment_method',
        'payment_id',
        'paid_at',
        'is_b2b',
        'inn',
        'business_card_id',
        'delivery_address',
        'delivery_lat',
        'delivery_lon',
        'tracking_number',
        'refund_status',
        'refund_amount',
        'refunded_at',
        'metadata',
        'tags',
        'correlation_id',
        'fulfillment_id',
        'fulfillment_type',
        'version',
    ];

    protected $casts = [
        'is_b2b'             => 'boolean',
        'metadata'           => 'array',
        'tags'               => 'array',
        'paid_at'            => 'datetime',
        'refunded_at'        => 'datetime',
        'subtotal'           => 'integer',
        'shipping_cost'      => 'integer',
        'discount_amount'    => 'integer',
        'total'              => 'integer',
        'platform_commission' => 'integer',
        'seller_earnings'    => 'integer',
        'refund_amount'      => 'integer',
        'delivery_lat'       => 'float',
        'delivery_lon'       => 'float',
        'version'            => 'integer',
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    // ─── Отношения ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    /**
     * Unified Logistics Core - Fulfillment relationship
     * Order can be fulfilled by Courier or PickupPoint (PVZ)
     */
    public function fulfillment()
    {
        return $this->morphTo();
    }

    /**
     * Get the courier that fulfills this order
     */
    public function courier()
    {
        return $this->morphOne(Courier::class, 'fulfillable');
    }

    /**
     * Get the pickup point that fulfills this order
     */
    public function pickupPoint()
    {
        return $this->morphOne(PickupPoint::class, 'fulfillable');
    }

    /**
     * Get the order shipment for this order
     */
    public function shipment()
    {
        return $this->hasOne(OrderShipment::class, 'order_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function scopeForVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeB2b($query)
    {
        return $query->where('is_b2b', true);
    }

    public function scopeB2c($query)
    {
        return $query->where('is_b2b', false);
    }

    // ─── Вспомогательные методы ──────────────────────────────────────────────

    /** Итоговая сумма в рублях (для отображения) */
    public function totalInRubles(): float
    {
        return $this->total / 100;
    }

    /** Можно ли отменить заказ */
    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ], true);
    }

    /** Заказ оплачен */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    protected static function booted(): void
    {
        // Tenant-scoping — канон CatVRF 2026
        self::addGlobalScope('tenant', static function ($query): void {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('orders.tenant_id', tenant()->id);
            }
        });

        // Автоматический UUID
        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = request()->header('X-Correlation-ID') ?? Str::uuid()->toString();
            }
            if (is_null($model->version)) {
                $model->version = 1;
            }
        });

        static::updating(static function (self $model): void {
            $model->version = $model->version + 1;
        });
    }

    /**
     * Update model with optimistic locking
     */
    public function updateWithLock(array $attributes, int $expectedVersion): bool
    {
        $affected = static::where('id', $this->id)
            ->where('version', $expectedVersion)
            ->update($attributes + ['version' => $expectedVersion + 1]);

        if ($affected === 0) {
            $this->refresh();
            throw new \App\Exceptions\OptimisticLockException(
                'order',
                $this->id,
                $expectedVersion,
                $this->version
            );
        }

        return true;
    }

    /**
     * Scope to filter by version
     */
    public function scopeWithVersion($query, int $version)
    {
        return $query->where('version', $version);
    }
}
