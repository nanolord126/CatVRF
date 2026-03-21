<?php declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $restaurant_id
 * @property string $client_id
 * @property string $status
 * @property int $total_price
 */
final class RestaurantOrder extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'restaurant_orders';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'table_id',
        'client_id',
        'order_number',
        'status',
        'items_json',
        'subtotal_price',
        'delivery_price',
        'commission_price',
        'total_price',
        'payment_status',
        'started_at',
        'ready_at',
        'completed_at',
        'customer_notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'items_json' => 'collection',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'subtotal_price' => 'integer',
        'delivery_price' => 'integer',
        'commission_price' => 'integer',
        'total_price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class, 'restaurant_order_id');
    }

    public function kds(): HasOne
    {
        return $this->hasOne(KDSOrder::class, 'restaurant_order_id');
    }
}
