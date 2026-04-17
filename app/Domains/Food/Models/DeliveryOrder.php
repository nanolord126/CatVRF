<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;

final class DeliveryOrder extends Model
{
    use HasFactory;

    protected $table = "food_delivery_orders";

    protected $fillable = [
        "tenant_id",
        "food_order_id",
        "courier_id",
        "uuid",
        "correlation_id",
        "status",
        "customer_address",
        "delivery_lat",
        "delivery_lon",
        "delivery_point",
        "distance_km",
        "eta_minutes",
        "picked_up_at",
        "delivered_at",
        "cancelled_at",
        "cancellation_reason",
        "metadata",
    ];

    protected $casts = [
        "delivery_lat" => "decimal:8",
        "delivery_lon" => "decimal:8",
        "distance_km" => "decimal:2",
        "eta_minutes" => "integer",
        "picked_up_at" => "datetime",
        "delivered_at" => "datetime",
        "cancelled_at" => "datetime",
        "metadata" => "json",
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ON_WAY = 'on_way';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder $query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                $query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, "tenant_id");
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class, "food_order_id");
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, "courier_id");
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOnWay(): bool
    {
        return $this->status === self::STATUS_ON_WAY;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
