<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $restaurant_order_id
 * @property string $status
 * @property int $delivery_price
 */
final class DeliveryOrder extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'tenant_id',
        'restaurant_order_id',
        'courier_id',
        'customer_address',
        'delivery_point',
        'delivery_price',
        'status',
        'distance_km',
        'eta_minutes',
        'picked_up_at',
        'delivered_at',
        'surge_multiplier',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'delivery_point' => 'json',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_price' => 'integer',
        'distance_km' => 'integer',
        'eta_minutes' => 'integer',
        'surge_multiplier' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }
}
