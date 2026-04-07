<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SlotBooking extends Model
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
