<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GroceryOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'grocery_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'user_id', 'store_id', 'delivery_partner_id', 'status',
        'total_price', 'delivery_price', 'commission_amount', 'delivery_slot_id',
        'delivery_address', 'lat', 'lon', 'placed_at', 'delivered_at', 'tags', 'correlation_id'
    ];

    protected $casts = [
        'total_price' => 'integer',
        'delivery_price' => 'integer',
        'commission_amount' => 'integer',
        'lat' => 'float',
        'lon' => 'float',
        'placed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class, 'store_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(GroceryOrderItem::class, 'order_id');
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
    }
}
