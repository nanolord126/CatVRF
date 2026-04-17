<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DeliverySlot extends Model
{

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
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id));
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
