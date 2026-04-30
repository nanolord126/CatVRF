<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Order Shipment Model
 *
 * Represents a shipment for an order that can be fulfilled by:
 * - Courier (regular or taxi driver)
 * - Pickup Point (ПВЗ)
 *
 * Follows CatVRF production standards with polymorphic fulfillment.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property int|null $courier_id
 * @property int|null $pickup_point_id
 * @property string|null $fulfillment_type
 * @property int|null $fulfillment_id
 * @property string $status
 * @property int|null $eta_minutes
 * @property string|null $route_polyline
 * @property float|null $distance_km
 * @property Carbon|null $assigned_at
 * @property Carbon|null $picked_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $issued_at_pvz
 * @property string|null $qr_code
 * @property string|null $pickup_code
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OrderShipment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    /**
     * Shipment statuses
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_PICKED = 'picked';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_ISSUED_AT_PVZ = 'issued_at_pvz';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    /**
     * Fulfillment types
     */
    public const FULFILLMENT_COURIER = 'courier';

    public const FULFILLMENT_PICKUP_POINT = 'pickup_point';

    public const FULFILLMENT_TAXI = 'taxi';

    protected $table = 'order_shipments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'courier_id',
        'pickup_point_id',
        'fulfillment_type',
        'fulfillment_id',
        'status',
        'eta_minutes',
        'route_polyline',
        'distance_km',
        'assigned_at',
        'picked_at',
        'delivered_at',
        'issued_at_pvz',
        'qr_code',
        'pickup_code',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'eta_minutes' => 'integer',
        'distance_km' => 'float',
        'assigned_at' => 'datetime',
        'picked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'issued_at_pvz' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Check if shipment is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
            self::STATUS_PICKED,
            self::STATUS_IN_TRANSIT,
        ], true);
    }

    /**
     * Check if shipment is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_ISSUED_AT_PVZ,
        ], true);
    }

    /**
     * Check if shipment is courier-based
     */
    public function isCourierBased(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_COURIER
            || $this->fulfillment_type === self::FULFILLMENT_TAXI;
    }

    /**
     * Check if shipment is PVZ-based
     */
    public function isPvzBased(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_PICKUP_POINT;
    }

    /**
     * Generate pickup code for PVZ issuance
     */
    public function generatePickupCode(): string
    {
        $this->pickup_code = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $this->save();

        return $this->pickup_code;
    }

    /**
     * Generate QR code for tracking
     */
    public function generateQrCode(): string
    {
        $this->qr_code = 'SHIP-'.$this->uuid;
        $this->save();

        return $this->qr_code;
    }

    // --- RELATIONS ---

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class, 'pickup_point_id');
    }

    /**
     * Polymorphic relationship to fulfillment entity (Courier or PickupPoint)
     */
    public function fulfillment(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Boot method for UUID generation
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}
