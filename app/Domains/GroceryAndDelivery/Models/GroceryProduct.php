<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryProduct extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'grocery_products';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'sku', 'name', 'category', 'price',
            'current_stock', 'min_stock', 'max_stock', 'barcode', 'description',
            'image_url', 'weight_kg', 'is_active', 'tags', 'correlation_id'
        ];

        protected $casts = [
            'price' => 'integer',
            'current_stock' => 'integer',
            'weight_kg' => 'float',
            'is_active' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(GroceryStore::class, 'store_id');
        }

        public function orderItems(): HasMany
        {
            return $this->hasMany(GroceryOrderItem::class, 'product_id');
        }
    }

    final class GroceryOrder extends BaseModel
    {
        use HasFactory, SoftDeletes;

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
            static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', tenant()->id));
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

    final class GroceryOrderItem extends BaseModel
    {
        use HasFactory;

        protected $table = 'grocery_order_items';

        protected $fillable = [
            'order_id', 'product_id', 'quantity', 'price_per_unit', 'total_price', 'correlation_id'
        ];

        protected $casts = [
            'quantity' => 'integer',
            'price_per_unit' => 'integer',
            'total_price' => 'integer',
        ];

        public function order(): BelongsTo
        {
            return $this->belongsTo(GroceryOrder::class);
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(GroceryProduct::class);
        }
    }

    final class DeliverySlot extends BaseModel
    {
        use HasFactory;

        protected $table = 'delivery_slots';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'slot_type', 'start_time', 'end_time',
            'max_orders', 'current_orders', 'surge_multiplier', 'is_available', 'tags', 'correlation_id'
        ];

        protected $casts = [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_orders' => 'integer',
            'current_orders' => 'integer',
            'surge_multiplier' => 'float',
            'is_available' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(GroceryStore::class, 'store_id');
        }

        public function slotBookings(): HasMany
        {
            return $this->hasMany(SlotBooking::class, 'slot_id');
        }
    }

    final class SlotBooking extends BaseModel
    {
        use HasFactory;

        protected $table = 'slot_bookings';

        protected $fillable = [
            'uuid', 'delivery_slot_id', 'user_id', 'is_confirmed', 'booked_at', 'correlation_id'
        ];

        protected $casts = [
            'is_confirmed' => 'boolean',
            'booked_at' => 'datetime',
        ];

        public function deliverySlot(): BelongsTo
        {
            return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
        }
    }

    final class DeliveryPartner extends BaseModel
    {
        use HasFactory, SoftDeletes;

        protected $table = 'delivery_partners';

        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'user_id', 'status', 'vehicle_type',
            'phone', 'rating', 'completed_orders', 'current_location_lat', 'current_location_lon',
            'working_hours_json', 'tags', 'correlation_id'
        ];

        protected $casts = [
            'rating' => 'float',
            'completed_orders' => 'integer',
            'current_location_lat' => 'float',
            'current_location_lon' => 'float',
            'working_hours_json' => 'json',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(GroceryStore::class, 'store_id');
        }

        public function deliveries(): HasMany
        {
            return $this->hasMany(GroceryOrder::class, 'delivery_partner_id');
        }
    }

    final class DeliveryLog extends BaseModel
    {
        use HasFactory;

        protected $table = 'delivery_logs';

        protected $fillable = [
            'uuid', 'order_id', 'partner_id', 'event_type', 'status', 'location_lat',
            'location_lon', 'notes', 'timestamp', 'correlation_id'
        ];

        protected $casts = [
            'location_lat' => 'float',
            'location_lon' => 'float',
            'timestamp' => 'datetime',
        ];

        public function order(): BelongsTo
        {
            return $this->belongsTo(GroceryOrder::class);
        }

        public function partner(): BelongsTo
        {
            return $this->belongsTo(DeliveryPartner::class, 'partner_id');
        }
}
